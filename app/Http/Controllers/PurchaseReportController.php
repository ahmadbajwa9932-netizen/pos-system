<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        return view('pages.reports.purchaseReport', compact('startDate', 'endDate'));
    }

    // Overview Summary Cards
    public function getSummary(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $summary = Purchase::whereBetween('purchase_date', [$dates['start'], $dates['end']])
            ->selectRaw('
                COUNT(*) as total_purchases,
 COUNT(DISTINCT CASE 
                WHEN supplier_id IS NOT NULL 
                AND supplier_id != "" 
                AND supplier_id != "N/A" 
                AND supplier_id != "none"
                AND supplier_id != "0" 
                THEN supplier_id 
            END) as total_suppliers,                
            COUNT(DISTINCT product_name) as total_products,
                SUM(purchased_price * quantity) as total_purchase_amount,
                SUM(quantity) as total_quantity,
                SUM(quantity - sold_quantity) as stock_in_hand,
                SUM(purchased_price*(quantity - sold_quantity)) as available_purchase_amount

            ')
            ->first();

        // Calculate average purchase value
        $avgPurchaseValue = $summary->total_purchases > 0 
            ? $summary->total_purchase_amount / $summary->total_purchases 
            : 0;

        return response()->json([
            'total_purchases' => $summary->total_purchases ?? 0,
            'total_suppliers' => $summary->total_suppliers ?? 0,
            'total_products' => $summary->total_products ?? 0,
            'total_purchase_amount' => $summary->total_purchase_amount ?? 0,
            'total_quantity' => $summary->total_quantity ?? 0,
            'stock_in_hand' => $summary->stock_in_hand ?? 0,
            'avg_purchase_value' => $avgPurchaseValue,
            'available_purchase_amount'=>$summary->available_purchase_amount ?? 0
        ]);
    }

    // Purchase List with Filters
    public function getPurchases(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $query = Purchase::with(['supplier', 'category'])
            ->whereBetween('purchase_date', [$dates['start'], $dates['end']]);

        // Apply filters
        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->product_name) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('product_name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('supplier', function($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'purchase_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $purchases = $query->paginate(15);

        $data = $purchases->map(function($purchase) {
            $totalAmount = $purchase->purchased_price * $purchase->quantity;
            $stockValue = $purchase->purchased_price * ($purchase->quantity - $purchase->sold_quantity);
            
            return [
                'id' => $purchase->id,
                'purchase_date' => Carbon::parse($purchase->purchase_date)->format('d-M-Y'),
                'supplier_name' => $purchase->supplier->name ?? 'N/A',
                'supplier_company' => $purchase->supplier->company ?? '',
                'product_name' => $purchase->product_name,
                'category' => $purchase->category->name ?? 'Misc',
                'quantity' => $purchase->quantity,
                'sold_quantity' => $purchase->sold_quantity,
                'remaining_stock' => $purchase->quantity - $purchase->sold_quantity,
                'unit' => $purchase->unit,
                'purchased_price' => $purchase->purchased_price,
                'sold_price' => $purchase->sold_price,
                'total_amount' => $totalAmount,
                'stock_value' => $stockValue,
                'profit_margin' => $purchase->sold_price > 0 
                    ? (($purchase->sold_price - $purchase->purchased_price) / $purchase->sold_price * 100) 
                    : 0
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $purchases->currentPage(),
            'last_page' => $purchases->lastPage(),
            'total' => $purchases->total()
        ]);
    }

    // Supplier-wise Summary
    public function getSupplierSummary(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $suppliers = Supplier::with(['purchases' => function($query) use ($dates) {
            $query->whereBetween('purchase_date', [$dates['start'], $dates['end']]);
        }])
        ->whereHas('purchases', function($query) use ($dates) {
            $query->whereBetween('purchase_date', [$dates['start'], $dates['end']]);
        })
        ->get()
        ->map(function($supplier) {
            $purchases = $supplier->purchases;
            $totalPurchases = $purchases->count();
            $totalAmount = $purchases->sum(function($p) {
                return $p->purchased_price * $p->quantity;
            });
            $totalProducts = $purchases->unique('product_name')->count();
            $lastPurchase = $purchases->sortByDesc('purchase_date')->first();
            
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'company' => $supplier->company,
                'contact_info' => $supplier->contact_info,
                'address' => $supplier->address,
                'total_purchases' => $totalPurchases,
                'total_products' => $totalProducts,
                'total_amount' => $totalAmount,
                'last_purchase_date' => $lastPurchase ? Carbon::parse($lastPurchase->purchase_date)->format('d-M-Y') : 'N/A',
                'avg_purchase_value' => $totalPurchases > 0 ? $totalAmount / $totalPurchases : 0
            ];
        })
        ->sortByDesc('total_amount')
        ->values();

        return response()->json($suppliers);
    }

    // Product-wise Summary
    public function getProductSummary(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $products = Purchase::with(['supplier', 'category'])
            ->whereBetween('purchase_date', [$dates['start'], $dates['end']])
            ->select('product_name', 'unit', 'category_id')
            ->selectRaw('
                COUNT(*) as purchase_count,
                SUM(quantity) as total_quantity,
                SUM(sold_quantity) as total_sold,
                SUM(quantity - sold_quantity) as remaining_stock,
                AVG(purchased_price) as avg_purchase_price,
                MAX(purchased_price) as max_purchase_price,
                MIN(purchased_price) as min_purchase_price,
                SUM(purchased_price * quantity) as total_cost,
                SUM(purchased_price * (quantity - sold_quantity)) as stock_value
            ')
            ->groupBy('product_name', 'unit', 'category_id')
            ->orderByDesc('total_cost')
            ->get()
            ->map(function($product) {
                return [
                    'product_name' => $product->product_name,
                    'category' => $product->category->name ?? 'Misc',
                    'unit' => $product->unit,
                    'purchase_count' => $product->purchase_count,
                    'total_quantity' => $product->total_quantity,
                    'total_sold' => $product->total_sold,
                    'remaining_stock' => $product->remaining_stock,
                    'avg_purchase_price' => $product->avg_purchase_price,
                    'max_purchase_price' => $product->max_purchase_price,
                    'min_purchase_price' => $product->min_purchase_price,
                    'total_cost' => $product->total_cost,
                    'stock_value' => $product->stock_value,
                    'stock_turnover' => $product->total_quantity > 0 
                        ? ($product->total_sold / $product->total_quantity * 100) 
                        : 0
                ];
            });

        return response()->json($products);
    }

    // Category-wise Analysis
    public function getCategoryAnalysis(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $categories = Category::with(['purchases' => function($query) use ($dates) {
            $query->whereBetween('purchase_date', [$dates['start'], $dates['end']]);
        }])
        ->whereHas('purchases', function($query) use ($dates) {
            $query->whereBetween('purchase_date', [$dates['start'], $dates['end']]);
        })
        ->get()
        ->map(function($category) {
            $purchases = $category->purchases;
            $totalCost = $purchases->sum(function($p) {
                return $p->purchased_price * $p->quantity;
            });
            $stockValue = $purchases->sum(function($p) {
                return $p->purchased_price * ($p->quantity - $p->sold_quantity);
            });
            
            return [
                'category' => $category->name,
                'total_products' => $purchases->unique('product_name')->count(),
                'total_purchases' => $purchases->count(),
                'total_quantity' => $purchases->sum('quantity'),
                'total_cost' => $totalCost,
                'stock_value' => $stockValue,
                'products' => $purchases->unique('product_name')->pluck('product_name')
            ];
        })
        ->sortByDesc('total_cost')
        ->values();

        return response()->json($categories);
    }

    // Monthly Trend Analysis
    public function getMonthlyTrend(Request $request)
    {
        $dates = $this->getDateRange($request);
        
        $trend = Purchase::whereBetween('purchase_date', [$dates['start'], $dates['end']])
            ->selectRaw('
                DATE_FORMAT(purchase_date, "%Y-%m") as month,
                COUNT(*) as total_purchases,
                COUNT(DISTINCT supplier_id) as suppliers_count,
                SUM(purchased_price * quantity) as total_amount,
                SUM(quantity) as total_quantity
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                return [
                    'month' => Carbon::parse($item->month . '-01')->format('M Y'),
                    'total_purchases' => $item->total_purchases,
                    'suppliers_count' => $item->suppliers_count,
                    'total_amount' => $item->total_amount,
                    'total_quantity' => $item->total_quantity,
                    'avg_purchase_value' => $item->total_purchases > 0 
                        ? $item->total_amount / $item->total_purchases 
                        : 0
                ];
            });

        return response()->json($trend);
    }

    // Price Trend Analysis
    public function getPriceTrend(Request $request)
    {
        $dates = $this->getDateRange($request);
        $productName = $request->get('product_name');

        if (!$productName) {
            return response()->json(['error' => 'Product name required'], 400);
        }

        $trend = Purchase::where('product_name', $productName)
            ->whereBetween('purchase_date', [$dates['start'], $dates['end']])
            ->select('purchase_date', 'purchased_price', 'quantity', 'supplier_id')
            ->with('supplier:id,name')
            ->orderBy('purchase_date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => Carbon::parse($item->purchase_date)->format('d-M-Y'),
                    'price' => $item->purchased_price,
                    'quantity' => $item->quantity,
                    'supplier' => $item->supplier->name ?? 'N/A'
                ];
            });

        return response()->json($trend);
    }

    // Get all suppliers for filter dropdown
    public function getSuppliers()
    {
        $suppliers = Supplier::select('id', 'name', 'company')
            ->orderBy('name')
            ->get()
            ->map(function($supplier) {
                return [
                    'id' => $supplier->id,
                    'label' => $supplier->name . ($supplier->company ? ' (' . $supplier->company . ')' : '')
                ];
            });

        return response()->json($suppliers);
    }

    // Get all categories for filter dropdown
    public function getCategories()
    {
        $categories = Category::where('status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    // Helper function to get date range
    private function getDateRange(Request $request)
    {
        $dateRange = $request->get('date_range', 'this_month');
        
        switch ($dateRange) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today();
                break;
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = Carbon::yesterday();
                break;
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $start = Carbon::now()->subWeek()->startOfWeek();
                $end = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_quarter':
                $start = Carbon::now()->firstOfQuarter();
                $end = Carbon::now()->lastOfQuarter();
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $start = Carbon::parse($request->get('start_date', Carbon::now()->startOfMonth()));
                $end = Carbon::parse($request->get('end_date', Carbon::now()->endOfMonth()));
                break;
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
        }

        return ['start' => $start, 'end' => $end];
    }
}