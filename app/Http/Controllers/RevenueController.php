<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use DB;

class RevenueController extends Controller
{
    public function index(Request $request)
{
    // Date range filter
    $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
    $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();

    // Sales within date range
    $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

    // ✅ FIXED: Calculate total returns using correct proportional method
    $totalReturnsAmount = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->select('sale_returns.*', 'sales.payment_type', 'sales.grand_total', 'sales.remaining_balance', 'sales.subtotal', 'sales.discount_amount')
        ->get()
        ->sum(function ($return) {
            // Use correct proportional method: for credit sales count only paid portion
            if ($return->payment_type === 'credit') {
                $paidAmount = $return->grand_total - $return->remaining_balance;
                if ($paidAmount > 0 && $return->grand_total > 0) {
                    $paidRatio = $paidAmount / $return->grand_total;
                    return $return->total_return_amount * $paidRatio;
                }
                return 0;
            }
            // For non-credit (cash/card), count the actual return amount
            return $return->total_return_amount;
        });

    // KPIs - Cash-based accounting (only paid sales count as revenue)
    $grossRevenue = $sales->where('payment_type', '!=', 'credit')->sum('grand_total'); // Only cash/card sales

    // Add paid portions of credit sales to revenue
    $paidCreditRevenue = 0;
    $creditSales = $sales->where('payment_type', 'credit');
    foreach ($creditSales as $creditSale) {
        $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
        $paidCreditRevenue += max(0, $paidAmount);
    }
    $grossRevenue += $paidCreditRevenue;

    // Net revenue after returns
    $totalRevenue = max(0, $grossRevenue - $totalReturnsAmount);

    // Calculate total credit pending (unpaid amounts only)
    $totalCredit = $sales->where('payment_type', 'credit')->sum('remaining_balance');

    // ✅ FIXED: Adjust credit for returns using actual refund amounts on credit sales
    $creditSalesWithReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
        ->where('sales.payment_type', 'credit')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->select('sale_returns.*', 'sales.subtotal', 'sales.discount_amount', 'sales.grand_total')
        ->sum('sale_returns.total_return_amount');

    $totalCredit = max(0, $totalCredit - $creditSalesWithReturns);

    // Calculate total payments received
    $grossPayments = Payment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount');

    // For credit sales with returns, we don't refund cash - we just reduce the debt
    // So total paid should be the actual payments received, not reduced by returns
    $totalPaid = $grossPayments;

    // Total Purchases (cost of goods) - only for items that generated revenue
    $totalPurchaseCost = $this->calculateCashBasedPurchaseCost($startDate, $endDate);

    // All purchases
    $totalAllPurchases = Purchase::whereBetween('created_at', [$startDate, $endDate])
        ->sum(DB::raw('quantity * purchased_price'));

    // Calculate available inventory cost
    $availableInventoryCost = $this->calculateAvailableInventoryCost($startDate, $endDate);

    // Profit calculation (net revenue - net purchase cost)
    $totalProfit = $totalRevenue - $totalPurchaseCost;
    // Net profit
    $totalExpenses = Expense::whereBetween('date', [$startDate, $endDate])
    ->sum('amount');
    $netProfit = $totalRevenue - $totalPurchaseCost - $totalExpenses;
    // KPIs: margins
    $grossMargin = $totalRevenue > 0 ? (($totalRevenue - $totalPurchaseCost) / $totalRevenue) * 100 : 0;
    $netMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

    // ✅ FIXED: Count invoices correctly (cash/card and credit paid portion)
    $totalInvoices = 0;

    // Cash/Card sales: count when not fully returned
    $cashCardSales = $sales->where('payment_type', '!=', 'credit');
    foreach ($cashCardSales as $sale) {
        $saleReturns = SaleReturn::where('sale_id', $sale->id)->sum('total_return_amount');
        if ($sale->grand_total > $saleReturns) {
            $totalInvoices++;
        }
    }

    // Credit sales: count only if there is paid portion and net positive after returns
    $creditSales = $sales->where('payment_type', 'credit');
    foreach ($creditSales as $creditSale) {
        if ($creditSale->remaining_balance < $creditSale->grand_total) {
            $saleReturns = SaleReturn::where('sale_id', $creditSale->id)->sum('total_return_amount');
            $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
            if ($creditSale->grand_total > 0) {
                $paidRatio = $paidAmount / $creditSale->grand_total;
            } else {
                $paidRatio = 0;
            }
            $effectiveReturnImpact = $saleReturns * $paidRatio;

            if ($paidAmount > $effectiveReturnImpact) {
                $totalInvoices++;
            }
        }
    }

    // Average Order Value
    $averageOrderValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;

    // Number of Items Sold
    $totalItemsSold = $this->calculateTotalItemsSold($startDate, $endDate);

    // Payment Collection Ratio
    $paymentCollectionRatio = $grossRevenue > 0 ? ($totalPaid / $grossRevenue) * 100 : 0;

    // Outstanding Credits by Customer
    $outstandingCredits = $this->getOutstandingCreditsByCustomer();

    // New/Returning Customers
    $customerTypes = $this->getNewVsCreditCustomers($startDate, $endDate);

    // Profit by Category
    $profitByCategory = $this->getProfitByCategory($startDate, $endDate);

    // Tax Collected (support both possible column names: tax OR tax_amount)
    $totalTaxCollected = $sales->sum(function ($s) {
        return $s->tax ?? $s->tax_amount ?? 0;
    });

    // Low inventory alerts
    $lowInventoryAlerts = $this->getLowInventoryAlerts();

    // FIXED: All charts now use cash-based accounting
    $salesByPaymentType = $this->getSalesByPaymentTypeCashBased($startDate, $endDate);
    $dailyRevenue = $this->getDailyRevenueCashBased($startDate, $endDate);
    $monthlyRevenue = $this->getMonthlyRevenueCashBased();
    $revenueByCategory = $this->getRevenueByCategoryCashBased($startDate, $endDate);
    $revenueByCustomerType = $this->getRevenueByCustomerTypeCashBased($startDate, $endDate);
    $topProducts = $this->getTopProductsCashBased($startDate, $endDate);
    $topCustomers = $this->getTopCustomersCashBased($startDate, $endDate);

    return view('pages.dashboard.dashboard_revenue', compact(
        'totalRevenue', 'totalCredit', 'totalPaid', 'totalPurchaseCost', 'totalAllPurchases', 'totalProfit','netProfit',
        'salesByPaymentType', 'dailyRevenue', 'monthlyRevenue', 'revenueByCategory',
        'revenueByCustomerType', 'topProducts', 'topCustomers', 'startDate', 'endDate',
        'grossRevenue', 'totalReturnsAmount', 'grossPayments', 'availableInventoryCost',
        'grossMargin', 'netMargin', 'totalInvoices', 'averageOrderValue', 'totalItemsSold',
        'paymentCollectionRatio', 'outstandingCredits', 'customerTypes', 'profitByCategory',
        'totalTaxCollected', 'lowInventoryAlerts'
    ));
}


// ✅ FINAL MERGED FUNCTION
private function getSalesByPaymentTypeCashBased($startDate, $endDate)
{
    // Only include cash/card sales as full revenue
    $salesByPaymentType = Sale::select('payment_type', DB::raw('SUM(grand_total) as total'))
        ->where('payment_type', '!=', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('payment_type')
        ->get();

    // Add only PAID portions of credit sales
    $paidCreditAmount = 0;
    $creditSales = Sale::where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
                 
    foreach ($creditSales as $creditSale) {
        $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
        $paidCreditAmount += $paidAmount;
    }
             
    if ($paidCreditAmount > 0) {
        $salesByPaymentType->push((object)[
            'payment_type' => 'credit (paid)',
            'total' => $paidCreditAmount
        ]);
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts (no discount ratio)
    foreach ($salesByPaymentType as $paymentType) {
        $paymentTypeForQuery = str_replace(' (paid)', '', $paymentType->payment_type);
                     
        if ($paymentTypeForQuery == 'credit') {
            // For credit (paid), adjust only the paid portion
            $returnsForType = 0;
            $creditReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
                ->where('sales.payment_type', 'credit')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
                ->get();
                             
            foreach ($creditReturns as $return) {
                $paidAmount = $return->grand_total - $return->remaining_balance;
                if ($paidAmount > 0) {
                    $paidRatio = $paidAmount / $return->grand_total;
                    $returnsForType += $return->total_return_amount * $paidRatio;
                }
            }
        } else {
            // For cash/card sales, just subtract actual return amounts
            $returnsForType = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
                ->where('sales.payment_type', $paymentTypeForQuery)
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->sum('sale_returns.total_return_amount');
        }
                     
        $paymentType->total = max(0, $paymentType->total - $returnsForType);
    }

    return $salesByPaymentType->filter(function($item) {
        return $item->total > 0;
    });
}



private function getDailyRevenueCashBased($startDate, $endDate)
{
    $dailyRevenue = Sale::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
        ->where('payment_type', '!=', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->keyBy('date');

    $creditSalesByDate = Sale::select(DB::raw('DATE(created_at) as date'))
        ->where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->get();

    foreach ($creditSalesByDate as $dateGroup) {
        $creditSalesForDate = Sale::where('payment_type', 'credit')
            ->whereDate('created_at', $dateGroup->date)
            ->get();
                         
        $paidAmountForDate = 0;
        foreach ($creditSalesForDate as $creditSale) {
            $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
            $paidAmountForDate += $paidAmount;
        }
                     
        if ($paidAmountForDate > 0) {
            if (isset($dailyRevenue[$dateGroup->date])) {
                $dailyRevenue[$dateGroup->date]->total += $paidAmountForDate;
            } else {
                $dailyRevenue[$dateGroup->date] = (object)[
                    'date' => $dateGroup->date,
                    'total' => $paidAmountForDate
                ];
            }
        }
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts
    foreach ($dailyRevenue as $day) {
        $cashCardReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', '!=', 'credit')
            ->whereDate('sales.created_at', $day->date)
            ->sum('sale_returns.total_return_amount');
                         
        $creditReturns = 0;
        $creditReturnData = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', 'credit')
            ->whereDate('sales.created_at', $day->date)
            ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
                         
        foreach ($creditReturnData as $return) {
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $creditReturns += $return->total_return_amount * $paidRatio;
            }
        }
                     
        $day->total = max(0, $day->total - $cashCardReturns - $creditReturns);
    }

    return collect($dailyRevenue->values())->filter(function($item) {
        return $item->total > 0;
    })->sortBy('date');
}

private function getMonthlyRevenueCashBased()
{
    $monthlyRevenue = Sale::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('SUM(grand_total) as total'))
        ->where('payment_type', '!=', 'credit')
        ->whereYear('created_at', Carbon::now()->year)
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->keyBy('month');

    $creditSalesByMonth = Sale::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'))
        ->where('payment_type', 'credit')
        ->whereYear('created_at', Carbon::now()->year)
        ->groupBy('month')
        ->get();

    foreach ($creditSalesByMonth as $monthGroup) {
        $creditSalesForMonth = Sale::where('payment_type', 'credit')
            ->where(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), $monthGroup->month)
            ->get();
                         
        $paidAmountForMonth = 0;
        foreach ($creditSalesForMonth as $creditSale) {
            $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
            $paidAmountForMonth += $paidAmount;
        }
                     
        if ($paidAmountForMonth > 0) {
            if (isset($monthlyRevenue[$monthGroup->month])) {
                $monthlyRevenue[$monthGroup->month]->total += $paidAmountForMonth;
            } else {
                $monthlyRevenue[$monthGroup->month] = (object)[
                    'month' => $monthGroup->month,
                    'total' => $paidAmountForMonth
                ];
            }
        }
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts
    foreach ($monthlyRevenue as $month) {
        $cashCardReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', '!=', 'credit')
            ->where(DB::raw('DATE_FORMAT(sales.created_at, "%Y-%m")'), $month->month)
            ->sum('sale_returns.total_return_amount');
                         
        $creditReturns = 0;
        $creditReturnData = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', 'credit')
            ->where(DB::raw('DATE_FORMAT(sales.created_at, "%Y-%m")'), $month->month)
            ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
                         
        foreach ($creditReturnData as $return) {
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $creditReturns += $return->total_return_amount * $paidRatio;
            }
        }
                     
        $month->total = max(0, $month->total - $cashCardReturns - $creditReturns);
    }

    return collect($monthlyRevenue->values())->filter(function($item) {
        return $item->total > 0;
    })->sortBy('month');
}

// ✅ FIXED: Revenue by category
private function getRevenueByCategoryCashBased($startDate, $endDate)
{
    $revenueByCategory = collect();

    // Get revenue from cash/card sales by category
    $cashCardRevenue = collect();
    $cashCardSales = Sale::where('payment_type', '!=', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

    foreach ($cashCardSales as $sale) {
        $saleItems = DB::table('sale_items')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->join('categories', 'purchases.category_id', '=', 'categories.id')
            ->where('sale_items.sale_id', $sale->id)
            ->select('categories.name as category', 'sale_items.total_after_discount')
            ->get();

        // ✅ FIXED: Use actual payment ratio calculation
        $saleSubtotal = $saleItems->sum('total_after_discount');
        $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

        foreach ($saleItems as $item) {
            $finalAmount = $item->total_after_discount * $discountRatio;
                     
            if (!$cashCardRevenue->has($item->category)) {
                $cashCardRevenue->put($item->category, (object)['category' => $item->category, 'total' => 0]);
            }
                     
            $current = $cashCardRevenue->get($item->category);
            $current->total += $finalAmount;
            $cashCardRevenue->put($item->category, $current);
        }
    }

    $cashCardRevenue = $cashCardRevenue->keyBy('category');

    // Add paid portions of credit sales by category
    $creditSales = Sale::where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

    $creditRevenueByCategory = collect();
    foreach ($creditSales as $creditSale) {
        if ($creditSale->remaining_balance < $creditSale->grand_total) {
            $paidRatio = ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total;
                             
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->join('categories', 'purchases.category_id', '=', 'categories.id')
                ->where('sale_items.sale_id', $creditSale->id)
                ->select('categories.name as category', 'sale_items.total_after_discount')
                ->get();
            
            // Calculate discount ratio for this credit sale
            $saleSubtotal = $saleItems->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $creditSale->grand_total / $saleSubtotal : 1;
                             
            foreach ($saleItems as $item) {
                $finalAmount = $item->total_after_discount * $discountRatio;
                $paidAmount = $finalAmount * $paidRatio;
                if (!$creditRevenueByCategory->has($item->category)) {
                    $creditRevenueByCategory->put($item->category, 0);
                }
                $creditRevenueByCategory->put($item->category, $creditRevenueByCategory->get($item->category) + $paidAmount);
            }
        }
    }

    // Combine cash/card and paid credit revenues
    $allCategories = $cashCardRevenue->keys()->merge($creditRevenueByCategory->keys())->unique();
             
    foreach ($allCategories as $category) {
        $cashAmount = $cashCardRevenue->has($category) ? $cashCardRevenue[$category]->total : 0;
        $creditAmount = $creditRevenueByCategory->get($category, 0);
                     
        $revenueByCategory->push((object)[
            'category' => $category,
            'total' => $cashAmount + $creditAmount
        ]);
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts
    foreach ($revenueByCategory as $category) {
        // Returns from cash/card sales - use actual amount_refunded
        $cashCardReturns = DB::table('sale_return_items')
            ->join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->join('categories', 'purchases.category_id', '=', 'categories.id')
            ->where('categories.name', $category->category)
            ->where('sales.payment_type', '!=', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum('sale_return_items.amount_refunded');
                         
        // Returns from credit sales (only from paid portions)
        $creditReturns = 0;
        $creditReturnData = DB::table('sale_return_items')
            ->join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->join('categories', 'purchases.category_id', '=', 'categories.id')
            ->where('categories.name', $category->category)
            ->where('sales.payment_type', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('sale_return_items.amount_refunded', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
                         
        foreach ($creditReturnData as $return) {
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $creditReturns += $return->amount_refunded * $paidRatio;
            }
        }
                     
        $category->total = max(0, $category->total - $cashCardReturns - $creditReturns);
    }

    return $revenueByCategory->filter(function($item) {
        return $item->total > 0;
    });
}


private function getRevenueByCustomerTypeCashBased($startDate, $endDate)
{
    $revenueByCustomerType = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
        ->select('customers.customer_type', DB::raw('SUM(sales.grand_total) as total'))
        ->where('sales.payment_type', '!=', 'credit')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->groupBy('customers.customer_type')
        ->get()
        ->keyBy('customer_type');

    // Add paid portions of credit sales by customer type
    $creditSales = Sale::join('customers', 'sales.customer_id', '=', 'customers.id')
        ->where('sales.payment_type', 'credit')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->select('sales.*', 'customers.customer_type')
        ->get()
        ->groupBy('customer_type');

    foreach ($creditSales as $customerType => $sales) {
        $paidAmount = 0;
        foreach ($sales as $sale) {
            $paidAmount += $sale->grand_total - $sale->remaining_balance;
        }
                     
        if ($paidAmount > 0) {
            if ($revenueByCustomerType->has($customerType)) {
                $revenueByCustomerType[$customerType]->total += $paidAmount;
            } else {
                $revenueByCustomerType->put($customerType, (object)[
                    'customer_type' => $customerType,
                    'total' => $paidAmount
                ]);
            }
        }
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts
    foreach ($revenueByCustomerType as $customerType) {
        // Returns from cash/card sales
        $cashCardReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('customers.customer_type', $customerType->customer_type)
            ->where('sales.payment_type', '!=', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum('sale_returns.total_return_amount');
                         
        // Returns from credit sales (only from paid portions)
        $creditReturns = 0;
        $creditReturnData = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('customers.customer_type', $customerType->customer_type)
            ->where('sales.payment_type', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
                         
        foreach ($creditReturnData as $return) {
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $creditReturns += $return->total_return_amount * $paidRatio;
            }
        }
                     
        $customerType->total = max(0, $customerType->total - $cashCardReturns - $creditReturns);
    }

    return $revenueByCustomerType->filter(function($item) {
        return $item->total > 0;
    })->values();
}


    private function getTopProductsCashBased($startDate, $endDate)
    {
        $topProducts = collect();

        // Get products from cash/card sales
        $cashCardProducts = DB::table('sale_items')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select('purchases.product_name', 'purchases.unit', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->where('sales.payment_type', '!=', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('purchases.product_name', 'purchases.unit')
            ->get()
            ->keyBy(function($item) { return $item->product_name . '|' . $item->unit; });

        // Add quantities from paid portions of credit sales
        $creditSales = Sale::where('payment_type', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $creditProductQty = collect();
        foreach ($creditSales as $creditSale) {
            if ($creditSale->remaining_balance < $creditSale->grand_total) {
                $paidRatio = ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total;
                
                $saleItems = DB::table('sale_items')
                    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                    ->where('sale_items.sale_id', $creditSale->id)
                    ->select('purchases.product_name', 'purchases.unit', 'sale_items.quantity')
                    ->get();
                
                foreach ($saleItems as $item) {
                    $paidQuantity = $item->quantity * $paidRatio;
                    $key = $item->product_name . '|' . $item->unit;
                    
                    if (!$creditProductQty->has($key)) {
                        $creditProductQty->put($key, [
                            'product_name' => $item->product_name,
                            'unit' => $item->unit,
                            'total_qty' => 0
                        ]);
                    }
                    
                    // Fix: Get current data, modify, then put back
                    $currentData = $creditProductQty->get($key);
                    $currentData['total_qty'] += $paidQuantity;
                    $creditProductQty->put($key, $currentData);
                }
            }
        }

        // Combine cash/card and paid credit quantities
        $allProductKeys = $cashCardProducts->keys()->merge($creditProductQty->keys())->unique();
        
        foreach ($allProductKeys as $key) {
            $parts = explode('|', $key);
            $productName = $parts[0];
            $unit = $parts[1];
            
            $cashQty = $cashCardProducts->has($key) ? $cashCardProducts[$key]->total_qty : 0;
            $creditQty = $creditProductQty->has($key) ? $creditProductQty[$key]['total_qty'] : 0;
            
            $topProducts->push((object)[
                'product_name' => $productName,
                'unit' => $unit,
                'total_qty' => $cashQty + $creditQty
            ]);
        }

        // Adjust for returns (only cash-based returns)
        foreach ($topProducts as $product) {
            // Returns from cash/card sales
            $cashCardReturns = DB::table('sale_return_items')
                ->join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->where('purchases.product_name', $product->product_name)
                ->where('purchases.unit', $product->unit)
                ->where('sales.payment_type', '!=', 'credit')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->sum('sale_return_items.quantity_returned');
                
            // Returns from credit sales (only from paid portions)
            $creditReturns = 0;
            $creditReturnData = DB::table('sale_return_items')
                ->join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->where('purchases.product_name', $product->product_name)
                ->where('purchases.unit', $product->unit)
                ->where('sales.payment_type', 'credit')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->select('sale_return_items.quantity_returned', 'sales.grand_total', 'sales.remaining_balance')
                ->get();
                
            foreach ($creditReturnData as $return) {
                $paidAmount = $return->grand_total - $return->remaining_balance;
                if ($paidAmount > 0) {
                    $paidRatio = $paidAmount / $return->grand_total;
                    $creditReturns += $return->quantity_returned * $paidRatio;
                }
            }
            
            $product->total_qty = max(0, $product->total_qty - $cashCardReturns - $creditReturns);
        }

        return $topProducts->filter(function($item) {
            return $item->total_qty > 0;
        })->sortByDesc('total_qty')->take(5)->values();
    }

    private function getTopCustomersCashBased($startDate, $endDate)
{
    // Get spending from cash/card sales
    $topCustomers = Sale::select('customer_id', DB::raw('SUM(grand_total) as total_spent'))
        ->where('payment_type', '!=', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('customer_id')
        ->with('customer')
        ->get()
        ->keyBy('customer_id');

    // Add paid portions from credit sales
    $creditSales = Sale::select('customer_id', 'grand_total', 'remaining_balance')
        ->where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get()
        ->groupBy('customer_id');

    foreach ($creditSales as $customerId => $sales) {
        $paidAmount = 0;
        foreach ($sales as $sale) {
            $paidAmount += $sale->grand_total - $sale->remaining_balance;
        }
                     
        if ($paidAmount > 0) {
            if ($topCustomers->has($customerId)) {
                $topCustomers[$customerId]->total_spent += $paidAmount;
            } else {
                $customer = Sale::where('customer_id', $customerId)->with('customer')->first();
                $topCustomers->put($customerId, (object)[
                    'customer_id' => $customerId,
                    'total_spent' => $paidAmount,
                    'customer' => $customer ? $customer->customer : null
                ]);
            }
        }
    }

    // ✅ FIXED: Adjust for returns using actual refund amounts
    foreach ($topCustomers as $customer) {
        // Returns from cash/card sales
        $cashCardReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.customer_id', $customer->customer_id)
            ->where('sales.payment_type', '!=', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum('sale_returns.total_return_amount');
                         
        // Returns from credit sales (only from paid portions)
        $creditReturns = 0;
        $creditReturnData = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.customer_id', $customer->customer_id)
            ->where('sales.payment_type', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
                         
        foreach ($creditReturnData as $return) {
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $creditReturns += $return->total_return_amount * $paidRatio;
            }
        }
                     
        $customer->total_spent = max(0, $customer->total_spent - $cashCardReturns - $creditReturns);
    }

    return $topCustomers->filter(function($item) {
        return $item->total_spent > 0;
    })->sortByDesc('total_spent')->take(5)->values();
}


    private function calculateCashBasedReturnsAmount($startDate, $endDate)
    {
        $totalReturns = 0;
        
        // Get returns for cash/card sales (immediate revenue impact)
        $cashCardReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', '!=', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum('sale_returns.total_return_amount');
        
        $totalReturns += $cashCardReturns;
        
        // Get returns from credit sales, but only count proportionally for PAID portions
        $creditSalesWithReturns = SaleReturn::join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sales.payment_type', 'credit')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('sale_returns.*', 'sales.grand_total', 'sales.remaining_balance')
            ->get();
            
        foreach ($creditSalesWithReturns as $return) {
            // Only count returns that affect revenue (from paid portions)
            $paidAmount = $return->grand_total - $return->remaining_balance;
            if ($paidAmount > 0) {
                $paidRatio = $paidAmount / $return->grand_total;
                $effectiveReturn = $return->total_return_amount * $paidRatio;
                $totalReturns += $effectiveReturn;
            }
        }
        
        return $totalReturns;
    }

    private function calculateCashBasedPurchaseCost($startDate, $endDate)
    {
        $totalCost = 0;
        
        // Get cash/card sales (immediate revenue)
        $paidSales = Sale::where('payment_type', '!=', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        foreach ($paidSales as $sale) {
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->where('sale_items.sale_id', $sale->id)
                ->select('sale_items.id', 'sale_items.quantity', 'purchases.purchased_price')
                ->get();
                
            foreach ($saleItems as $item) {
                // Get returned quantity for this item
                $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity_returned');
                $netQuantity = $item->quantity - $returnedQty;
                $totalCost += $netQuantity * $item->purchased_price;
            }
        }
        
        // Add cost for PAID portions of credit sales only
        $creditSales = Sale::where('payment_type', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        foreach ($creditSales as $creditSale) {
            if ($creditSale->remaining_balance < $creditSale->grand_total) {
                // This credit sale has some payments
                $paidAmount = $creditSale->grand_total - $creditSale->remaining_balance;
                $paidRatio = $paidAmount / $creditSale->grand_total;
                
                $saleItems = DB::table('sale_items')
                    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                    ->where('sale_items.sale_id', $creditSale->id)
                    ->select('sale_items.id', 'sale_items.quantity', 'purchases.purchased_price')
                    ->get();
                
                foreach ($saleItems as $item) {
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity_returned');
                    $netQuantity = $item->quantity - $returnedQty;
                    $paidQuantity = $netQuantity * $paidRatio;
                    $totalCost += $paidQuantity * $item->purchased_price;
                }
            }
        }

        return max(0, $totalCost);
    }

    private function calculateAvailableInventoryCost($startDate, $endDate)
{
    // Start with total purchases
    $totalPurchases = Purchase::whereBetween('created_at', [$startDate, $endDate])
        ->sum(DB::raw('quantity * purchased_price'));
    
    // Subtract cost of cash/card sales (immediate sales)
    $cashCardSalesCost = 0;
    $cashCardSales = Sale::where('payment_type', '!=', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
    
    foreach ($cashCardSales as $sale) {
        $saleItems = DB::table('sale_items')
            ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
            ->where('sale_items.sale_id', $sale->id)
            ->select('sale_items.quantity', 'purchases.purchased_price')
            ->get();
        
        foreach ($saleItems as $item) {
            $cashCardSalesCost += $item->quantity * $item->purchased_price;
        }
    }
    
    // Subtract cost of PAID portions of credit sales only
    $paidCreditSalesCost = 0;
    $creditSales = Sale::where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
    
    foreach ($creditSales as $creditSale) {
        if ($creditSale->remaining_balance < $creditSale->grand_total) {
            $paidRatio = ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total;
            
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->where('sale_items.sale_id', $creditSale->id)
                ->select('sale_items.quantity', 'purchases.purchased_price')
                ->get();
            
            foreach ($saleItems as $item) {
                $paidQuantity = $item->quantity * $paidRatio;
                $paidCreditSalesCost += $paidQuantity * $item->purchased_price;
            }
        }
    }
    
    // Add back cost of returned items (they're back in inventory)
    $returnedItemsCost = 0;
    $allReturns = SaleReturnItem::join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->select('sale_return_items.quantity_returned', 'purchases.purchased_price')
        ->get();
    
    foreach ($allReturns as $return) {
        $returnedItemsCost += $return->quantity_returned * $return->purchased_price;
    }
    
    $availableCost = $totalPurchases - $cashCardSalesCost - $paidCreditSalesCost + $returnedItemsCost;
    
    return max(0, $availableCost);
}

private function calculateTotalItemsSold($startDate, $endDate)
{
    // Cash/card sales
    $cashCardItemsSold = DB::table('sale_items')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->where('sales.payment_type', '!=', 'credit')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->sum('sale_items.quantity');

    // Paid credit sales
    $creditItemsSold = 0;
    $creditSales = Sale::where('payment_type', 'credit')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

    foreach ($creditSales as $creditSale) {
        if ($creditSale->remaining_balance < $creditSale->grand_total) {
            $paidRatio = ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total;
            $saleItems = DB::table('sale_items')
                ->where('sale_id', $creditSale->id)
                ->sum('quantity');
            $creditItemsSold += $saleItems * $paidRatio;
        }
    }

    // Subtract returned items
    $returnedItems = SaleReturnItem::join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->sum('sale_return_items.quantity_returned');

    return max(0, $cashCardItemsSold + $creditItemsSold - $returnedItems);
}

private function getOutstandingCreditsByCustomer()
{
    return Sale::select('customer_id', DB::raw('SUM(remaining_balance) as outstanding'))
        ->where('payment_type', 'credit')
        ->where('remaining_balance', '>', 0)
        ->groupBy('customer_id')
        ->with('customer')
        ->orderByDesc('outstanding')
        ->take(5)
        ->get();
}


private function getNewVsCreditCustomers($startDate, $endDate) {
    // Ensure proper date formatting
    $startDate = Carbon::parse($startDate)->startOfDay();
    $endDate = Carbon::parse($endDate)->endOfDay();
    
    // Get unique customers who made purchases in this period
    $customerIds = Sale::whereBetween('created_at', [$startDate, $endDate])
        ->whereNotNull('customer_id')
        ->distinct()
        ->pluck('customer_id');

    $newCustomers = collect();
    $creditCustomers = collect();
    
    foreach ($customerIds as $customerId) {
        $customer = Customer::find($customerId);
        if (!$customer) continue;
        
        // Check if customer has net positive sales (after returns) in current period
        $hasValidSales = $this->customerHasValidSales($customerId, $startDate, $endDate);
        if (!$hasValidSales) continue;
        
        // Check if this is the customer's first ever visit
        $hadSalesBeforePeriod = Sale::where('customer_id', $customerId)
            ->where('created_at', '<', $startDate)
            ->exists();
        
        // Only process truly new customers
        if (!$hadSalesBeforePeriod) {
            // Check if customer EVER had unpaid credit (including current period)
            $hasEverHadCredit = false;
            if (Schema::hasColumn('sales', 'remaining_balance')) {
                $hasEverHadCredit = Sale::where('customer_id', $customerId)
                    ->where('remaining_balance', '>', 0)
                    ->exists();
            }
            
            if ($hasEverHadCredit) {
                $creditCustomers->put($customerId, $customer);
            } else {
                $newCustomers->put($customerId, $customer);
            }
        }
    }

    return (object) [
        'new' => $newCustomers->count(),
        'credit' => $creditCustomers->count(),
        'newCustomers' => $newCustomers->values(),
        'creditCustomers' => $creditCustomers->values()
    ];
}
// Simplified helper method to check if customer has valid sales
private function customerHasValidSales($customerId, $startDate, $endDate)
{
    $customerSales = Sale::where('customer_id', $customerId)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
    
    foreach ($customerSales as $sale) {
        // Check if SaleReturn table/relationship exists
        $totalReturns = 0;
        if (Schema::hasTable('sale_returns')) {
            $totalReturns = SaleReturn::where('sale_id', $sale->id)
                ->sum('total_return_amount') ?? 0;
        }
        
        $netSaleAmount = $sale->grand_total - $totalReturns;
        
        if ($netSaleAmount > 0) {
            return true;
        }
    }
    
    return false;
}



private function getProfitByCategory($startDate, $endDate)
{
    $profitByCategory = collect();
    
    $categories = DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->join('categories', 'purchases.category_id', '=', 'categories.id')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->select('categories.name as category')
        ->distinct()
        ->get();

    foreach ($categories as $category) {
        $categoryRevenue = 0;
        $categoryCost = 0;

        // CASH/CARD SALES REVENUE AND COST
        $cashCardSales = Sale::where('payment_type', '!=', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($cashCardSales as $sale) {
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->join('categories', 'purchases.category_id', '=', 'categories.id')
                ->where('sale_items.sale_id', $sale->id)
                ->where('categories.name', $category->category)
                ->select(
                    'sale_items.id',
                    'sale_items.total_after_discount', 
                    'purchases.purchased_price', 
                    'sale_items.quantity'
                )
                ->get();

            // Calculate discount ratio for this sale
            $saleSubtotal = DB::table('sale_items')->where('sale_id', $sale->id)->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

            foreach ($saleItems as $item) {
                // Calculate net quantity (after returns)
                $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity_returned');
                $netQuantity = $item->quantity - $returnedQty;
                
                if ($netQuantity > 0) {
                    // Revenue: proportional to net quantity with discount applied
                    $itemRevenue = ($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio;
                    $categoryRevenue += $itemRevenue;
                    
                    // Cost: only for net quantity sold
                    $categoryCost += $item->purchased_price * $netQuantity;
                }
            }
        }

        // CREDIT SALES REVENUE AND COST (PAID PORTIONS ONLY)
        $creditSales = Sale::where('payment_type', '=', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($creditSales as $creditSale) {
            if ($creditSale->remaining_balance < $creditSale->grand_total) {
                $paidRatio = ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total;

                $saleItems = DB::table('sale_items')
                    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                    ->join('categories', 'purchases.category_id', '=', 'categories.id')
                    ->where('sale_items.sale_id', $creditSale->id)
                    ->where('categories.name', $category->category)
                    ->select(
                        'sale_items.id',
                        'sale_items.total_after_discount', 
                        'purchases.purchased_price', 
                        'sale_items.quantity'
                    )
                    ->get();

                // Calculate discount ratio for this credit sale
                $saleSubtotal = DB::table('sale_items')->where('sale_id', $creditSale->id)->sum('total_after_discount');
                $discountRatio = $saleSubtotal > 0 ? $creditSale->grand_total / $saleSubtotal : 1;

                foreach ($saleItems as $item) {
                    // Calculate net quantity after returns
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)->sum('quantity_returned');
                    $netQuantity = $item->quantity - $returnedQty;
                    
                    if ($netQuantity > 0) {
                        // Revenue: paid portion of net revenue with discount applied
                        $itemRevenue = (($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio) * $paidRatio;
                        $categoryRevenue += $itemRevenue;
                        
                        // Cost: proportional to payment ratio
                        $itemCost = ($item->purchased_price * $netQuantity) * $paidRatio;
                        $categoryCost += $itemCost;
                    }
                }
            }
        }

        $finalRevenue = $categoryRevenue;
        $profit = $finalRevenue - $categoryCost;
        
        if ($profit != 0) {
            $profitByCategory->push((object)[
                'category' => $category->category,
                'profit' => $profit,
                'revenue' => $finalRevenue,
                'cost' => $categoryCost,
                'margin' => $finalRevenue > 0 ? ($profit / $finalRevenue) * 100 : 0
            ]);
        }
    }

    return $profitByCategory->sortByDesc('profit');
}

private function getLowInventoryAlerts()
{
    // This assumes you have a way to track current inventory
    // You might need to adjust based on your inventory tracking system
    return Purchase::select('product_name', 'unit', DB::raw('SUM(quantity) as total_purchased'))
        ->groupBy('product_name', 'unit')
        ->havingRaw('SUM(quantity) < 10') // Assuming 10 is low stock threshold
        ->orderBy('total_purchased')
        ->take(5)
        ->get();
}
}