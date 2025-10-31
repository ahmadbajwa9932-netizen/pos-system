<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Sale_item;
use App\Models\Purchase;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /**
     * Calculate the correct refund amount for an item considering sale-level discounts
     */
    private function calculateItemRefund($saleItem, $qtyToReturn, $sale)
    {
        // Guard against zero quantities
        if ($qtyToReturn <= 0 || $saleItem->quantity <= 0) {
            return ['unit_net' => 0, 'refund_amount' => 0];
        }
        
        // Step 1: Calculate unit price after item-level discount
        $unitAfterItemDiscount = $saleItem->total_after_discount / $saleItem->quantity;
        
        // Step 2: Apply sale-level discount proportionally
        // Use grand total to subtotal ratio to get what customer actually paid per subtotal unit
        $actualPaymentRatio = ($sale->subtotal > 0 && $sale->grand_total > 0) 
            ? ($sale->grand_total / $sale->subtotal)
            : 1;
        
        // Step 3: Calculate final unit net (what customer actually paid per unit)
        $unitNetAfterAllDiscounts = round($unitAfterItemDiscount * $actualPaymentRatio, 4);
        
        // Step 4: Calculate total refund for the quantity being returned
        $refundAmount = round($unitNetAfterAllDiscounts * $qtyToReturn, 2);
        
        return [
            'unit_net' => $unitNetAfterAllDiscounts,
            'refund_amount' => $refundAmount
        ];
    }

    /**
     * Display all returned sales
     */
    public function index()
    {
        // Group returns by sale_id and get aggregated data
        $returnedSales = DB::table('sale_returns')
            ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select([
                'sales.id as sale_id',
                'sales.voucher_no',
                'sales.created_at as sale_date',
                'sales.grand_total as original_amount',
                'customers.name as customer_name',
                DB::raw('COUNT(sale_returns.id) as return_count'),
                DB::raw('SUM(sale_returns.total_return_amount) as total_refunded'),
                DB::raw('SUM((SELECT COUNT(*) FROM sale_return_items WHERE sale_return_items.sale_return_id = sale_returns.id)) as total_items_returned'),
                DB::raw('MAX(sale_returns.created_at) as last_return_date'),
                DB::raw('GROUP_CONCAT(sale_returns.status) as return_statuses')
            ])
            ->groupBy([
                'sales.id', 
                'sales.voucher_no', 
                'sales.created_at',
                'sales.grand_total',
                'customers.name'
            ])
            ->orderBy('last_return_date', 'desc')
            ->paginate(10);
    
        // Get detailed return data for expandable rows
        $returnDetails = collect();
        foreach ($returnedSales as $sale) {
            $details = SaleReturn::with(['items.purchase'])
                ->where('sale_id', $sale->sale_id)
                ->get();
            $returnDetails[$sale->sale_id] = $details;
        }
    
        return view('pages.sales.returnedSale', compact('returnedSales', 'returnDetails'));
    }

    /**
     * Show the return form for a specific sale (legacy create view)
     */
    public function create($saleId)
    {
        $sale = Sale::with(['customer', 'saleItems.purchase'])->findOrFail($saleId);

        // Calculate already returned qty for each item
        $alreadyReturned = SaleReturnItem::whereIn('sale_item_id', $sale->saleItems->pluck('id'))
            ->select('sale_item_id', DB::raw('SUM(quantity_returned) as qty'))
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        return view('pages.sales.returns.create', compact('sale', 'alreadyReturned'));
    }
/**
 * Show partial return page (new full-screen view instead of modal)
 */
public function showPartialReturn($saleId)
{
    // Get sale with customer and totals
    $sale = Sale::with(['customer'])->findOrFail($saleId);

    // ✅ FIXED: Use same calculation as working SaleReturnController
    // Calculate the actual payment ratio (what customer actually paid per subtotal unit)
    $actualPaymentRatio = ($sale->subtotal > 0 && $sale->grand_total > 0) 
        ? ($sale->grand_total / $sale->subtotal)
        : 1;

    // Get sale items with remaining quantities
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->where('sale_items.sale_id', $saleId)
        ->select(
            'sale_items.id',
            'sale_items.sale_id',
            'sale_items.purchase_id',
            'sale_items.quantity as original_quantity',
            'sale_items.price',
            'sale_items.total_after_discount',
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount',
            'purchases.product_name',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity')
        )
        ->having('remaining_quantity', '>', 0) // Only items that can still be returned
        ->get()
        ->map(function ($item) use ($actualPaymentRatio) {
            // Create an object that mimics the Sale_item model structure
            $saleItem = new \stdClass();
            $saleItem->id = $item->id;
            $saleItem->sale_id = $item->sale_id;
            $saleItem->purchase_id = $item->purchase_id;
            $saleItem->quantity = $item->remaining_quantity;
            $saleItem->original_quantity = $item->original_quantity;
            $saleItem->returned_quantity = $item->returned_quantity;
            $saleItem->price = $item->price;

            // ✅ FIXED: Proper calculations matching SaleReturnController
            // Step 1: Calculate unit price after item-level discount
            $unitAfterItemDiscount = ($item->original_quantity > 0) 
                ? ($item->total_after_discount / $item->original_quantity)
                : 0;
            
            // Step 2: Calculate unit item discount amount
            $unitItemDiscount = ($item->original_quantity > 0)
                ? ($item->discount_amount / $item->original_quantity)
                : 0;

            // Step 3: Calculate final unit net (what customer actually paid per unit)
            $unitNetAfterAllDiscounts = round($unitAfterItemDiscount * $actualPaymentRatio, 4);

            // For remaining quantities
            $itemLevelNet = $item->remaining_quantity * $unitAfterItemDiscount;
            $finalTotalWithAllDiscounts = $item->remaining_quantity * $unitNetAfterAllDiscounts;

            $saleItem->total_after_discount = $itemLevelNet;
            $saleItem->final_refund_total = $finalTotalWithAllDiscounts;
            $saleItem->unit_after_item_discount = $unitAfterItemDiscount;
            $saleItem->unit_item_discount = $unitItemDiscount;
            $saleItem->unit_net_after_all_discounts = $unitNetAfterAllDiscounts;

            $saleItem->discount_type = $item->discount_type;
            $saleItem->discount_value = $item->discount_value;
            $saleItem->discount_amount = $item->discount_amount;

            // Create purchase object
            $saleItem->purchase = new \stdClass();
            $saleItem->purchase->product_name = $item->product_name;

            return $saleItem;
        });

    // Calculate already returned quantities for validation (legacy compatibility)
    $alreadyReturned = $saleItems->pluck('returned_quantity', 'id');

    return view('pages.sales.partial_return', [
        'sale' => $sale,
        'saleItems' => $saleItems,
        'alreadyReturned' => $alreadyReturned,
        'actualPaymentRatio' => $actualPaymentRatio // ✅ Pass this to view for JS calculations
    ]);
}


    /**
     * Process full return - returns all remaining items in the sale
     */
    public function processFullReturn(Request $request, $saleId)
    {
        DB::transaction(function () use ($saleId) {
            $sale = Sale::with('saleItems.purchase')->findOrFail($saleId);

            // Get items that still have returnable quantities
            $returnableItems = collect();
            $totalRefund = 0;

            foreach ($sale->saleItems as $saleItem) {
                // Check how much is already returned for this item
                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->sum('quantity_returned');
                
                $remainingQty = $saleItem->quantity - $alreadyReturned;
                
                // Only include items that have remaining quantity to return
                if ($remainingQty > 0) {
                    $returnableItems->push([
                        'saleItem' => $saleItem,
                        'remainingQty' => $remainingQty
                    ]);
                }
            }

            // Check if there are any items that can be returned
            if ($returnableItems->isEmpty()) {
                throw new \Exception('All items from this sale have already been fully returned.');
            }

            // Create return record
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'total_return_amount' => 0,
                'status' => 'completed',
                'notes' => 'Full return of remaining items processed',
            ]);

            // Process each returnable item with correct calculations
            foreach ($returnableItems as $item) {
                $saleItem = $item['saleItem'];
                $qtyToReturn = $item['remainingQty'];
                
                // ✅ Use the corrected calculation method
                $refundData = $this->calculateItemRefund($saleItem, $qtyToReturn, $sale);
                
                $totalRefund += $refundData['refund_amount'];

                // Create return item
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'purchase_id' => $saleItem->purchase_id,
                    'quantity_returned' => $qtyToReturn,
                    'unit_net_after_discount' => $refundData['unit_net'],
                    'amount_refunded' => $refundData['refund_amount'],
                ]);

                // Update stock: reduce sold quantity on purchase
                if ($saleItem->purchase) {
                    $purchase = $saleItem->purchase;
                    $newSold = max(0, (int)$purchase->sold_quantity - $qtyToReturn);
                    $purchase->update(['sold_quantity' => $newSold]);
                }
            }

            // Update total return amount
            $saleReturn->update(['total_return_amount' => $totalRefund]);
        });

        return redirect()->route('sales.general')->with('success', 'Full return of remaining items processed successfully.');
    }

    /**
     * Process partial return - returns selected items with specified quantities
     */
    public function processPartialReturn(Request $request, $saleId)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $saleId) {
            $sale = Sale::with('saleItems.purchase')->findOrFail($saleId);

            // Check if there are items to return
            $hasItemsToReturn = false;
            foreach ($request->items as $qtyToReturn) {
                if ((int)$qtyToReturn > 0) {
                    $hasItemsToReturn = true;
                    break;
                }
            }

            if (!$hasItemsToReturn) {
                throw new \Exception('Please select at least one item to return.');
            }

            // Create return record
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'total_return_amount' => 0,
                'status' => 'completed',
                'notes' => $request->notes ?? 'Partial return processed',
            ]);

            $totalRefund = 0;

            foreach ($request->items as $saleItemId => $qtyToReturn) {
                $qtyToReturn = (int)$qtyToReturn;
                if ($qtyToReturn <= 0) {
                    continue;
                }

                /** @var Sale_item $saleItem */
                $saleItem = Sale_item::with('purchase')->findOrFail($saleItemId);

                // Ensure item belongs to this sale
                if ((int)$saleItem->sale_id !== (int)$sale->id) {
                    throw new \Exception('Invalid item: does not belong to this sale.');
                }

                // Check how much is already returned
                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)->sum('quantity_returned');
                $maxReturnable = $saleItem->quantity - $alreadyReturned;

                if ($maxReturnable <= 0) {
                    throw new \Exception('Item already fully returned.');
                }
                if ($qtyToReturn > $maxReturnable) {
                    throw new \Exception("Return quantity exceeds remaining returnable quantity (max {$maxReturnable}).");
                }

                // ✅ Use the corrected calculation method
                $refundData = $this->calculateItemRefund($saleItem, $qtyToReturn, $sale);
                
                $totalRefund += $refundData['refund_amount'];

                // Save return item details
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'purchase_id' => $saleItem->purchase_id,
                    'quantity_returned' => $qtyToReturn,
                    'unit_net_after_discount' => $refundData['unit_net'],
                    'amount_refunded' => $refundData['refund_amount'],
                ]);

                // Update stock: reduce sold quantity on purchase
                if ($saleItem->purchase) {
                    $purchase = $saleItem->purchase;
                    $newSold = max(0, (int)$purchase->sold_quantity - $qtyToReturn);
                    $purchase->update(['sold_quantity' => $newSold]);
                }
            }

            // Update total return amount
            $saleReturn->update(['total_return_amount' => $totalRefund]);
        });

        return redirect()->route('sales.general')->with('success', 'Partial return processed successfully.');
    }

    /**
     * Process and store a return for a sale (legacy method - keeping for compatibility)
     */
    public function store(Request $request, $saleId)
    {
        return $this->processPartialReturn($request, $saleId);
    }

    /**
     * Show detailed return information for a specific sale
     */
    public function showDetails($saleId)
    {
        // Get the original sale with customer
        $sale = Sale::with(['customer'])->findOrFail($saleId);
        
        // Get all returns for this sale
        $returns = SaleReturn::with(['items.purchase'])
            ->where('sale_id', $saleId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Check if sale has any returns
        if ($returns->isEmpty()) {
            abort(404, 'No returns found for this sale.');
        }
        
        // Calculate totals
        $totalReturnedAmount = $returns->sum('total_return_amount');
        $totalReturnTransactions = $returns->count();
        $totalItemsReturned = $returns->sum(function ($return) {
            return $return->items->sum('quantity_returned');
        });
        
        // Get original sale items with return information
        $originalSaleItems = DB::table('sale_items')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->leftJoin(DB::raw('(
                SELECT sale_item_id, SUM(quantity_returned) as total_returned 
                FROM sale_return_items 
                GROUP BY sale_item_id
            ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
            ->where('sale_items.sale_id', $saleId)
            ->select(
                'sale_items.id',
                'sale_items.quantity as original_quantity',
                'sale_items.price',
                'sale_items.total_after_discount as original_total',
                'sale_items.discount_type',
                'sale_items.discount_value',
                'sale_items.discount_amount',
                'purchases.product_name',
                DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
                DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
                DB::raw('
                    CASE 
                        WHEN sale_items.quantity > 0 
                        THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                        ELSE 0 
                    END as remaining_total
                ')
            )
            ->get();
        
        // ✅ Correct financial impact calculations
        $adjustedGrandTotal = $sale->grand_total - $totalReturnedAmount;
        $returnPercentage = $sale->grand_total > 0 ? ($totalReturnedAmount / $sale->grand_total) : 0;
        
        // Calculate adjusted values based on the proportion of grand total returned
        $adjustedSubtotal = $sale->subtotal * (1 - $returnPercentage);
        $adjustedDiscountAmount = $sale->discount_amount * (1 - $returnPercentage);
// ✅ Calculate adjusted tax based on tax type
if ($sale->tax_type === 'percentage') {
    // For percentage tax, recalculate on adjusted subtotal
    $adjustedTax = ($adjustedSubtotal * ($sale->tax ?? 0)) / 100;
} else {
    // For amount tax, reduce proportionally
    $adjustedTax = ($sale->tax_amount ?? $sale->tax ?? 0) * (1 - $returnPercentage);
}        
        // Add calculated fields to sale object
        $sale->has_returns = true;
        $sale->total_returned_amount = $totalReturnedAmount;
        $sale->adjusted_subtotal = max(0, $adjustedSubtotal);
        $sale->adjusted_discount_amount = max(0, $adjustedDiscountAmount);
        $sale->adjusted_tax = max(0, $adjustedTax);
        $sale->adjusted_grand_total = max(0, $adjustedGrandTotal);
        $sale->original_sale_items = $originalSaleItems;
        $sale->total_return_transactions = $totalReturnTransactions;
        $sale->total_items_returned = $totalItemsReturned;
        
        return view('pages.sales.returnedSale_detail', compact('sale', 'returns'));
    }
}