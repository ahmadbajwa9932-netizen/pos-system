<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Sale_item;
use App\Models\SaleReturn;   
use App\Models\SaleReturnItem;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\InvoiceCounter;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function create()
{
    // 1️⃣ Get all purchases where stock is still available
    $purchases = Purchase::select('id', 'product_name as name', 'sold_price as price', 'unit' ,'purchased_price', 'quantity', 'sold_quantity')
        ->get();

    // 2️⃣ Map available stock field (calculated dynamically)
    $purchases->map(function ($purchase) {
        $purchase->available_stock = $purchase->quantity - $purchase->sold_quantity;
        return $purchase;
    });

    // 3️⃣ Reuse purchases as products for the search bar
    $products = $purchases;
    // 4️⃣ Return both for backward compatibility
        $customers = Customer::select('id', 'name', 'shop_name', 'city', 'contact')->get();
    return view('pages.make_a_sale.create', compact('purchases', 'products','customers'));
}

/**
 * Calculate payment distribution between current sale and previous balance
 * 
 * @param float $grandTotal - Current sale's grand total
 * @param float $currentBalance - Customer's previous balance
 * @param float $receivedAmount - Amount customer is paying
 * @param string $paymentType - Payment type (cash/credit/card)
 * 
 * @return array [
 *   'sale_payment' => amount allocated to current sale,
 *   'balance_payment' => amount allocated to previous balance,
 *   'new_balance' => customer's new remaining balance,
 *   'sale_status' => status for current sale (paid/partial/unpaid),
 *   'sale_remaining' => remaining amount for current sale
 * ]
 */
private function calculatePaymentDistribution($grandTotal, $currentBalance, $receivedAmount, $paymentType)
{
    $totalPayable = $grandTotal + $currentBalance;
    // Force full payment for cash/card even if received_amount is empty
// if (in_array($paymentType, ['cash', 'card'])) {
//     $receivedAmount = $receivedAmount > 0 ? $receivedAmount : $grandTotal + $currentBalance;
// }
    
    // For credit sales with no payment
    if ($paymentType === 'credit' && $receivedAmount == 0) {
        return [
            'sale_payment' => 0,
            'balance_payment' => 0,
            'new_balance' => $totalPayable,
            'sale_status' => 'unpaid',
            'sale_remaining' => $grandTotal
        ];
    }
    
    // For credit sales with partial payment
    if ($paymentType === 'credit' && $receivedAmount > 0 && $receivedAmount < $totalPayable) {
        // First pay current sale, then old balance
        if ($receivedAmount >= $grandTotal) {
            $salePayment = $grandTotal;
            $balancePayment = $receivedAmount - $grandTotal;
        } else {
            $salePayment = $receivedAmount;
            $balancePayment = 0;
        }
        
        $saleRemaining = $grandTotal - $salePayment;
        $balanceRemaining = $currentBalance - $balancePayment;
        
        return [
            'sale_payment' => $salePayment,
            'balance_payment' => $balancePayment,
            'new_balance' => $saleRemaining + $balanceRemaining,
            'sale_status' => $saleRemaining > 0 ? 'partial' : 'paid',
            'sale_remaining' => $saleRemaining
        ];
    }
    
    // For cash/card sales or full payment
    if ($receivedAmount >= $totalPayable) {
        // Full payment received
        return [
            'sale_payment' => $grandTotal,
            'balance_payment' => $currentBalance,
            'new_balance' => 0,
            'sale_status' => 'paid',
            'sale_remaining' => 0
        ];
    }
    
    // Partial payment for cash/card (should be prevented, but handle gracefully)
    // Distribute payment proportionally
    $salePayment = ($grandTotal / $totalPayable) * $receivedAmount;
    $balancePayment = ($currentBalance / $totalPayable) * $receivedAmount;
    
    return [
        'sale_payment' => $salePayment,
        'balance_payment' => $balancePayment,
        'new_balance' => $totalPayable - $receivedAmount,
        'sale_status' => 'partial',
        'sale_remaining' => $grandTotal - $salePayment
    ];
}    


    public function store(Request $request)
{
    // ✅ Validation rules
    $rules = [
        'customer_name' => 'nullable|string',
        'customer_type' => 'required|in:cash,credit,card',
        'payment_type' => 'required|in:cash,credit,card',
        'subtotal' => 'required|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'discount_type' => 'nullable|in:amount,percentage',
        'tax' => 'nullable|numeric|min:0',
        'grand_total' => 'required|numeric|min:0',
        'received_amount' => 'nullable|numeric|min:0',
        'change_amount' => 'nullable|numeric|min:0',
    ];
    // 🎯 CRITICAL: Only require items array when grand_total > 0
if (floatval($request->grand_total) > 0) {
    $rules['items'] = 'required|array|min:1';
    $rules['items.*.purchase_id'] = 'required|exists:purchases,id';
    $rules['items.*.quantity'] = 'required|integer|min:1';
    $rules['items.*.price'] = 'required|numeric|min:0';
    $rules['items.*.discount_type'] = 'nullable|in:amount,percentage';
    $rules['items.*.discount_value'] = 'nullable|numeric|min:0';
}

    $request->validate($rules);

    try {
        
        $result = DB::transaction(function () use ($request) {
           // ============================================
// 🎯 NEW: Handle Payment-Only Transactions
// (When grand_total = 0, customer is only paying old dues)
// ============================================
if (floatval($request->grand_total) === 0.0 && floatval($request->received_amount) > 0.0) {
    // Find customer
    $customer = null;
    if (!empty($request->contact)) {
        $customer = Customer::where('contact', $request->contact)->first();
    }

    if (!$customer || $customer->current_balance == 0) {
        throw new \Exception('No outstanding balance found for this customer.');
    }

    $receivedAmount = $request->received_amount;
    $currentBalance = $customer->current_balance;

    // Determine actual payment method (never use 'credit' for payment records)
    $actualPaymentMethod = $request->payment_type === 'credit' ? 'cash' : $request->payment_type;

    // Case 1: Full payment (pays all dues)
    if ($receivedAmount >= $currentBalance) {
        $changeAmount = $receivedAmount - $currentBalance;
        
        // Mark all unpaid/partial credit sales as paid
        $unpaidSales = Sale::where('customer_id', $customer->id)
            ->where('payment_type', 'credit')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_balance', '>', 0)
            ->get();

        foreach ($unpaidSales as $unpaidSale) {
            // Add payment record
            Payment::create([
                'sale_id' => $unpaidSale->id,
                'amount' => $unpaidSale->remaining_balance,
                'method' => $actualPaymentMethod,  // ✅ FIXED
                'payment_date' => now()
            ]);

            // Update sale status
            $unpaidSale->update([
                'status' => 'paid',
                'remaining_balance' => 0,
                'payment_type' => $actualPaymentMethod,  // ✅ FIXED: Never set to 'credit'
                'due_date' => null
            ]);
        }

        // Clear customer balance
        $customer->update([
            'current_balance' => 0,
            'customer_type' => $actualPaymentMethod  // ✅ FIXED
        ]);

        // Return success signal
return [
    'type' => 'payment_success',
    'message' => 'All dues cleared successfully! Change amount: Rs ' . number_format($changeAmount, 2)
];
    }
    
    // Case 2: Partial payment (pays some dues)
    else {
        $remainingPayment = $receivedAmount;
        
        // Get all unpaid/partial credit sales ordered by date (oldest first)
        $unpaidSales = Sale::where('customer_id', $customer->id)
            ->where('payment_type', 'credit')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_balance', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        // Distribute payment across invoices
        foreach ($unpaidSales as $unpaidSale) {
            if ($remainingPayment <= 0) break;

            $invoiceBalance = $unpaidSale->remaining_balance;
            $paymentForThisInvoice = min($remainingPayment, $invoiceBalance);

            // Create payment record
            Payment::create([
                'sale_id' => $unpaidSale->id,
                'amount' => $paymentForThisInvoice,
                'method' => $actualPaymentMethod,  // ✅ FIXED
                'payment_date' => now()
            ]);

            // Update invoice
            $newBalance = $invoiceBalance - $paymentForThisInvoice;
            $unpaidSale->update([
                'remaining_balance' => $newBalance,
                'status' => $newBalance > 0 ? 'partial' : 'paid',
                'payment_type' => $newBalance > 0 ? 'credit' : $actualPaymentMethod,  // ✅ FIXED: Only change to cash/card when fully paid
                'due_date' => $newBalance > 0 ? $unpaidSale->due_date : null
            ]);

            $remainingPayment -= $paymentForThisInvoice;
        }

        // Update customer balance
        $customer->update([
            'current_balance' => max(0, $currentBalance - $receivedAmount)
        ]);

        // Return success signal
return [
    'type' => 'payment_success',
    'message' => 'Payment of Rs ' . number_format($receivedAmount, 2) . ' applied successfully! Remaining balance: Rs ' . number_format($customer->current_balance, 2)
];
    }
}
            
            // ============================================
            // 🎯 Continue with normal sale creation logic
            // ============================================
            // ✅ Generate Voucher Number
            $today = now()->format('Ymd');

// Try to get today's counter
$counter = \App\Models\InvoiceCounter::firstOrCreate(
    ['date' => $today],
    ['last_number' => 0]
);

// Increment the counter safely
$counter->increment('last_number');

// Build the voucher number
$voucherNo = 'INV-' . $today . '-' . $counter->last_number;


            // ✅ Smart Customer Management Logic
            $customer = null;
            
            // 1️⃣ Try to find existing customer by contact (most reliable field)
            if (!empty($request->contact)) {
                $customer = Customer::where('contact', $request->contact)->first();
            }
            if (!$customer && !empty($request->shop_name)) {
                $customer = Customer::where('shop_name', $request->shop_name)->first();
            }

            // 2️⃣ If not found, create new customer
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $request->customer_name ?? 'Walk-in Customer',
                    'shop_name' => $request->shop_name,
                    'customer_type' => $request->payment_type,
                    'city' => $request->city,
                    'contact' => $request->contact,
                    'current_balance' => 0 // ✅ Initialize with 0
                ]);
            } else {
                // 3️⃣ If found, update details when info differs
                $updates = [];

                if (!empty($request->customer_name) && trim(strtolower($request->customer_name)) !== trim(strtolower($customer->name))) {
                    $updates['name'] = trim($request->customer_name);
                }
                if (!empty($request->shop_name) && trim(strtolower($request->shop_name)) !== trim(strtolower($customer->shop_name))) {
                    $updates['shop_name'] = trim($request->shop_name);
                }
                if (!empty($request->city) && trim(strtolower($request->city)) !== trim(strtolower($customer->city))) {
                    $updates['city'] = trim($request->city);
                }
                if (!empty($request->contact) && trim($request->contact) !== trim($customer->contact)) {
                    $updates['contact'] = trim($request->contact);
                }

                // Upgrade permanently if customer buys on credit
                if ($customer->customer_type !== 'credit' && $request->payment_type === 'credit') {
                    $updates['customer_type'] = 'credit';
                }

                if (!empty($updates)) {
                    $customer->update($updates);
                }
            }

            // ✅ Calculate discount amount for sale
            $discountType = $request->discount_type ?? 'amount';
            $discountAmount = $request->discount ?? 0;
            if ($discountType === 'percentage') {
                $discountAmount = ($request->subtotal * $request->discount) / 100;
            }

            // ✅ Get customer's current balance
            $received = $request->received_amount ?? 0;
            $currentBalance = $customer->current_balance ?? 0;

            // ✅ Calculate payment distribution
           // ✅ Shortcut for simple cash/card sales
if (in_array($request->payment_type, ['cash', 'card'])) {
    $paymentDistribution = [
        'sale_payment' => $request->grand_total,
        'balance_payment' => 0,
        'new_balance' => $currentBalance,
        'sale_status' => 'paid',
        'sale_remaining' => 0
    ];
} else {
    // 🔹 Credit sales go through balance logic
    $paymentDistribution = $this->calculatePaymentDistribution(
        $request->grand_total,
        $currentBalance,
        $request->received_amount ?? 0,
        $request->payment_type
    );
}


            // ✅ Extract distribution results
            $salePayment = $paymentDistribution['sale_payment'];
            $balancePayment = $paymentDistribution['balance_payment'];
            $newCustomerBalance = $paymentDistribution['new_balance'];
            $status = $paymentDistribution['sale_status'];
            $remainingBalance = $paymentDistribution['sale_remaining'];

            $salePaymentType = $request->payment_type;
if ($status === 'paid' && $request->payment_type !== 'credit' && $salePayment > 0) {
    $salePaymentType = $request->payment_type;
} elseif ($status === 'paid' && $request->payment_type === 'credit' && $salePayment >= $request->grand_total) {
    // Edge case: credit sale but fully paid in same transaction
    $salePaymentType = 'cash'; // Default to cash
}
            // ✅ Create Sale
            $sale = Sale::create([
                'voucher_no' => $voucherNo,
                'customer_id' => $customer->id,
                'payment_type' => $salePaymentType,
                'subtotal' => $request->subtotal,
                'discount_type' => $discountType,
                'discount' => $request->discount ?? 0,
                'discount_amount' => $discountAmount,
                'tax' => $request->tax ?? 0,
                'grand_total' => $request->grand_total,
                'received_amount' => $received,
                'change_amount' => $request->change_amount ?? 0,
                'remaining_balance' => $remainingBalance,
                'due_date' => ($request->customer_type === 'credit' && $request->payment_type === 'credit') 
                            ? $request->due_date 
                            : null,
                'status' => $status
            ]);

            // ✅ Store Sale Items
            foreach ($request->items as $item) {
                $purchase = Purchase::findOrFail($item['purchase_id']);

                // Update sold quantity
                $purchase->increment('sold_quantity', $item['quantity']);
                
                // Update sold price if changed
                if ($purchase->sold_price != $item['price']) {
                    $purchase->update([
                        'previous_sold_price' => $purchase->sold_price,
                        'sold_price' => $item['price']
                    ]);
                }

                $price = $item['price'];
                $quantity = $item['quantity'];
                $total = $price * $quantity;

                $itemDiscountType = $item['discount_type'] ?? 'amount';
                $itemDiscountValue = $item['discount_value'] ?? 0;
                $itemDiscountAmount = ($itemDiscountType === 'percentage')
                    ? ($total * $itemDiscountValue / 100)
                    : $itemDiscountValue;

                Sale_item::create([
                    'sale_id' => $sale->id,
                    'purchase_id' => $purchase->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount_type' => $itemDiscountType,
                    'discount_value' => $itemDiscountValue,
                    'discount_amount' => $itemDiscountAmount,
                    'total' => $total,
                    'total_after_discount' => $total - $itemDiscountAmount
                ]);
            }

            // ✅ Create Payment Records
           // ✅ Create Payment Record for Current Sale
           if ($salePayment > 0) {
            Payment::create([
                'sale_id' => $sale->id,
                'amount' => $salePayment,
                'method' => $request->payment_type,
                'payment_date' => now()
            ]);
        }

        // 🎯 NEW: DISTRIBUTE BALANCE PAYMENT TO OLD INVOICES
        if ($balancePayment > 0) {
            $remainingPayment = $balancePayment;
            
            // Get all unpaid/partial credit sales ordered by date (oldest first)
            $unpaidSales = Sale::where('customer_id', $customer->id)
                ->where('payment_type', 'credit')
                ->whereIn('status', ['unpaid', 'partial'])
                ->where('remaining_balance', '>', 0)
                ->where('id', '!=', $sale->id) // Exclude current sale
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($unpaidSales as $unpaidSale) {
                if ($remainingPayment <= 0) break;

                $invoiceBalance = $unpaidSale->remaining_balance;
                $paymentForThisInvoice = min($remainingPayment, $invoiceBalance);

                // Create payment record for this old invoice
                Payment::create([
                    'sale_id' => $unpaidSale->id,
                    'amount' => $paymentForThisInvoice,
                    'method' => $request->payment_type,
                    'payment_date' => now()
                ]);

                // Update old invoice
                $newInvoiceBalance = $invoiceBalance - $paymentForThisInvoice;
                $unpaidSale->update([
                    'remaining_balance' => $newInvoiceBalance,
                    'status' => $newInvoiceBalance > 0 ? 'partial' : 'paid',
                    'payment_type' => $newInvoiceBalance > 0 ? 'credit' : ($request->payment_type === 'credit' ? 'cash' : $request->payment_type),
                    'due_date' => $newInvoiceBalance > 0 ? $unpaidSale->due_date : null
                ]);

                $remainingPayment -= $paymentForThisInvoice;
            }
        }
            // 🎯 UPDATE CUSTOMER BALANCE (THIS IS THE KEY PART!)
            // if ($remainingBalance > 0) {
                // Add remaining balance to customer's current balance
            //     $customer->increment('current_balance', $remainingBalance);
            // }
            // 🎯 UPDATE CUSTOMER BALANCE
            // Set new balance (includes both unpaid current sale + unpaid old balance)
            // 🎯 Smart Current Balance Update
if (in_array($request->payment_type, ['credit'])) {
    // Only update balance for credit-related sales
    $customer->update(['current_balance' => $newCustomerBalance]);
} else {
    if ($balancePayment > 0) {
        // Payment was made towards old balance, update it
        $customer->update(['current_balance' => $newCustomerBalance]);
    }
}

            // if ($newCustomerBalance == 0 && $customer->id) {
            //     $recentPaymentMethod = Sale::where('customer_id', $customer->id)
            //         ->whereIn('payment_type', ['cash', 'card'])
            //         ->orderBy('created_at', 'desc')
            //         ->value('payment_type') ?? 'cash';
                
            //     // Get all unpaid/partial credit sales
            //     $unpaidSales = Sale::where('customer_id', $customer->id)
            //         ->where('payment_type', 'credit')
            //         ->whereIn('status', ['unpaid', 'partial'])
            //         ->where('remaining_balance', '>', 0)
            //         ->get();
            
            //     foreach ($unpaidSales as $unpaidSale) {
            //         // ✅ Create payment record for old credit sale
            //         Payment::create([
            //             'sale_id' => $unpaidSale->id,
            //             'amount' => $unpaidSale->remaining_balance,
            //             'method' => $recentPaymentMethod,
            //             'payment_date' => now()
            //         ]);
            
            //         // ✅ Update sale to paid
            //         $unpaidSale->update([
            //             'payment_type' => $recentPaymentMethod,
            //             'status' => 'paid',
            //             'remaining_balance' => 0,
            //             'due_date' => null
            //         ]);
            //     }
            
            //     // ✅ Update customer type
            //     $customer->update(['customer_type' => $recentPaymentMethod]);
            // }
            
            return $sale;
        });

// Handle different return types
if (is_array($result) && $result['type'] === 'payment_success') {
    return redirect()->back()->with('success', $result['message']);
}

// ✅ FIX: Handle both array and object returns
$sale = is_array($result) ? ($result['sale'] ?? null) : $result;

if ($request->action_type === 'print') {
    return redirect()->route('sales.print', $sale->id)
                     ->with('success', 'Receipt generated and ready for print!');
} else {
    return redirect()->back()
                     ->with('success', 'Record saved successfully! Voucher No: ' . $sale->voucher_no);
}

    }  catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
} 

    public function checkStock(Request $request, $purchaseId)
{
    $purchase = Purchase::findOrFail($purchaseId);
    $availableStock = $purchase->quantity - $purchase->sold_quantity;

    return response()->json([
        'available' => $availableStock,
        'status' => $availableStock >= $request->quantity ? 'ok' : 'insufficient'
    ]);
}

public function getNextVoucherNo()
{
    $today = now()->format('Ymd');
    $counter = InvoiceCounter::firstOrCreate(
        ['date' => $today],
        ['last_number' => 0]
    );

    $nextNumber = $counter->last_number + 1;
    $voucherNo = 'INV-' . $today . '-' . $nextNumber;

    return response()->json(['voucher_no' => $voucherNo]);
}

public function dailySales()
{
    $today = now()->format('Y-m-d');

    // Get sales with calculated remaining quantities after returns
    $dailySales = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'sales.grand_total',
            'customers.name as customer_name',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id') 
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereDate('sales.created_at', $today)
        // ->where('sales.status', 'paid')
        ->whereIn('sales.payment_type',['cash','card'])
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')        
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->paginate(10);

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $dailySales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');

    return view('pages.sales.dailysale', compact('dailySales', 'saleItems'));
}

public function creditShowDetails($voucher_no)
{
        $creditSale = Sale::with(['customer', 'saleItems.purchase', 'payments'])
            ->where('voucher_no', $voucher_no)
            ->where('payment_type', 'credit')
            ->firstOrFail();
        
        // Calculate total payments made
        $totalPaid = $creditSale->payments->sum('amount');
        $remainingBalance = $creditSale->grand_total - $totalPaid;
        
        // Add calculated values to the sale object
        $creditSale->total_paid = $totalPaid;
        $creditSale->remaining_balance = $remainingBalance;
        $creditSale->is_fully_paid = $remainingBalance <= 0;
        
        return view('pages.sales.creditSale_detail', compact('creditSale'));
}

public function showDetails($voucher_no)
{
    $sale = Sale::with(['customer', 'saleItems.purchase'])->where('voucher_no', $voucher_no)->firstOrFail();
    
    // Get all returns for this sale (similar to SaleReturnController)
    $returns = SaleReturn::with(['items.purchase'])
        ->where('sale_id', $sale->id)
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Calculate totals (matching SaleReturnController logic)
    $totalReturnedAmount = $returns->sum('total_return_amount');
    $totalReturnTransactions = $returns->count();
    $totalItemsReturned = $returns->sum(function ($return) {
        return $return->items->sum('quantity_returned');
    });
    
    // ✅ FIXED: Use the same calculation logic as SaleReturnController
    // Calculate return percentage based on GRAND TOTAL, not subtotal
    $returnPercentage = $sale->grand_total > 0 ? ($totalReturnedAmount / $sale->grand_total) : 0;
    
    // ✅ FIXED: Calculate adjusted values based on the proportion of grand total returned
    $adjustedSubtotal = $sale->subtotal * (1 - $returnPercentage);
    $adjustedDiscountAmount = $sale->discount_amount * (1 - $returnPercentage);
    $adjustedTax = ($sale->tax ?? 0) * (1 - $returnPercentage);
    $adjustedGrandTotal = $sale->grand_total - $totalReturnedAmount;
    
    // ✅ FIXED: Get original sale items with return information (same as SaleReturnController)
    $originalSaleItems = DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned, SUM(amount_refunded) as total_refunded
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->where('sale_items.sale_id', $sale->id)
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
            DB::raw('COALESCE(returned_qty.total_refunded, 0) as returned_amount'),
            DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            // ✅ FIXED: Proper remaining total calculation
            DB::raw('
                CASE 
                    WHEN sale_items.quantity > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END as remaining_total
            ')
        )
        ->get();
    
    // ✅ Add all the calculated fields to sale object (matching SaleReturnController)
    $sale->has_returns = $totalReturnedAmount > 0;
    $sale->total_returned_amount = $totalReturnedAmount;
    $sale->adjusted_subtotal = max(0, $adjustedSubtotal);
    $sale->adjusted_discount_amount = max(0, $adjustedDiscountAmount);
    $sale->adjusted_tax = max(0, $adjustedTax);
    $sale->adjusted_grand_total = max(0, $adjustedGrandTotal);
    $sale->original_sale_items = $originalSaleItems; // This matches SaleReturnController naming
    $sale->total_return_transactions = $totalReturnTransactions;
    $sale->total_items_returned = $totalItemsReturned;
    
    // ✅ Keep backward compatibility with existing view if it expects 'remaining_items'
    $sale->remaining_items = $originalSaleItems;
    
    // ✅ Add returns data if needed for detailed view
    $sale->returns = $returns;
    
    return view('pages.sales.dailySale_detail', compact('sale'));
}

public function generalSales(Request $request)
{
    // ✅ ADD THESE 4 LINES
    $highlightSaleId = $request->get('highlight');
    $perPage = 10;
    $targetPage = 1;

    // ✅ ADD THIS ENTIRE BLOCK (calculates which page the sale is on)
    if ($highlightSaleId) {
        $position = \DB::table('sales')
            ->select('sales.id')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin(\DB::raw('(
                SELECT sale_item_id, SUM(quantity_returned) as total_returned 
                FROM sale_return_items 
                GROUP BY sale_item_id
            ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
            ->whereNull('sales.deleted_at')
            ->groupBy('sales.id')
            ->havingRaw('SUM(CASE WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 THEN 1 ELSE 0 END) > 0')
            ->orderBy('sales.created_at', 'desc')
            ->pluck('sales.id')
            ->search($highlightSaleId);

        if ($position !== false) {
            $targetPage = floor($position / $perPage) + 1;
        }
    }
    // Build base query with remaining quantities after returns
    $query = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'sales.grand_total',
            'customers.name as customer_name',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
          // 🔹 ADD THESE LINES:
    ->whereNull('sales.deleted_at');
    // ->whereNull('customers.deleted_at');

    // Group and paginate
    $generalSales = $query
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $targetPage); // 🔹 CHANGE THIS LINE

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $generalSales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');

    return view('pages.sales.generalSale', compact('generalSales', 'saleItems', 'highlightSaleId'));
}
public function creditSales()
{
    $creditSales = Sale::with('customer')
        ->where('payment_type', 'credit')
        ->whereIn('status', ['unpaid','partial'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

    // Get detailed items with remaining quantities for credit sales
    $saleIds = $creditSales->pluck('id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.id',  // ADD THIS LINE - this is the missing piece!
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0)
        ->get()
        ->groupBy('sale_id');

    return view('pages.sales.creditSale', compact('creditSales', 'saleItems'));
}
/**
 * Delete entire credit sale and restore inventory
 */
public function returnCreditSale($saleId)
{
    try {
        $sale = Sale::with(['saleItems.purchase', 'payments'])->findOrFail($saleId);
        
        // Only allow deletion of unpaid or partial credit sales
        if (!in_array($sale->status, ['unpaid', 'partial']) || $sale->payment_type !== 'credit') {
            return redirect()->back()->with('error', 'Only unpaid credit sales can be returned.');
        }

        DB::transaction(function () use ($sale) {
            // 1. Restore inventory quantities
            foreach ($sale->saleItems as $saleItem) {
                $purchase = $saleItem->purchase;
                $purchase->decrement('sold_quantity', $saleItem->quantity);
            }
// Reduce customer balance when deleting unpaid/partial credit sale
if ($sale->customer && $sale->remaining_balance > 0) {
    $sale->customer->decrement('current_balance', $sale->remaining_balance);
}
            // 2. Delete related records in proper order
            // Delete payments first
            $sale->payments()->delete();
            
            // Delete sale items
            $sale->saleItems()->delete();
            
            // 3. Delete the sale itself
            $sale->delete();
        });

        return redirect()->route('sales.credit')->with('success', 'Credit sale returned successfully and inventory restored.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error deleting sale: ' . $e->getMessage());
    }
}

public function deleteCreditSale($saleId)
{
    try {
        $sale = Sale::with(['saleItems.purchase', 'payments'])->findOrFail($saleId);
        
        // Allow deletion only for unpaid/partial credit sales
        if (!in_array($sale->status, ['unpaid', 'partial']) || $sale->payment_type !== 'credit') {
            return redirect()->back()->with('error', 'Only unpaid credit sales can be deleted.');
        }

        DB::transaction(function () use ($sale) {
            // 1. Do NOT restore inventory anymore
            foreach ($sale->saleItems as $saleItem) {
                $purchase = $saleItem->purchase;
                // $purchase->decrement('sold_quantity', $saleItem->quantity); // ❌ Commented
            }

            // 2. Delete related records
            $sale->payments()->delete();
            $sale->saleItems()->delete();

            // 3. Delete the sale itself
            $sale->delete();
        });

        return redirect()->route('sales.credit')->with('success', 'Credit sale deleted successfully.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error deleting sale: ' . $e->getMessage());
    }
}



/**
 * Delete specific item from credit sale
 */
public function deleteCreditSaleItem($saleId, $saleItemId)
{
    try {
        $sale = Sale::with('saleItems')->findOrFail($saleId);
        $saleItem = Sale_item::with('purchase')->findOrFail($saleItemId);
        
        // Validate that this item belongs to this sale
        if ($saleItem->sale_id != $saleId) {
            return redirect()->back()->with('error', 'Invalid item for this sale.');
        }

        // Only allow deletion from unpaid or partial credit sales
        if (!in_array($sale->status, ['unpaid', 'partial']) || $sale->payment_type !== 'credit') {
            return redirect()->back()->with('error', 'Items can only be deleted from unpaid credit sales.');
        }

        DB::transaction(function () use ($sale, $saleItem) {
            // 1. Restore inventory
            $purchase = $saleItem->purchase;
            $purchase->decrement('sold_quantity', $saleItem->quantity);

            // 2. Delete the sale item
            $saleItem->delete();

            // 3. Recalculate sale totals
            $remainingSaleItems = Sale_item::where('sale_id', $sale->id)->get();
            
            if ($remainingSaleItems->count() === 0) {
                // 🎯 Reduce customer balance FIRST
    if ($sale->customer && $sale->remaining_balance > 0) {
        $sale->customer->decrement('current_balance', $sale->remaining_balance);
    }
                // If no items left, delete the entire sale
                $sale->payments()->delete();
                $sale->delete();
                return;
            }

             // 🎯 STORE OLD BALANCE BEFORE RECALCULATION
    $oldRemainingBalance = $sale->remaining_balance;
            // Recalculate totals
$newSubtotal = $remainingSaleItems->sum('total_after_discount'); // ✅ Changed this line too
            
            // Calculate new sale-level discount if it was percentage-based
            $newSaleDiscountAmount = 0;
            if ($sale->discount_type === 'percentage' && $sale->discount > 0) {
                $newSaleDiscountAmount = ($newSubtotal * $sale->discount) / 100;
            } elseif ($sale->discount_type === 'amount') {
                // Keep the same amount discount, but not more than subtotal
                $newSaleDiscountAmount = min($sale->discount, $newSubtotal);
            }

            // Calculate new grand total
            $newGrandTotal = $newSubtotal - $newSaleDiscountAmount + $sale->tax;
            
            $paidAmount = $sale->grand_total - $oldRemainingBalance;
            $newRemainingBalance = max(0, $newGrandTotal - $paidAmount);

            // Update sale totals
            $sale->update([
                'subtotal' => $newSubtotal,
                'discount_amount' => $newSaleDiscountAmount,
                'grand_total' => $newGrandTotal,
                'remaining_balance' => $newRemainingBalance
            ]);
            // Update customer balance if remaining balance changed
$balanceDifference = $oldRemainingBalance - $newRemainingBalance;

if ($balanceDifference != 0 && $sale->customer) {
    $sale->customer->decrement('current_balance', $balanceDifference);
}
        });

        return redirect()->back()->with('success', 'Item deleted successfully and totals recalculated.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error deleting item: ' . $e->getMessage());
    }
}

/**
 * Reduce quantity of specific item in credit sale
 */
public function reduceCreditSaleItemQuantity(Request $request, $saleId, $saleItemId)
{
    // ✅ Fixed validation to allow 0 quantity
    $request->validate([
        'new_quantity' => 'required|integer|min:0'  // Changed from min:1 to min:0
    ]);

    try {
        $sale = Sale::findOrFail($saleId);
        $saleItem = Sale_item::with('purchase')->findOrFail($saleItemId);
        
        // Validate that this item belongs to this sale
        if ($saleItem->sale_id != $saleId) {
            return redirect()->back()->with('error', 'Invalid item for this sale.');
        }

        // Only allow reduction from unpaid or partial credit sales
        if (!in_array($sale->status, ['unpaid', 'partial']) || $sale->payment_type !== 'credit') {
            return redirect()->back()->with('error', 'Quantity can only be reduced for unpaid credit sales.');
        }

        $newQuantity = $request->new_quantity;
        $currentQuantity = $saleItem->quantity;

        // Validate new quantity
        if ($newQuantity >= $currentQuantity) {
            return redirect()->back()->with('error', 'New quantity must be less than current quantity.');
        }

        // ✅ If quantity is 0, delete the item instead
        if ($newQuantity == 0) {
            return $this->deleteCreditSaleItem($saleId, $saleItemId);
        }

        DB::transaction(function () use ($sale, $saleItem, $newQuantity, $currentQuantity) {
            $quantityDifference = $currentQuantity - $newQuantity;
            
            // 1. Restore inventory for reduced quantity
            $purchase = $saleItem->purchase;
            $purchase->decrement('sold_quantity', $quantityDifference);

            // 2. Calculate new item totals based on new quantity
            $unitPrice = $saleItem->price;
            $newItemTotal = $unitPrice * $newQuantity;
            
            // Calculate new discount amount proportionally
            $newDiscountAmount = 0;
            if ($saleItem->discount_type === 'percentage' && $saleItem->discount_value > 0) {
                $newDiscountAmount = ($newItemTotal * $saleItem->discount_value) / 100;
            } elseif ($saleItem->discount_type === 'amount') {
                // Proportionally reduce amount discount
                $newDiscountAmount = ($saleItem->discount_amount * $newQuantity) / $currentQuantity;
            }
            
            $newTotalAfterDiscount = $newItemTotal - $newDiscountAmount;

            // 3. Update sale item
            $saleItem->update([
                'quantity' => $newQuantity,
                'total' => $newItemTotal,
                'discount_amount' => $newDiscountAmount,
                'total_after_discount' => $newTotalAfterDiscount
            ]);

            // 🎯 STORE OLD BALANCE BEFORE RECALCULATION
    $oldRemainingBalance = $sale->remaining_balance;
            // 4. Recalculate sale totals
$allSaleItems = Sale_item::where('sale_id', $sale->id)->get();
$newSubtotal = $allSaleItems->sum('total_after_discount'); // ✅ Changed from 'total' to 'total_after_discount'

// Recalculate sale-level discount
$newSaleDiscountAmount = 0;
if ($sale->discount_type === 'percentage' && $sale->discount > 0) {
    // Use subtotal before item discounts for percentage calculation
    // $subtotalBeforeItemDiscounts = $allSaleItems->sum('total');
    // $newSaleDiscountAmount = ($subtotalBeforeItemDiscounts * $sale->discount) / 100;
    $newSaleDiscountAmount = ($newSubtotal * $sale->discount) / 100;
} elseif ($sale->discount_type === 'amount') {
    // Keep the same amount discount, but not more than subtotal
    $newSaleDiscountAmount = min($sale->discount, $newSubtotal);
}

// Calculate new grand total
$newGrandTotal = $newSubtotal - $newSaleDiscountAmount + $sale->tax;

$paidAmount = $sale->grand_total - $oldRemainingBalance;
$newRemainingBalance = max(0, $newGrandTotal - $paidAmount);

// Update sale totals
$sale->update([
    'subtotal' => $newSubtotal, // This will now be 14,500 instead of 15,000
    'discount_amount' => $newSaleDiscountAmount,
    'grand_total' => $newGrandTotal,
    'remaining_balance' => $newRemainingBalance
]);

$balanceDifference = $oldRemainingBalance - $newRemainingBalance;

if ($balanceDifference != 0 && $sale->customer) {
    $sale->customer->decrement('current_balance', $balanceDifference);
}
        });
        return redirect()->back()->with('success', "Quantity reduced successfully. Item quantity changed from {$currentQuantity} to {$newQuantity}.");

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error reducing quantity: ' . $e->getMessage());
    }
} 

public function markAsPaid(Request $request, $id)
{
    $sale = Sale::findOrFail($id);

    if ($sale->status === 'paid') {
        return redirect()->back()->with('info', 'This sale is already marked as paid.');
    }

    DB::transaction(function () use ($sale) {
        // Calculate already paid amount
        $alreadyPaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $remainingToPay = $sale->grand_total - $alreadyPaid;

        $payment = null; // avoid undefined variable error

        // If something is still due, add the difference as a new payment
        if ($remainingToPay > 0) {
            $payment = Payment::create([
                'sale_id' => $sale->id,
                'amount' => $remainingToPay,
                'method' => 'cash',
                'payment_date' => now()
            ]);
        }

        // 🎯 STORE OLD BALANCE FIRST
        $oldRemainingBalance = $sale->remaining_balance;

        // Update sale status
        $sale->update([
            'status' => 'paid',
            'remaining_balance' => 0,
            'received_amount' => $sale->grand_total
        ]);

        // Reduce customer balance when marking as paid
        if ($sale->customer && $oldRemainingBalance > 0) {
            $sale->customer->decrement('current_balance', $oldRemainingBalance);
        }

        // ✅ Determine final payment method
        $methods = Payment::where('sale_id', $sale->id)->pluck('method');

        if ($methods->isEmpty()) {
            $finalMethod = 'cash';
        } else {
            $methodCounts = array_count_values($methods->toArray());
            arsort($methodCounts);

            $mostUsedMethods = array_keys($methodCounts);
            $finalMethod = null;

            if (count($methodCounts) === 1) {
                $finalMethod = $mostUsedMethods[0];
            } else {
                $maxCount = reset($methodCounts);
                $topMethods = array_keys(array_filter($methodCounts, fn($count) => $count === $maxCount));

                if (count($topMethods) === 1) {
                    $finalMethod = $topMethods[0];
                } else {
                    $finalMethod = $payment?->method ?? 'cash';
                }
            }
        }

        // ✅ Update sale payment_type
        $sale->update(['payment_type' => $finalMethod]);

        // ✅ Update customer type
        if ($sale->customer) {
            $hasCreditSales = Sale::where('customer_id', $sale->customer_id)
                ->where('payment_type', 'credit')
                ->where('id', '!=', $sale->id)
                ->exists();

            if (!$hasCreditSales) {
                $sale->customer->update(['customer_type' => $finalMethod]);
            }
        }

        // ✅ Clear due_date if exists
        if ($sale->due_date) {
            $sale->update(['due_date' => null]);
        }
    });

    return redirect()->back()->with('success', 'Sale marked as paid successfully!');
}




public function addPayment(Request $request)
{
    $request->validate([
        'sale_id' => 'required|exists:sales,id',
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'required|in:cash,card'
    ]);

    $sale = Sale::findOrFail($request->sale_id);

    if ($sale->status === 'paid') {
        return back()->with('info', 'This sale is already fully paid.');
    }

    // Prevent overpaying
    if ($request->amount > $sale->remaining_balance) {
        return back()->withErrors(['amount' => 'Amount cannot exceed remaining balance of Rs ' . number_format($sale->remaining_balance, 2)]);
    }

    DB::transaction(function () use ($sale, $request) {
        // Create new payment record
        $payment = Payment::create([
            'sale_id' => $sale->id,
            'amount' => $request->amount,
            'method' => $request->payment_method,
            'payment_date' => now()
        ]);
        // Reduce customer balance
if ($sale->customer) {
    $sale->customer->decrement('current_balance', $request->amount);
}
        // Update remaining balance
        $newBalance = $sale->remaining_balance - $request->amount;
        $status = $newBalance <= 0 ? 'paid' : 'partial';

        $sale->update([
            'remaining_balance' => max($newBalance, 0),
            'status' => $status,
            'received_amount'=> $sale->grand_total
        ]);

        // If fully paid, determine final payment_type
        if ($status === 'paid') {
            $methods = Payment::where('sale_id', $sale->id)->pluck('method');
            $methodCounts = array_count_values($methods->toArray());
            arsort($methodCounts);

            $mostUsedMethods = array_keys($methodCounts);
            $finalMethod = null;

            if (count($methodCounts) === 1) {
                $finalMethod = $mostUsedMethods[0];
            } else {
                $maxCount = reset($methodCounts);
                $topMethods = array_keys(array_filter($methodCounts, function($count) use ($maxCount) {
                    return $count === $maxCount;
                }));

                if (count($topMethods) === 1) {
                    $finalMethod = $topMethods[0];
                } else {
                    $finalMethod = $payment->method; // Last one
                }
            }

            // ✅ Update sale payment_type
            $sale->update(['payment_type' => $finalMethod]);

            // ✅ Update customer type to match final payment method
           // ✅ Keep customer as 'credit' if they ever had credit sales
if ($sale->customer) {
    // Only downgrade if they have NO other credit sales
    $hasCreditSales = Sale::where('customer_id', $sale->customer_id)
        ->where('payment_type', 'credit')
        ->where('id', '!=', $sale->id) // Exclude current sale
        ->exists();
    
    if (!$hasCreditSales) {
        // No other credit sales exist, safe to change type
        $sale->customer->update(['customer_type' => $finalMethod]);
    }
    // If credit sales exist, keep customer_type as 'credit'
}

            //update due_date
            if($sale->due_date){
                $sale->update(['due_date'=>null]);
            }
        }
    });

    return back()->with('success', 'Payment added successfully!');
}

public function getCreditSalesNotifications()
{
    $today = now()->format('Y-m-d');
    
    // Get credit sales that are due within 7 days or overdue
    $notifications = DB::table('sales')
        ->join('customers', 'sales.customer_id', '=', 'customers.id')
        ->where('sales.payment_type', 'credit')
        ->where('sales.status', '!=', 'paid')
        ->whereNotNull('sales.due_date')
        ->where('sales.due_date', '<=', now()->addDays(7)->format('Y-m-d')) // Show 7 days ahead
        ->select([
            'sales.voucher_no',
            'customers.name as customer_name',
            'customers.contact',
            'customers.city',
            'sales.grand_total',
            'sales.due_date',
            'sales.remaining_balance',
            DB::raw("DATEDIFF(sales.due_date, CURDATE()) as days_difference")
        ])
        ->orderBy('sales.due_date', 'asc')
        ->get();

    return response()->json($notifications);
}

/**
 * Get customer's current balance for display on receipt page
 */
public function getCustomerBalance($customerId)
{
    try {
        $customer = Customer::findOrFail($customerId);
        
        return response()->json([
            'success' => true,
            'current_balance' => $customer->current_balance,
            'customer_name' => $customer->name,
            'shop_name' => $customer->shop_name,
            'city' => $customer->city,
            'contact' => $customer->contact
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Customer not found'
        ], 404);
    }
}

}
