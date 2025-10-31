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
 * Get product sales history for POS with proper discount, tax, return, and credit payment handling
 */
public function getProductHistory($purchaseId)
{
    try {
        $purchase = Purchase::with(['supplier', 'category'])->findOrFail($purchaseId);
        
        // 🎯 Get sales history with ALL necessary fields INCLUDING payment data
        $salesHistory = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin(DB::raw('(
                SELECT sale_item_id, 
                       SUM(quantity_returned) as total_returned,
                       SUM(amount_refunded) as total_refunded
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
            ->where('sale_items.purchase_id', $purchaseId)
            ->select(
                'sale_items.id as sale_item_id',
                'sales.id as sale_id',
                'sales.voucher_no',
                'sales.created_at as sale_date',
                'sales.payment_type',
                'sales.subtotal as sale_subtotal',
                'sales.discount_type as sale_discount_type',
                'sales.discount as sale_discount_value',
                'sales.discount_amount as sale_discount_amount',
                'sales.tax_type as sale_tax_type',
                'sales.tax as sale_tax',
                'sales.tax_amount as sale_tax_amount',
                'sales.grand_total as sale_grand_total',
                'sales.received_amount',
                'customers.name as customer_name',
                'customers.customer_type',
                'customers.contact',
                'sale_items.quantity as original_quantity',
                'sale_items.price',
                'sale_items.discount_type',
                'sale_items.discount_value',
                'sale_items.discount_amount as item_discount_amount',
                'sale_items.total as item_total_before_discount',
                'sale_items.total_after_discount as item_total_after_discount',
                DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_qty'),
                DB::raw('COALESCE(returned_qty.total_refunded, 0) as returned_amount'),
                DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
                DB::raw('COALESCE(payments.total_paid, 0) as paid_amount')
            )
            ->orderBy('sales.created_at', 'desc')
            ->limit(15)
            ->get();
        
        // 🎯 Process each sale to calculate ACTUAL amounts with all discounts, tax, and payment ratio
        $processedHistory = $salesHistory->map(function($sale) use ($purchase) {
            $remainingQty = $sale->remaining_quantity;
            
            if ($remainingQty <= 0) {
                // Fully returned
                return (object) array_merge((array) $sale, [
                    'quantity' => $sale->original_quantity,
                    'final_total' => 0,
                    'final_revenue' => 0,
                    'profit' => 0,
                    'payment_ratio' => 0
                ]);
            }
            
            // Step 1: Calculate item total after item-level discount (proportional to remaining qty)
            $itemTotalAfterItemDiscount = ($sale->item_total_after_discount / $sale->original_quantity) * $remainingQty;
            
            // Step 2: Calculate this item's share of sale-level discount
            $saleDiscountShare = 0;
            if ($sale->sale_discount_amount > 0 && $sale->sale_subtotal > 0) {
                $itemProportion = $itemTotalAfterItemDiscount / $sale->sale_subtotal;
                $saleDiscountShare = $sale->sale_discount_amount * $itemProportion;
            }
            
            $totalAfterBothDiscounts = $itemTotalAfterItemDiscount - $saleDiscountShare;
            
            // Step 3: Calculate this item's share of tax
            $taxShare = 0;
            if ($sale->sale_tax_amount > 0 && $sale->sale_subtotal > 0) {
                $subtotalAfterSaleDiscount = $sale->sale_subtotal - $sale->sale_discount_amount;
                if ($subtotalAfterSaleDiscount > 0) {
                    $itemProportionForTax = $totalAfterBothDiscounts / $subtotalAfterSaleDiscount;
                    $taxShare = $sale->sale_tax_amount * $itemProportionForTax;
                }
            }
            
            // Step 4: Final total including tax
            $finalTotal = $totalAfterBothDiscounts + $taxShare;
            
            // 🎯 Step 5: Calculate payment ratio for credit sales
            $paymentRatio = 1; // Default: fully paid (cash/card sales)
            
            if ($sale->payment_type === 'credit') {
                // For credit sales, calculate how much has been paid
                $totalPaid = $sale->received_amount + $sale->paid_amount;
                
                if ($sale->sale_grand_total > 0) {
                    $paymentRatio = min($totalPaid / $sale->sale_grand_total, 1);
                } else {
                    $paymentRatio = 0;
                }
            }
            
            // 🎯 Step 6: Apply payment ratio to revenue
            $finalRevenue = $finalTotal * $paymentRatio;
            
// Step 7: Calculate profit (final revenue - proportional cost)
// 🎯 FIX: Cost should also be proportional to payment ratio
$paidQuantity = $remainingQty * $paymentRatio;
$costPrice = $purchase->purchased_price * $paidQuantity;

$profit = $finalRevenue - $costPrice;
            
            return (object) array_merge((array) $sale, [
                'quantity' => $sale->original_quantity,
                'remaining_quantity' => $remainingQty,
                'item_total_after_item_discount' => round($itemTotalAfterItemDiscount, 2),
                'sale_discount_share' => round($saleDiscountShare, 2),
                'tax_share' => round($taxShare, 2),
                'final_total' => round($finalTotal, 2),
                'payment_ratio' => round($paymentRatio, 4),
                'final_revenue' => round($finalRevenue, 2),
                'profit' => round($profit, 2)
            ]);
        });
        
        // 🎯 Filter out fully returned items (we keep partially/fully paid credit sales)
        $activeHistory = $processedHistory->filter(function($sale) {
            // Only include sales that have remaining quantity
            return $sale->remaining_quantity > 0;
        });
        
        // 🎯 Calculate summary metrics
        $totalRevenue = $activeHistory->sum('final_revenue');
        $totalProfit = $activeHistory->sum('profit');
        $totalTransactions = $activeHistory->count();
        $totalSoldQty = $activeHistory->sum('remaining_quantity');
        
        $summary = [
            'product_name' => $purchase->product_name,
            'unit' => $purchase->unit,
            'purchase_price' => $purchase->purchased_price,
            'selling_price' => $purchase->sold_price,
            'total_purchased' => $purchase->quantity,
            'total_sold' => $totalSoldQty,
            'available_stock' => $purchase->quantity - $purchase->sold_quantity,
            'total_transactions' => $totalTransactions,
            'total_revenue' => round($totalRevenue, 2),
            'total_profit' => round($totalProfit, 2),
            'supplier' => $purchase->supplier->name ?? 'N/A',
            'category' => $purchase->category->name ?? 'N/A'
        ];
        
        return response()->json([
            'success' => true,
            'summary' => $summary,
            'history' => $processedHistory->values()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Product history error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error loading product history: ' . $e->getMessage()
        ], 500);
    }
}
}