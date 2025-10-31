<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Category;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class PurchaseController extends Controller
{
    public function index()
    {
        return view('pages.purchase.purchase');
    }

    public function getData(Request $request)
{
    $page = $request->get('page', 1);
    
    $purchases = Purchase::with(['supplier', 'category'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);
    
    // Return JSON for AJAX
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $purchases->items(),
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'first_item' => $purchases->firstItem(),
                'last_item' => $purchases->lastItem(),
                'has_more_pages' => $purchases->hasMorePages(),
                'on_first_page' => $purchases->onFirstPage(),
            ]
        ]);
    }
    
    return view('pages.purchase.purchase', compact('purchases'));
}

    public function create()
    {
        $activeCategories = Category::where('status', true)->get();
        return view('pages.purchase.add', compact('activeCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'   => 'required|string|max:255',
            'purchased_price'=> 'required|numeric',
            'sold_price'     => 'nullable|numeric',
            'category_id'    => 'nullable|exists:categories,id',
            'quantity'       => 'required|integer',
            'unit'           => 'required',
            'purchase_date'  => 'required|date',
            'supplier_name'  => 'nullable|string|max:255',
            'company'        => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'contact_info'   => 'nullable|string|max:255',
        ]);

        DB::beginTransaction(); // ✅ Start transaction

        try {
            $supplier = Supplier::firstOrCreate([
                'name'         => $validated['supplier_name'],
                'company'      => $validated['company'],
                'address'      => $validated['address'],
                'contact_info' => $validated['contact_info'],
            ]);

            Purchase::create([
                'product_name'  => $validated['product_name'],
                'purchased_price'=> $validated['purchased_price'],
                'sold_price'         => $validated['sold_price'] ?? 0,
                'quantity'      => $validated['quantity'],
                'unit'          => $validated['unit'],
                'purchase_date' => $validated['purchase_date'],
                'supplier_id'   => $supplier->id,
                'category_id' => $validated['category_id'] ?? Category::where('name', 'Misc')->value('id'),
            ]);

            DB::commit(); // ✅ Commit everything

            return redirect()->back()->with('success', 'Purchase added successfully!');
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ Undo all changes if error
            return redirect()->back()->with('error', 'Something went wrong. Please try again!');
        }
    }

    public function show($id)
{
    $purchase = Purchase::with(['supplier', 'category'])->findOrFail($id);
    
    $salesHistory = DB::table('sale_items')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->leftJoin(DB::raw('(
            SELECT sale_item_id, 
                   SUM(quantity_returned) as total_returned
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->leftJoin(DB::raw('(
            SELECT sale_id, 
                   SUM(amount) as total_paid
            FROM payments 
            WHERE deleted_at IS NULL
            GROUP BY sale_id
        ) as payments'), 'sales.id', '=', 'payments.sale_id')
        ->where('sale_items.purchase_id', $id)
        ->select(
            'sales.payment_type',
            'sales.grand_total as sale_grand_total',
            'sales.received_amount',
            'sales.subtotal as sale_subtotal',
            'sales.discount_amount as sale_discount_amount',
            'sales.tax_amount as sale_tax_amount',
            'sale_items.quantity as original_quantity',
            'sale_items.total_after_discount as item_total_after_discount',
            DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            DB::raw('COALESCE(payments.total_paid, 0) as paid_amount')
        )
        ->get();
    
    $totalRevenue = 0;
    
    foreach ($salesHistory as $sale) {
        $remainingQty = $sale->remaining_quantity;
        
        if ($remainingQty <= 0) continue;
        
        $itemTotalAfterItemDiscount = ($sale->item_total_after_discount / $sale->original_quantity) * $remainingQty;
        
        $saleDiscountShare = 0;
        if ($sale->sale_discount_amount > 0 && $sale->sale_subtotal > 0) {
            $itemProportion = $itemTotalAfterItemDiscount / $sale->sale_subtotal;
            $saleDiscountShare = $sale->sale_discount_amount * $itemProportion;
        }
        
        $totalAfterBothDiscounts = $itemTotalAfterItemDiscount - $saleDiscountShare;
        
        $taxShare = 0;
        if ($sale->sale_tax_amount > 0 && $sale->sale_subtotal > 0) {
            $subtotalAfterSaleDiscount = $sale->sale_subtotal - $sale->sale_discount_amount;
            if ($subtotalAfterSaleDiscount > 0) {
                $itemProportionForTax = $totalAfterBothDiscounts / $subtotalAfterSaleDiscount;
                $taxShare = $sale->sale_tax_amount * $itemProportionForTax;
            }
        }
        
        $finalTotal = $totalAfterBothDiscounts + $taxShare;
        
        $paymentRatio = 1;
        
        if ($sale->payment_type === 'credit') {
            $totalPaid = $sale->received_amount + $sale->paid_amount;
            
            if ($sale->sale_grand_total > 0) {
                $paymentRatio = min($totalPaid / $sale->sale_grand_total, 1);
            } else {
                $paymentRatio = 0;
            }
        }
        
        $finalRevenue = $finalTotal * $paymentRatio;
        if ($finalRevenue > 0) {
            $totalRevenue += $finalRevenue;
        }
    }
    
    // 🎯 Calculate total PAID quantity and cost
    $totalPaidQuantity = 0;
    $totalCost = 0;
    
    foreach ($salesHistory as $sale) {
        $remainingQty = $sale->remaining_quantity;
        
        if ($remainingQty <= 0) continue;
        
        $paymentRatio = 1;
        
        if ($sale->payment_type === 'credit') {
            $totalPaid = $sale->received_amount + $sale->paid_amount;
            
            if ($sale->sale_grand_total > 0) {
                $paymentRatio = min($totalPaid / $sale->sale_grand_total, 1);
            } else {
                $paymentRatio = 0;
            }
        }
        
        $paidQty = $remainingQty * $paymentRatio;
        $totalPaidQuantity += $paidQty;
        $totalCost += ($paidQty * $purchase->purchased_price);
    }
    
    $purchase->total_revenue = round($totalRevenue, 2);
    $purchase->total_cost = round($totalCost, 2);
    $purchase->paid_quantity = round($totalPaidQuantity, 2);
     // Handle AJAX request
     if (request()->ajax() || request()->wantsJson()) {
        return response()->json([
            'success' => true,
            'purchase' => $purchase
        ]);
    }
    return view('pages.purchase.see_detail', compact('purchase'));
}

    public function edit($id)
    {
        $purchase = Purchase::with(['supplier', 'category'])->findOrFail($id);
        $activeCategories = Category::where('status', true)->get();
        // Handle AJAX request
    if (request()->ajax() || request()->wantsJson()) {
        return response()->json([
            'success' => true,
            'purchase' => $purchase,
            'activeCategories' => $activeCategories
        ]);
    }
        return view('pages.purchase.update', compact('purchase', 'activeCategories'));
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'product_name'   => 'required|string|max:255',
        'purchased_price'=> 'required|numeric',
        'sold_price'          => 'nullable|numeric',
        'category_id'    => 'nullable|integer|exists:categories,id',
        'quantity'       => 'required|numeric',
        'unit'           => 'required',
        'purchase_date'  => 'required|date',
        'supplier_name'  => 'nullable|string|max:255',
        'company'        => 'nullable|string|max:255',
        'address'        => 'nullable|string|max:255',
        'contact_info'   => 'nullable|string|max:255',
    ]);
try{

    $purchase = Purchase::findOrFail($id);

    // Update supplier info if exists
    if ($purchase->supplier) {
        $purchase->supplier->update([
            'name'         => $validated['supplier_name'],
            'company'      => $validated['company'],
            'address'      => $validated['address'],
            'contact_info' => $validated['contact_info'],
        ]);
    }

    // Store the old product name before updating
    $oldName = $purchase->product_name;

    // Calculate total stock based on old product name (before renaming)
    $totalStock = Purchase::where('product_name', $oldName);

    // Update **all rows** with old product name to the new data
    Purchase::where('product_name', $oldName)
        ->update([
            'product_name'  => $validated['product_name'],
            'purchased_price'=> $validated['purchased_price'],
            'sold_price'         => $validated['sold_price'],
            'category_id'   => $validated['category_id'],
            'quantity'      => $validated['quantity'],
            'unit'          => $validated['unit'],
            'purchase_date' => $validated['purchase_date'],
        ]);
    return redirect()->route('purchase.index')->with('success', 'Product updated successfully.');
}catch(\Exception $e){
    return redirect()->route('purchase.index')->with('error', 'Something went wrong. Please try again!');
}
}



public function destroy($id)
{
    try {
        $purchase = Purchase::findOrFail($id);
        $supplier = $purchase->supplier;
        $category = $purchase->category;

        $purchase->delete();

        if ($supplier && $supplier->purchases()->count() === 0) {
            $supplier->delete();
        }

        if ($category && $category->purchases()->count() === 0) {
            $category->delete();
        }

        // Return JSON if AJAX request
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted successfully!'
            ]);
        }

        return redirect()->route('purchase.index')->with('success', 'Purchase deleted successfully!');
    } catch (\Exception $e) {
        if (request()->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again!'
            ], 500);
        }
        
        return redirect()->route('purchase.index')->with('error', 'Something went wrong. Please try again!');
    }
}

public function lowInventory(Request $request)
{
    // Return the initial view
    return view('pages.purchase.lowInventory');
}

    public function getLowInventoryData(Request $request)
{
    $page = $request->get('page', 1);
    $purchases = Purchase::with('supplier', 'category')
        ->select('id', 'product_name', 'quantity', 'sold_quantity', 'supplier_id', 'category_id', 'created_at','purchase_date')
        ->selectRaw('(quantity - sold_quantity) as remaining')
        ->whereRaw('(quantity - sold_quantity) <= 5') // Filter for low inventory
        ->orderByRaw('(quantity - sold_quantity) ASC') // Always sort by remaining first
        ->orderBy('created_at', 'desc') // Secondary sort by latest
        ->paginate(10);

        if($request->ajax() || $request->wantsJson()){
            return response()->json([
                'success' => true,
                'data' => $purchases->items(),
                'pagination' => [
                    'current_page' => $purchases->currentPage(),
                    'last_page' => $purchases->lastPage(),
                    'per_page' => $purchases->perPage(),
                    'total' => $purchases->total(),
                    'first_item' => $purchases->firstItem(),
                    'last_item' => $purchases->lastItem(),
                    'has_more_pages' => $purchases->hasMorePages(),
                    'on_first_page' => $purchases->onFirstPage(),
                ]
            ]);
        }
}

    

    

public function restock(Request $request)
{
    $request->validate([
        'purchase_id' => 'required|exists:purchases,id',
        'quantity' => 'required|integer|min:1',
        'purchase_date' => 'required|date'
    ]);

    try {
        $purchase = Purchase::findOrFail($request->purchase_id);
        $purchase->quantity += $request->quantity;
        $purchase->purchase_date = $request->purchase_date; // Update date
        $purchase->save();

        return redirect()->back()->with('success', 'Stock updated successfully!');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Something went wrong. Please try again!');
    }
}
public function search(Request $request)
{
    $searchType = $request->input('search_type');
    $searchValue = $request->input('search_value');
    $quantityFrom = $request->input('quantity_from');
    $quantityTo = $request->input('quantity_to');

    // ✅ Eager load both relationships to prevent N+1 queries
    $query = Purchase::with(['category', 'supplier'])
        ->select('purchases.*')
        ->orderBy('purchase_date', 'desc');

    if ($searchType === 'product' && $searchValue) {
        // ✅ Use prefix search for better index usage
        $query->where('product_name', 'LIKE', $searchValue . '%');
        
    } elseif ($searchType === 'category' && $searchValue) {
        // ✅ Optimized category search
        $query->whereHas('category', function($q) use ($searchValue) {
            $q->where('name', 'LIKE', $searchValue . '%');
        });
        
    } elseif ($searchType === 'unit' && $searchValue) {
        $query->where('unit', 'LIKE', $searchValue . '%');
        
    } elseif ($searchType === 'quantity' && ($quantityFrom || $quantityTo)) {
        // ✅ Better range handling with sensible defaults
        $from = $quantityFrom ?? 0;
        $to = $quantityTo ?? 999999; // More reasonable max value
        
        // ✅ Use individual column comparisons (better for optimizer)
        $query->whereRaw('(quantity - sold_quantity) BETWEEN ? AND ?', [$from, $to]);
    }

    // ✅ Keep pagination at 15 and append search params
    $purchases = $query->paginate(10)->appends($request->all());

    return view('pages.purchase.purchase', compact('purchases'));
}
}
