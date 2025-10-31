<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display the list of customers.
     */
    // Page loads instantly - NO database queries
public function index()
{
    return view('pages.customer.customer');
}
    // AJAX endpoint - returns data only
public function getData(Request $request)
{
    $page = $request->get('page', 1);
    
    // Get all customers with sales count and payment type breakdown
    $customers = Customer::withCount(['sales' => function ($query) {
        $query->whereNull('deleted_at');
    }])
        ->with(['sales' => function ($query) {
            $query->whereNull('deleted_at')->latest();
        }])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

    // Get sales breakdown by payment type for each customer
    $salesBreakdown = [];
    foreach ($customers as $customer) {
        $salesBreakdown[$customer->id] = DB::table('sales')
            ->select('payment_type', DB::raw('count(*) as count'))
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');
    }
    
    // Return JSON for AJAX
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'salesBreakdown' => $salesBreakdown,
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'first_item' => $customers->firstItem(),
                'last_item' => $customers->lastItem(),
                'has_more_pages' => $customers->hasMorePages(),
                'on_first_page' => $customers->onFirstPage(),
            ]
        ]);
    }
    
    // Fallback for non-AJAX requests
    return view('pages.customer.customer', compact('customers', 'salesBreakdown'));
}

// Page loads instantly - NO database queries
public function creditCustomerindex()
{
    return view('pages.customer.creditCustomer');
}

   // AJAX endpoint for credit customers
public function getCreditData(Request $request)
{
    $page = $request->get('page', 1);
    
    $customers = Customer::withCount(['sales as credit_sales_count' => function ($query) {
        $query->where('payment_type', 'credit');
    }])
    ->with(['sales' => function ($query) {
        $query->where('payment_type', 'credit')->latest();
    }])
    ->has('sales', '>=', 1)
    ->whereHas('sales', function ($query) {
        $query->where('payment_type', 'credit');
    })
    ->orderBy('created_at', 'desc')
    ->paginate(10);
    
    // Return JSON for AJAX
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'first_item' => $customers->firstItem(),
                'last_item' => $customers->lastItem(),
                'has_more_pages' => $customers->hasMorePages(),
                'on_first_page' => $customers->onFirstPage(),
            ]
        ]);
    }
    
    return view('pages.customer.creditCustomer', compact('customers'));
}

    /**
     * Check for duplicate customer before creating
     */
    public function checkDuplicate(Request $request)
    {
        $duplicate = Customer::where('name', $request->name)
            ->where('city', $request->city)
            ->where('contact', $request->contact)
            ->where('customer_type', $request->customer_type)
            ->first();

        if ($duplicate) {
            return response()->json([
                'duplicate' => true,
                'customer_id' => $duplicate->id,
                'message' => 'Customer already exists with same details'
            ]);
        }

        return response()->json(['duplicate' => false]);
    }

/**
 * Find existing customer or create new one
 */
public function findOrCreateCustomer($customerData)
{
    // ✅ Step 1: Try to find by contact (most reliable field)
    $existingCustomer = null;
    if (!empty($customerData['contact'])) {
        $existingCustomer = Customer::where('contact', $customerData['contact'])->first();
    }

    if (!$existingCustomer && !empty($customerData['shop_name'])) {
        $existingCustomer = Customer::where('shop_name', $customerData['shop_name'])->first();
    }

    // ✅ Step 2: If found, check for updates (name/shop/city changes or credit upgrade)
if ($existingCustomer) {
    $updates = [];

    // Normalize all values (trim + lowercase for comparison)
    $newName = isset($customerData['name']) ? trim(strtolower($customerData['name'])) : '';
    $newShop = isset($customerData['shop_name']) ? trim(strtolower($customerData['shop_name'])) : '';
    $newCity = isset($customerData['city']) ? trim(strtolower($customerData['city'])) : '';
    $newContact = isset($customerData['contact']) ? trim($customerData['contact']) : '';

    $oldName = !empty($existingCustomer->name) ? trim(strtolower($existingCustomer->name)) : '';
    $oldShop = !empty($existingCustomer->shop_name) ? trim(strtolower($existingCustomer->shop_name)) : '';
    $oldCity = !empty($existingCustomer->city) ? trim(strtolower($existingCustomer->city)) : '';
    $oldContact = !empty($existingCustomer->contact) ? trim($existingCustomer->contact) : '';

    // ✅ Update name if: (1) new value provided AND (2) either different OR old is empty/Walk-in
    if (!empty($newName) && 
        ($newName !== $oldName || $oldName === 'walk-in customer' || empty($existingCustomer->name))) {
        $updates['name'] = trim($customerData['name']);
    }
    
    // ✅ Update shop name if: (1) new value provided AND (2) either different OR old is empty
    if (!empty($newShop) && 
        ($newShop !== $oldShop || empty($existingCustomer->shop_name))) {
        $updates['shop_name'] = trim($customerData['shop_name']);
    }
    
    // ✅ Update city if: (1) new value provided AND (2) either different OR old is empty
    if (!empty($newCity) && 
        ($newCity !== $oldCity || empty($existingCustomer->city))) {
        $updates['city'] = trim($customerData['city']);
    }
    
    // ✅ Update contact if it changed (customer changed phone number)
    if (!empty($newContact) && $newContact !== $oldContact) {
        $updates['contact'] = trim($customerData['contact']);
    }

    // Upgrade permanently to credit if applicable
    if ($existingCustomer->customer_type !== 'credit' && ($customerData['customer_type'] ?? '') === 'credit') {
        $updates['customer_type'] = 'credit';
    }

    // Apply updates if needed
    if (!empty($updates)) {
        $existingCustomer->update($updates);
    }

    return $existingCustomer;
}

    // ✅ Step 3: Create new if not found
    return Customer::create([
        'name' => trim($customerData['name'] ?? 'Walk-in Customer'),
        'shop_name' => trim($customerData['shop_name'] ?? ''),
        'city' => trim($customerData['city'] ?? ''),
        'contact' => trim($customerData['contact'] ?? ''),
        'customer_type' => $customerData['customer_type'] ?? 'cash',
        'current_balance' => 0
    ]);
}

    /**
     * Delete a customer.
     */
    public function destroy(Request $request, $id)
{
    $customer = Customer::with(['sales.saleItems.returnItems'])->findOrFail($id);
    $option = $request->input('delete_option', 'only');

    DB::beginTransaction();
    try {
        if ($option === 'with_sales') {
            // 🧾 Delete all related sales (and their child items)
            foreach ($customer->sales as $sale) {
                // Delete sale return items first (if any)
                foreach ($sale->saleItems as $item) {
                    if ($item->saleReturnItems) {
                        foreach ($item->saleReturnItems as $returnItem) {
                            $returnItem->delete();
                        }
                    }
                    $item->delete(); // delete sale item
                }
                $sale->delete(); // delete sale record
            }

            // Finally delete customer
            $customer->delete();

            DB::commit();
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer and all related sales deleted successfully.');
        } else {
            // 🧍‍♂️ Delete only the customer, keep all their sales intact
            $customer->delete();

            DB::commit();
            return redirect()
                ->route('customers.index')
                ->with('success', 'Customer deleted successfully. Sales history remains intact.');
        }
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()
            ->route('customers.index')
            ->with('error', 'Error deleting customer: ' . $e->getMessage());
    }
}

public function destroyCreditCustomer(Request $request, $id)
{
    // Accept options:
    // - 'only_sales'           => soft-delete only credit sales (customer stays)
    // - 'sales_and_customer'   => soft-delete credit sales and customer (only if no cash/card sales exist)
    $option = $request->input('delete_option', 'only_sales');

    // Load sales (including relations we may delete)
    $customer = Customer::with(['sales.saleItems.returnItems'])->findOrFail($id);

    DB::beginTransaction();
    try {
        // Does customer have any active (non-deleted) cash/card sales?
        $hasNonCreditSales = $customer->sales()
            ->whereNull('deleted_at')
            ->whereIn('payment_type', ['cash', 'card'])
            ->exists();

        // Always get credit sales to act on
        $creditSales = $customer->sales()
            ->whereNull('deleted_at')
            ->where('payment_type', 'credit')
            ->get();

        if ($option === 'only_sales') {
            // Soft-delete all credit sales (and their children)
            foreach ($creditSales as $sale) {
                // delete child return items
                foreach ($sale->saleItems as $item) {
                    if (method_exists($item, 'returnItems')) {
                        $item->returnItems()->delete();
                    }
                    $item->delete();
                }
                $sale->delete();
            }
            // If the customer has no other active (cash/card) sales, delete the customer too
        if (!$hasNonCreditSales) {
            $customer->delete();
            DB::commit();
            return redirect()->route('customers.credit.index')
                ->with('success', 'Customer and all credit sales deleted successfully.');
        }


            // If the customer has no other active sales (no cash/card and no remaining credit sales),
            // we keep the customer record (so it can be restored later). Do not delete customer here.
            DB::commit();

            return redirect()->route('customers.credit.index') // adjust route name to your credit list route
                ->with('success', 'All credit sales removed for this customer. Customer remains for general sales.');
    }
            }catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Error deleting credit-customer: ' . $e->getMessage());
                    return redirect()->route('customers.credit.index')
                        ->with('error', 'Something went wrong: ' . $e->getMessage());
                }
    }



    /**
     * Show customer purchases grouped by sales/invoices
     */
    public function showPurchases($id)
{
    // Get customer details
    $customer = Customer::findOrFail($id);
    
    // 🎯 FIX: Get sale_item IDs separately to avoid grouping issues
    $salesData = DB::table('sales')
        ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->leftJoin('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned, SUM(amount_refunded) as total_refunded
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->where('sales.customer_id', $id)
        ->whereNull('sales.deleted_at')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.payment_type',
            'sales.subtotal',
            'sales.discount_type',
            'sales.discount',
            'sales.discount_amount',
            'sales.tax_type',
            'sales.tax',
            'sales.tax_amount',
            'sales.grand_total',
            'sales.received_amount',
            'sales.remaining_balance',
            'sales.status',
            'sales.created_at as sale_date',
            'sale_items.id as sale_item_id', // 🎯 ADD THIS - unique identifier
            'purchases.product_name',
            'purchases.unit',
            'sale_items.quantity as original_quantity',
            'sale_items.price',
            'sale_items.discount_type as item_discount_type',
            'sale_items.discount_value as item_discount_value',
            'sale_items.discount_amount as item_discount_amount',
            'sale_items.total as item_total',
            'sale_items.total_after_discount',
            DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            DB::raw('COALESCE(returned_qty.total_refunded, 0) as returned_amount'),
            DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            DB::raw('CASE 
                WHEN sale_items.quantity > 0 AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as remaining_total_after_item_discount')
        )
        // 🎯 REMOVE groupBy completely - we want all sale_items as separate rows
        ->orderBy('sales.created_at', 'desc')
        ->orderBy('sale_items.id', 'asc') // Keep items in order
        ->get();

    // Group sales data by sale_id
    $groupedSales = [];
    foreach ($salesData as $item) {
        $saleId = $item->sale_id;
        
        if (!isset($groupedSales[$saleId])) {
            // 🎯 Get total payments for this sale (correct calculation)
            $totalPaid = DB::table('payments')
                ->where('sale_id', $saleId)
                ->sum('amount');
            
            // Get total returned amount for this sale
            $totalReturnedAmount = DB::table('sale_return_items')
                ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
                ->where('sale_returns.sale_id', $saleId)
                ->sum('sale_return_items.amount_refunded');
            
            $hasReturns = $totalReturnedAmount > 0;
            $returnPercentage = $item->grand_total > 0 ? ($totalReturnedAmount / $item->grand_total) : 0;
            
            $adjustedSubtotal = $item->subtotal * (1 - $returnPercentage);
            $adjustedDiscountAmount = $item->discount_amount * (1 - $returnPercentage);
            if ($item->tax_type === 'percentage') {
                $adjustedTax = ($item->tax_amount ?? 0) * (1 - $returnPercentage);
            } else {
                $adjustedTax = ($item->tax ?? 0) * (1 - $returnPercentage);
            }
            $adjustedGrandTotal = $item->grand_total - $totalReturnedAmount;
            $groupedSales[$saleId] = [
                'sale_info' => [
                    'voucher_no' => $item->voucher_no,
                    'payment_type' => $item->payment_type,
                    'subtotal' => $item->subtotal,
                    'discount_type' => $item->discount_type,
                    'discount' => $item->discount,
                    'discount_amount' => $item->discount_amount,
'tax_type' => $item->tax_type,
'tax' => $item->tax, // keep actual input value (like 5%)
'tax_amount' => $item->tax_amount, // computed amount in currency
                    'grand_total' => $item->grand_total,
                    'received_amount' => $item->received_amount,
                    'remaining_balance' => $item->remaining_balance,
                    'total_paid' => $totalPaid, // 🎯 FIXED: Correct payment total
                    'status' => $item->status,
                    'sale_date' => $item->sale_date,
                    'has_returns' => $hasReturns,
                    'total_returned_amount' => $totalReturnedAmount,
                    'adjusted_subtotal' => max(0, $adjustedSubtotal),
                    'adjusted_discount_amount' => max(0, $adjustedDiscountAmount),
                    'adjusted_tax' => max(0, $adjustedTax),
                    'adjusted_grand_total' => max(0, $adjustedGrandTotal)
                ],
                'items' => []
            ];
        }
        
        // 🎯 Add EVERY item (including duplicates)
        if ($item->product_name) {
            $adjustedDiscountAmount = 0;
            if ($item->remaining_quantity > 0 && $item->original_quantity > 0) {
                $adjustedDiscountAmount = ($item->item_discount_amount * $item->remaining_quantity) / $item->original_quantity;
            }
            
            $groupedSales[$saleId]['items'][] = [
                'sale_item_id' => $item->sale_item_id, // 🎯 ADD THIS
                'product_name' => $item->product_name,
                'unit' => $item->unit,
                'original_quantity' => $item->original_quantity,
                'returned_quantity' => $item->returned_quantity,
                'remaining_quantity' => $item->remaining_quantity,
                'price' => $item->price,
                'item_discount_type' => $item->item_discount_type,
                'item_discount_value' => $item->item_discount_value,
                'item_discount_amount' => $item->item_discount_amount,
                'adjusted_discount_amount' => $adjustedDiscountAmount,
                'item_total' => $item->item_total,
                'total_after_discount' => $item->total_after_discount,
                'remaining_total_after_item_discount' => $item->remaining_total_after_item_discount,
                'adjusted_total_after_discount' => $item->remaining_total_after_item_discount
            ];
        }
    }

    // Convert to paginated collection
    $perPage = 5;
    $currentPage = request()->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    
    $paginatedSales = array_slice($groupedSales, $offset, $perPage, true);
    
    $total = count($groupedSales);
    $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
        $paginatedSales,
        $total,
        $perPage,
        $currentPage,
        [
            'path' => request()->url(),
            'pageName' => 'page',
        ]
    );

    return view('pages.customer.customer_purchases', compact('customer', 'pagination'));
}

/**
 * Show only credit customers' credit purchases grouped by sales/invoices
 */
public function showCreditPurchases($id)
{
    // Get customer details
    $customer = Customer::where('id', $id)
        ->where('customer_type', 'credit')
        ->firstOrFail();

    // 🎯 FIX: Get sale_item IDs separately
    $salesData = DB::table('sales')
        ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->leftJoin('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned, SUM(amount_refunded) as total_refunded
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->where('sales.customer_id', $id)
        ->where('sales.payment_type', 'credit')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.payment_type',
            'sales.subtotal',
            'sales.discount_type',
            'sales.discount',
            'sales.discount_amount',
            'sales.tax_type',
            'sales.tax',
            'sales.tax_amount',
            'sales.grand_total',
            'sales.received_amount',
            'sales.remaining_balance',
            'sales.status',
            'sales.created_at as sale_date',
            'sale_items.id as sale_item_id', // 🎯 ADD THIS
            'purchases.product_name',
            'purchases.unit',
            'sale_items.quantity as original_quantity',
            'sale_items.price',
            'sale_items.discount_type as item_discount_type',
            'sale_items.discount_value as item_discount_value',
            'sale_items.discount_amount as item_discount_amount',
            'sale_items.total as item_total',
            'sale_items.total_after_discount',
            DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            DB::raw('COALESCE(returned_qty.total_refunded, 0) as returned_amount'),
            DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            DB::raw('CASE 
                WHEN sale_items.quantity > 0 AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as remaining_total_after_item_discount')
        )
        // 🎯 REMOVE groupBy
        ->orderBy('sales.created_at', 'desc')
        ->orderBy('sale_items.id', 'asc')
        ->get();

    // Group sales data
    $groupedSales = [];
    foreach ($salesData as $item) {
        $saleId = $item->sale_id;

        if (!isset($groupedSales[$saleId])) {
            // 🎯 Get total payments correctly
            $totalPaid = DB::table('payments')
                ->where('sale_id', $saleId)
                ->sum('amount');
            
            $totalReturnedAmount = DB::table('sale_return_items')
                ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
                ->where('sale_returns.sale_id', $saleId)
                ->sum('sale_return_items.amount_refunded');

            $hasReturns = $totalReturnedAmount > 0;
            $returnPercentage = $item->grand_total > 0 ? ($totalReturnedAmount / $item->grand_total) : 0;

            $adjustedSubtotal = $item->subtotal * (1 - $returnPercentage);
            $adjustedDiscountAmount = $item->discount_amount * (1 - $returnPercentage);
            if ($item->tax_type === 'percentage') {
                $adjustedTax = ($item->tax_amount ?? 0) * (1 - $returnPercentage);
            } else {
                $adjustedTax = ($item->tax ?? 0) * (1 - $returnPercentage);
            }
            $adjustedGrandTotal = $item->grand_total - $totalReturnedAmount;
            $groupedSales[$saleId] = [
                'sale_info' => [
                    'voucher_no' => $item->voucher_no,
                    'payment_type' => $item->payment_type,
                    'subtotal' => $item->subtotal,
                    'discount_type' => $item->discount_type,
                    'discount' => $item->discount,
                    'discount_amount' => $item->discount_amount,
'tax_type' => $item->tax_type,
'tax' => $item->tax, // keep actual input value (like 5%)
'tax_amount' => $item->tax_amount, // computed amount in currency
                    'grand_total' => $item->grand_total,
                    'received_amount' => $item->received_amount,
                    'remaining_balance' => $item->remaining_balance,
                    'total_paid' => $totalPaid, // 🎯 FIXED
                    'status' => $item->status,
                    'sale_date' => $item->sale_date,
                    'has_returns' => $hasReturns,
                    'total_returned_amount' => $totalReturnedAmount,
                    'adjusted_subtotal' => max(0, $adjustedSubtotal),
                    'adjusted_discount_amount' => max(0, $adjustedDiscountAmount),
                    'adjusted_tax' => max(0, $adjustedTax),
                    'adjusted_grand_total' => max(0, $adjustedGrandTotal),
                ],
                'items' => []
            ];
        }

        // 🎯 Add every item
        if ($item->product_name) {
            $adjustedDiscountAmount = 0;
            if ($item->remaining_quantity > 0 && $item->original_quantity > 0) {
                $adjustedDiscountAmount = ($item->item_discount_amount * $item->remaining_quantity) / $item->original_quantity;
            }

            $groupedSales[$saleId]['items'][] = [
                'sale_item_id' => $item->sale_item_id, // 🎯 ADD THIS
                'product_name' => $item->product_name,
                'unit' => $item->unit,
                'original_quantity' => $item->original_quantity,
                'returned_quantity' => $item->returned_quantity,
                'remaining_quantity' => $item->remaining_quantity,
                'price' => $item->price,
                'item_discount_type' => $item->item_discount_type,
                'item_discount_value' => $item->item_discount_value,
                'item_discount_amount' => $item->item_discount_amount,
                'adjusted_discount_amount' => $adjustedDiscountAmount,
                'item_total' => $item->item_total,
                'total_after_discount' => $item->total_after_discount,
                'remaining_total_after_item_discount' => $item->remaining_total_after_item_discount,
                'adjusted_total_after_discount' => $item->remaining_total_after_item_discount
            ];
        }
    }

    // Paginate
    $perPage = 5;
    $currentPage = request()->get('page', 1);
    $offset = ($currentPage - 1) * $perPage;
    $paginatedSales = array_slice($groupedSales, $offset, $perPage, true);

    $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
        $paginatedSales,
        count($groupedSales),
        $perPage,
        $currentPage,
        [
            'path' => request()->url(),
            'pageName' => 'page',
        ]
    );

    return view('pages.customer.credit_customer_purchases', compact('customer', 'pagination'));
}
}