<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->input('query'));
    
        $products = Purchase::query();
    
        // Split the query into tokens by space
        $tokens = preg_split('/\s+/', $query);
        $nameSearch = [];
        $minPrice = null;
        $maxPrice = null;
        $hasPriceOperator = false; // Track if we found any price operators
    
        foreach ($tokens as $token) {
            if (preg_match('/^>=\s*(\d+)$/', $token, $matches)) {
                $minPrice = $matches[1];
                $hasPriceOperator = true;
            } elseif (preg_match('/^<=\s*(\d+)$/', $token, $matches)) {
                $maxPrice = $matches[1];
                $hasPriceOperator = true;
            } elseif (preg_match('/^>\s*(\d+)$/', $token, $matches)) {
                $minPrice = $matches[1] + 0.01; // greater than
                $hasPriceOperator = true;
            } elseif (preg_match('/^<\s*(\d+)$/', $token, $matches)) {
                $maxPrice = $matches[1] - 0.01; // less than
                $hasPriceOperator = true;
            } elseif (preg_match('/^(\d+)-(\d+)$/', $token, $matches)) {
                $minPrice = $matches[1];
                $maxPrice = $matches[2];
                $hasPriceOperator = true;
            } elseif (is_numeric($token) && $hasPriceOperator) {
                // Only treat as exact price if we already found price operators
                $minPrice = $maxPrice = $token;
            } else {
                // Treat as name part (including standalone numbers like "6")
                $nameSearch[] = $token;
            }
        }
    
        // Apply price filters only if we found price operators
        if ($hasPriceOperator) {
            if ($minPrice !== null && $maxPrice !== null) {
                $products->whereBetween('sold_price', [$minPrice, $maxPrice]);
            } elseif ($minPrice !== null) {
                $products->where('sold_price', '>=', $minPrice);
            } elseif ($maxPrice !== null) {
                $products->where('sold_price', '<=', $maxPrice);
            }
        }
    
        // Apply name filters if any
        if (!empty($nameSearch)) {
            foreach ($nameSearch as $word) {
                $products->where('product_name', 'LIKE', "%{$word}%");
            }
        }
    
        // Only show products with available stock
        $products->whereRaw('quantity > sold_quantity');
    
        // Limit to 10 records for performance
        $results = $products->limit(10)->get([
            'id',
            'product_name',
            'sold_price',
            'unit',
            'quantity',
            'sold_quantity'
        ]);
    
        return response()->json($results->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->product_name,
                'price' => $product->sold_price,
                'unit' => $product->unit,
                'available_stock' => $product->quantity - $product->sold_quantity
            ];
        }));
    }    
 

    /**
     * Get product sales history for POS
     */
    public function getProductHistory($purchaseId)
    {
        try {
            $purchase = Purchase::with(['supplier', 'category'])->findOrFail($purchaseId);
            
            // Get sales history with customer details
            $salesHistory = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoin('sale_return_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
                ->where('sale_items.purchase_id', $purchaseId)
                ->select(
                    'sales.voucher_no',
                    'sales.created_at as sale_date',
                    'sales.payment_type',
                    'customers.name as customer_name',
                    'customers.customer_type',
                    'customers.contact',
                    'sale_items.quantity',
                    'sale_items.price',
                    'sale_items.discount_type',
                    'sale_items.discount_value',
                    'sale_items.discount_amount',
                    'sale_items.total_after_discount',
                    DB::raw('COALESCE(sale_return_items.quantity_returned, 0) as returned_qty')
                )
                ->orderBy('sales.created_at', 'desc')
                ->limit(15) // Last 15 transactions for performance
                ->get();
            
            // Calculate summary
            $summary = [
                'product_name' => $purchase->product_name,
                'unit' => $purchase->unit,
                'purchase_price' => $purchase->purchased_price,
                'selling_price' => $purchase->sold_price,
                'total_purchased' => $purchase->quantity,
                'total_sold' => $purchase->sold_quantity,
                'available_stock' => $purchase->quantity - $purchase->sold_quantity,
                'total_transactions' => $salesHistory->count(),
                'total_revenue' => $salesHistory->sum('total_after_discount'),
                'supplier' => $purchase->supplier->name ?? 'N/A',
                'category' => $purchase->category->name ?? 'N/A'
            ];
            
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'history' => $salesHistory
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading product history'
            ], 500);
        }
    }
}