<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Sale_item;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Category;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    /**
     * Display the financial report page
     */
    public function index(Request $request)
    {
        $dateRange = $request->date_range ?? 'this_month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        [$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);

        return view('pages.reports.financialReport', compact(
            'startDate', 'endDate', 'dateRange'
        ));
    }

    /**
     * Get comprehensive financial summary
     */
    public function getFinancialSummary(Request $request)
    {
        $dateRange = $request->date_range ?? 'this_month';
$startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
$endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

[$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);

        // 1. SALES REVENUE
        $salesRevenue = $this->calculateSalesRevenue($startDate, $endDate);

        // 2. COST OF GOODS SOLD
        $cogs = $this->calculateCOGS($startDate, $endDate);

        // 3. GROSS PROFIT
        $grossProfit = $salesRevenue['net_sales_revenue'] - $cogs['total_cogs'];
        $grossProfitMargin = $salesRevenue['net_sales_revenue'] > 0 
            ? ($grossProfit / $salesRevenue['net_sales_revenue']) * 100 
            : 0;

        // 4. OPERATING EXPENSES
        $operatingExpenses = $this->calculateOperatingExpenses($startDate, $endDate);

        // 5. OPERATING PROFIT
        $operatingProfit = $grossProfit - $operatingExpenses['total_expenses'];

        // 6. TAX INFORMATION
        $taxInfo = $this->calculateTaxInfo($startDate, $endDate);

        // 7. NET PROFIT
        $netProfit = $operatingProfit;
        $netProfitMargin = $salesRevenue['net_sales_revenue'] > 0 
            ? ($netProfit / $salesRevenue['net_sales_revenue']) * 100 
            : 0;

        // 8. FINANCIAL RATIOS
        $ratios = [
            'gross_profit_margin' => round($grossProfitMargin, 2),
            'net_profit_margin' => round($netProfitMargin, 2),
            'expense_ratio' => $salesRevenue['net_sales_revenue'] > 0 
                ? round(($operatingExpenses['total_expenses'] / $salesRevenue['net_sales_revenue']) * 100, 2) 
                : 0,
            'cogs_ratio' => $salesRevenue['net_sales_revenue'] > 0 
                ? round(($cogs['total_cogs'] / $salesRevenue['net_sales_revenue']) * 100, 2) 
                : 0,
        ];

        // 9. PAYMENT TYPE BREAKDOWN
        $paymentBreakdown = $this->getPaymentTypeBreakdown($startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $startDate->format('d M Y'),
                'end' => $endDate->format('d M Y'),
                'days' => $startDate->diffInDays($endDate) + 1
            ],
            'sales_revenue' => $salesRevenue,
            'cogs' => $cogs,
            'gross_profit' => [
                'amount' => round($grossProfit, 2),
                'margin_percentage' => round($grossProfitMargin, 2)
            ],
            'operating_expenses' => $operatingExpenses,
            'operating_profit' => round($operatingProfit, 2),
            'tax_info' => $taxInfo,
            'net_profit' => [
                'amount' => round($netProfit, 2),
                'margin_percentage' => round($netProfitMargin, 2)
            ],
            'financial_ratios' => $ratios,
            'payment_breakdown' => $paymentBreakdown
        ]);
    }

    /**
     * Calculate Sales Revenue with proper handling of discounts, returns, and credit sales
     */
    private function calculateSalesRevenue($startDate, $endDate)
{
    $grossSales = 0;
    $totalDiscounts = 0;
    $totalReturns = 0;
    $salesCount = 0;
    $itemsSold = 0;

    $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

    foreach ($sales as $sale) {
        $includeInRevenue = false;
        $revenueRatio = 0;

        if ($sale->payment_type !== 'credit') {
            $includeInRevenue = true;
            $revenueRatio = 1;
        } else {
            $paidAmount = $sale->grand_total - $sale->remaining_balance;
            if ($paidAmount > 0) {
                $includeInRevenue = true;
                $revenueRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
            }
        }

        if ($includeInRevenue) {
            // ✅ 1. Gross revenue = sale’s grand total before returns
            $grossSales += $sale->grand_total * $revenueRatio;

            // ✅ 2. Get total returned amount
            $returnAmount = SaleReturn::where('sale_id', $sale->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_return_amount');

            // ✅ 3. Only subtract return amount from gross — not discount
            $totalReturns += $returnAmount * $revenueRatio;

            // ✅ 4. Calculate discount ONLY for non-returned portion
            // Find total sale discount proportionally to items not returned
            $totalSaleQty = Sale_item::where('sale_id', $sale->id)->sum('quantity');
            $totalReturnedQty = SaleReturnItem::whereIn(
                'sale_item_id',
                Sale_item::where('sale_id', $sale->id)->pluck('id')
            )->sum('quantity_returned');

            $returnedRatio = $totalSaleQty > 0 ? $totalReturnedQty / $totalSaleQty : 0;
            $activeDiscount = $sale->discount_amount * (1 - $returnedRatio);

            $totalDiscounts += $activeDiscount * $revenueRatio;

            // ✅ 5. Count items sold (net of returns)
            $saleItems = Sale_item::where('sale_id', $sale->id)->get();
            foreach ($saleItems as $item) {
                $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('quantity_returned');
                
                $netQty = $item->quantity - $returnedQty;
                if ($netQty > 0) {
                    $itemsSold += $netQty * $revenueRatio;
                }
            }

            $salesCount++;
        }
    }

    // ✅ Final: Discount is shown, but not applied to returned sales
    $netSalesRevenue = $grossSales - $totalReturns;

    return [
        'gross_sales' => round($grossSales, 2),
        'total_discounts' => round($totalDiscounts, 2),
        'total_returns' => round($totalReturns, 2),
        'net_sales_revenue' => round($netSalesRevenue, 2),
        'sales_count' => $salesCount,
        'items_sold' => round($itemsSold, 2),
        'avg_transaction_value' => $salesCount > 0 ? round($netSalesRevenue / $salesCount, 2) : 0
    ];
}
    

    /**
     * Calculate Cost of Goods Sold
     */
    private function calculateCOGS($startDate, $endDate)
    {
        $totalCost = 0;

        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        foreach ($sales as $sale) {
            // Determine revenue ratio for credit sales
            $includeInRevenue = false;
            $revenueRatio = 0;

            if ($sale->payment_type !== 'credit') {
                $includeInRevenue = true;
                $revenueRatio = 1;
            } else {
                $paidAmount = $sale->grand_total - $sale->remaining_balance;
                if ($paidAmount > 0) {
                    $includeInRevenue = true;
                    $revenueRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
                }
            }

            if ($includeInRevenue) {
                $saleItems = DB::table('sale_items')
                    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                    ->where('sale_items.sale_id', $sale->id)
                    ->select(
                        'sale_items.id as sale_item_id',
                        'sale_items.quantity',
                        'purchases.purchased_price'
                    )
                    ->get();

                foreach ($saleItems as $item) {
                    // Get returns for this specific sale_item
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->sale_item_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('quantity_returned');

                    $netQuantity = $item->quantity - $returnedQty;

                    if ($netQuantity > 0) {
                        // Calculate cost based on net quantity and revenue ratio
                        $effectiveQuantity = $netQuantity * $revenueRatio;
                        $itemCost = $item->purchased_price * $effectiveQuantity;
                        $totalCost += $itemCost;
                    }
                }
            }
        }

        return [
            'total_cogs' => round($totalCost, 2),
            'avg_cost_per_item' => 0 // Can be calculated if needed
        ];
    }

    /**
     * Calculate Operating Expenses
     */
    private function calculateOperatingExpenses($startDate, $endDate)
    {
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])->get();

        $breakdown = [];
        $totalExpenses = 0;

        foreach ($expenses as $expense) {
            $category = $expense->category ?? 'Miscellaneous';
            
            if (!isset($breakdown[$category])) {
                $breakdown[$category] = [
                    'category' => $category,
                    'amount' => 0,
                    'count' => 0
                ];
            }

            $breakdown[$category]['amount'] += $expense->amount;
            $breakdown[$category]['count']++;
            $totalExpenses += $expense->amount;
        }

        return [
            'total_expenses' => round($totalExpenses, 2),
            'breakdown' => array_values($breakdown),
            'expense_count' => $expenses->count()
        ];
    }

    /**
     * Calculate Tax Information
     */
    private function calculateTaxInfo($startDate, $endDate)
    {
        $totalTaxCollected = 0;
        $totalTaxableAmount = 0;

        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        foreach ($sales as $sale) {
            // Determine revenue inclusion
            $includeInRevenue = false;
            $revenueRatio = 0;

            if ($sale->payment_type !== 'credit') {
                $includeInRevenue = true;
                $revenueRatio = 1;
            } else {
                $paidAmount = $sale->grand_total - $sale->remaining_balance;
                if ($paidAmount > 0) {
                    $includeInRevenue = true;
                    $revenueRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
                }
            }

            if ($includeInRevenue) {
                $saleTaxableAmount = ($sale->grand_total - $sale->tax) * $revenueRatio;
                $saleTaxAmount = $sale->tax * $revenueRatio;

                $totalTaxableAmount += $saleTaxableAmount;
                $totalTaxCollected += $saleTaxAmount;
            }
        }

        // Subtract returns
        $returns = SaleReturn::with('sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($returns as $return) {
            $sale = $return->sale;
            if (!$sale) continue;

            // Calculate refunded tax using the same logic as salesTaxReport
            $actualPaymentRatio = $sale->subtotal > 0 ? ($sale->grand_total / $sale->subtotal) : 1;
            $baseReturnAmount = $actualPaymentRatio > 0 ? ($return->total_return_amount / $actualPaymentRatio) : 0;
            $returnRatio = $sale->subtotal > 0 ? ($baseReturnAmount / $sale->subtotal) : 0;
            
            $refundedTax = $sale->tax * $returnRatio;
            $refundedTaxable = $return->total_return_amount - $refundedTax;

            $totalTaxableAmount -= $refundedTaxable;
            $totalTaxCollected -= $refundedTax;
        }

        return [
            'total_tax_collected' => round($totalTaxCollected, 2),
            'total_taxable_amount' => round($totalTaxableAmount, 2),
            'avg_tax_rate' => $totalTaxableAmount > 0 
                ? round(($totalTaxCollected / $totalTaxableAmount) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get payment type breakdown
     */
    /**
 * Get payment type breakdown
 */
private function getPaymentTypeBreakdown($startDate, $endDate)
{
    $breakdown = [
        'cash' => ['amount' => 0, 'count' => 0],
        'card' => ['amount' => 0, 'count' => 0],
        'credit' => ['amount' => 0, 'count' => 0, 'paid' => 0, 'outstanding' => 0]
    ];

    $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

    foreach ($sales as $sale) {
        // Get returns for this sale
        $returnAmount = SaleReturn::where('sale_id', $sale->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_return_amount');

        // ✅ Skip completely returned sales (don't count in transactions)
        if ($returnAmount >= $sale->grand_total) {
            continue; // Sale is fully returned, exclude from count
        }

        $netAmount = $sale->grand_total - $returnAmount;

        if ($sale->payment_type === 'credit') {
            $paidAmount = $sale->grand_total - $sale->remaining_balance;
            $breakdown['credit']['amount'] += $netAmount;
            $breakdown['credit']['paid'] += max(0, $paidAmount - $returnAmount);
            $breakdown['credit']['outstanding'] += max(0, $sale->remaining_balance - max(0, $returnAmount - $paidAmount));
            $breakdown['credit']['count']++;
        } else {
            $type = $sale->payment_type;
            if (isset($breakdown[$type])) {
                $breakdown[$type]['amount'] += $netAmount;
                $breakdown[$type]['count']++;
            }
        }
    }

    return $breakdown;
}

    /**
     * Get top performing products
     */
    public function getTopProducts(Request $request)
    {
        $dateRange = $request->date_range ?? 'this_month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;
        
        [$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);
        $limit = $request->limit ?? 10;
        $sortBy = $request->sort_by ?? 'revenue'; // revenue, profit, quantity

        $products = collect();
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        foreach ($sales as $sale) {
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->where('sale_items.sale_id', $sale->id)
                ->select(
                    'purchases.product_name',
                    'purchases.unit',
                    'purchases.purchased_price',
                    'sale_items.id as sale_item_id',
                    'sale_items.quantity',
                    'sale_items.total_after_discount'
                )
                ->get();

            $saleSubtotal = $saleItems->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

            $includeInRevenue = false;
            $revenueRatio = 0;

            if ($sale->payment_type !== 'credit') {
                $includeInRevenue = true;
                $revenueRatio = 1;
            } else {
                $paidAmount = $sale->grand_total - $sale->remaining_balance;
                if ($paidAmount > 0) {
                    $includeInRevenue = true;
                    $revenueRatio = $paidAmount / $sale->grand_total;
                }
            }

            if ($includeInRevenue) {
                foreach ($saleItems as $item) {
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->sale_item_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('quantity_returned');

                    $netQuantity = $item->quantity - $returnedQty;

                    if ($netQuantity > 0) {
                        $effectiveQuantity = $netQuantity * $revenueRatio;
                        $itemRevenue = ($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio * $revenueRatio;
                        $itemCost = $item->purchased_price * $effectiveQuantity;
                        $itemProfit = $itemRevenue - $itemCost;

                        $key = $item->product_name;

                        if (!$products->has($key)) {
                            $products->put($key, [
                                'product_name' => $item->product_name,
                                'unit' => $item->unit,
                                'quantity_sold' => 0,
                                'revenue' => 0,
                                'cost' => 0,
                                'profit' => 0,
                                'profit_margin' => 0
                            ]);
                        }

                        $current = $products->get($key);
                        $current['quantity_sold'] += $effectiveQuantity;
                        $current['revenue'] += $itemRevenue;
                        $current['cost'] += $itemCost;
                        $current['profit'] += $itemProfit;
                        $products->put($key, $current);
                    }
                }
            }
        }

        $products = $products->map(function ($product) {
            $product['profit_margin'] = $product['revenue'] > 0 
                ? ($product['profit'] / $product['revenue']) * 100 
                : 0;
            return $product;
        });

        $products = $products->sortByDesc($sortBy)->take($limit)->values();

        return response()->json($products);
    }

    /**
     * Get category performance
     */
    public function getCategoryPerformance(Request $request)
    {
        $dateRange = $request->date_range ?? 'this_month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;
        
        [$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);

        $categories = collect();
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();

        foreach ($sales as $sale) {
            $saleItems = DB::table('sale_items')
                ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
                ->join('categories', 'purchases.category_id', '=', 'categories.id')
                ->where('sale_items.sale_id', $sale->id)
                ->select(
                    'categories.name as category',
                    'sale_items.id as sale_item_id',
                    'sale_items.quantity',
                    'sale_items.total_after_discount',
                    'purchases.purchased_price'
                )
                ->get();

            $saleSubtotal = $saleItems->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

            $includeInRevenue = false;
            $revenueRatio = 0;

            if ($sale->payment_type !== 'credit') {
                $includeInRevenue = true;
                $revenueRatio = 1;
            } else {
                $paidAmount = $sale->grand_total - $sale->remaining_balance;
                if ($paidAmount > 0) {
                    $includeInRevenue = true;
                    $revenueRatio = $paidAmount / $sale->grand_total;
                }
            }

            if ($includeInRevenue) {
                foreach ($saleItems as $item) {
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->sale_item_id)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('quantity_returned');

                    $netQuantity = $item->quantity - $returnedQty;

                    if ($netQuantity > 0) {
                        $effectiveQuantity = $netQuantity * $revenueRatio;
                        $itemRevenue = ($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio * $revenueRatio;
                        $itemCost = $item->purchased_price * $effectiveQuantity;

                        if (!$categories->has($item->category)) {
                            $categories->put($item->category, [
                                'category' => $item->category,
                                'revenue' => 0,
                                'cost' => 0,
                                'profit' => 0,
                                'profit_margin' => 0,
                                'contribution_percentage' => 0
                            ]);
                        }

                        $current = $categories->get($item->category);
                        $current['revenue'] += $itemRevenue;
                        $current['cost'] += $itemCost;
                        $current['profit'] += ($itemRevenue - $itemCost);
                        $categories->put($item->category, $current);
                    }
                }
            }
        }

        $totalRevenue = $categories->sum('revenue');

        $categories = $categories->map(function ($category) use ($totalRevenue) {
            $category['profit_margin'] = $category['revenue'] > 0 
                ? ($category['profit'] / $category['revenue']) * 100 
                : 0;
            $category['contribution_percentage'] = $totalRevenue > 0 
                ? ($category['revenue'] / $totalRevenue) * 100 
                : 0;
            return $category;
        });

        return response()->json($categories->sortByDesc('revenue')->values());
    }

    /**
     * Export financial report to PDF
     */
    public function exportPDF(Request $request)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $financialData = $this->getFinancialSummary($request)->getData();
        $topProducts = $this->getTopProducts($request)->getData();
        $categoryPerformance = $this->getCategoryPerformance($request)->getData();

        $pdf = PDF::loadView('pages.reports.pdf.financial-report', [
            'financialData' => $financialData,
            'topProducts' => $topProducts,
            'categoryPerformance' => $categoryPerformance,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => Carbon::now()
        ]);

        return $pdf->download('financial-report-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.pdf');
    }

    /**
     * Helper method to get date range
     */
    private function getDateRange($dateRange, $startDate, $endDate)
    {
        if ($dateRange === 'custom' && $startDate && $endDate) {
            return [$startDate, $endDate];
        }

        $now = Carbon::now();

        switch ($dateRange) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'yesterday':
                return [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()];
            case 'this_week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'last_week':
                return [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()];
            case 'this_month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'last_month':
                return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
            case 'this_year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }
}