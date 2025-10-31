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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        // Date range handling
        $dateRange = $request->date_range ?? 'this_month';
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        // Set dates based on predefined ranges
        [$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);

        // Get comparison dates for previous period
        $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
        $previousEndDate = $startDate->copy()->subDay();

        return view('pages.reports.salesReport', compact(
            'startDate', 'endDate', 'dateRange', 'previousStartDate', 'previousEndDate'
        ));
    }

    // helper METHOD
private function parseDateRange(Request $request)
{
    $dateRange = $request->date_range ?? 'this_month';
    $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
    $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

    return $this->getDateRange($dateRange, $startDate, $endDate);
}

    // 1. DATE RANGE SALES REPORT WITH COMPARISON
    public function dateRangeSales(Request $request)
    {
         // FIXED: Provide defaults if missing
    $dateRange = $request->date_range ?? 'this_month';
    $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
    $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
    [$startDate, $endDate] = $this->getDateRange($dateRange, $startDate, $endDate);

        // Current period data
        $currentData = $this->getPeriodSalesData($startDate, $endDate);

        // Previous period data for comparison
        $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
        $previousEndDate = $startDate->copy()->subDay();
        $previousData = $this->getPeriodSalesData($previousStartDate, $previousEndDate);

        // Calculate changes
        $changes = $this->calculateChanges($currentData, $previousData);

        return response()->json([
            'current' => $currentData,
            'previous' => $previousData,
            'changes' => $changes,
            'period' => [
                'current' => [
                    'start' => $startDate->format('d M Y'),
                    'end' => $endDate->format('d M Y')
                ],
                'previous' => [
                    'start' => $previousStartDate->format('d M Y'),
                    'end' => $previousEndDate->format('d M Y')
                ]
            ]
        ]);
    }

    // 2. PRODUCT-WISE SALES REPORT (UPDATED WITH RETURNS)
public function productWiseSales(Request $request)
{
    [$startDate, $endDate] = $this->parseDateRange($request);
    $sortBy = $request->sort_by ?? 'revenue';
    $sortOrder = $request->sort_order ?? 'desc';

    $products = collect();

    // Get all sales in period
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

        // CORRECT: Calculate discount ratio exactly like revenue controller
        $saleSubtotal = $saleItems->sum('total_after_discount');
        $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

        // CORRECT: Determine revenue inclusion based on payment type (cash-based accounting)
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
            $saleHasRemainingItems = false; // ✅ Track if any non-returned item exists

            foreach ($saleItems as $item) {
                // Get returns for this specific sale_item
                $returnedQty = SaleReturnItem::where('sale_item_id', $item->sale_item_id)
                    ->sum('quantity_returned');

                $netQuantity = $item->quantity - $returnedQty;

                // Skip fully returned items
                if ($netQuantity <= 0) {
                    continue;
                }

                $saleHasRemainingItems = true; // ✅ At least one valid item remains

                // CORRECT: Apply revenue ratio for credit sales
                $effectiveQuantity = $netQuantity * $revenueRatio;

                // CORRECT: Calculate revenue with discount ratio applied
                $itemRevenue = ($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio * $revenueRatio;

                // CORRECT: Cost based on effective quantity
                $itemCost = $item->purchased_price * $effectiveQuantity;

                $itemProfit = $itemRevenue - $itemCost;

                $key = $item->product_name . '|' . $item->unit;

                if (!$products->has($key)) {
                    $products->put($key, [
                        'product_name' => $item->product_name,
                        'unit' => $item->unit,
                        'quantity_sold' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'avg_selling_price' => 0,
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

            // ✅ If all items of this sale are returned, skip sale entirely (don't count it at all)
            if (!$saleHasRemainingItems) {
                continue;
            }
        }
    }

    // Calculate averages and margins
    $products = $products->map(function ($product) {
        $product['avg_selling_price'] = $product['quantity_sold'] > 0 
            ? $product['revenue'] / $product['quantity_sold'] 
            : 0;
        $product['profit_margin'] = $product['revenue'] > 0 
            ? ($product['profit'] / $product['revenue']) * 100 
            : 0;
        return $product;
    });

    // Sort products
    $products = $products->sortBy([
        [$sortBy, $sortOrder]
    ])->values();

    return response()->json($products);
}


    // 3. CATEGORY-WISE SALES REPORT (CORRECTED)
    public function categoryWiseSales(Request $request)
    {
        [$startDate, $endDate] = $this->parseDateRange($request);

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

            // CORRECT: Same discount ratio calculation
            $saleSubtotal = $saleItems->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;

            // CORRECT: Same revenue inclusion logic
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
    ->whereBetween('created_at', [$startDate, $endDate]) // ensures only returns within range
    ->sum('quantity_returned');

                    
                    $netQuantity = $item->quantity - $returnedQty;

                    if ($netQuantity > 0) {
                        $effectiveQuantity = $netQuantity * $revenueRatio;
                        
                        // CORRECT: Revenue calculation matching your logic
                        $itemRevenue = ($item->total_after_discount * ($netQuantity / $item->quantity)) * $discountRatio * $revenueRatio;
                        $itemCost = $item->purchased_price * $effectiveQuantity;

                        if (!$categories->has($item->category)) {
                            $categories->put($item->category, [
                                'category' => $item->category,
                                'revenue' => 0,
                                'cost' => 0,
                                'profit' => 0,
                                'transactions' => 0,
                                'items_sold' => 0
                            ]);
                        }

                        $current = $categories->get($item->category);
                        $current['revenue'] += $itemRevenue;
                        $current['cost'] += $itemCost;
                        $current['profit'] += ($itemRevenue - $itemCost);
                        $current['items_sold'] += $effectiveQuantity;
                        $categories->put($item->category, $current);
                    }
                }
            }
        }

        // Count transactions per category (excluding fully returned)
foreach ($categories as $categoryName => $data) {
    $transactionCount = DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->join('categories', 'purchases.category_id', '=', 'categories.id')
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->leftJoin(DB::raw('(
            SELECT sale_item_id, 
                   SUM(quantity_returned) as total_returned
            FROM sale_return_items 
            WHERE created_at BETWEEN "' . $startDate . '" AND "' . $endDate . '"
            GROUP BY sale_item_id
        ) as returns'), 'sale_items.id', '=', 'returns.sale_item_id')
        ->where('categories.name', $categoryName)
        ->whereBetween('sales.created_at', [$startDate, $endDate])
        ->whereRaw('sale_items.quantity > COALESCE(returns.total_returned, 0)') // ✅ Exclude fully returned
        ->distinct('sales.id')
        ->count('sales.id');

            $data['transactions'] = $transactionCount;
            $data['profit_margin'] = $data['revenue'] > 0 ? ($data['profit'] / $data['revenue']) * 100 : 0;
            $categories->put($categoryName, $data);
        }

        return response()->json($categories->sortByDesc('revenue')->values());
    }

    public function transactionLog(Request $request)
{
    [$startDate, $endDate] = $this->parseDateRange($request);
    $paymentType = $request->payment_type;
    $customerId = $request->customer_id;
    $perPage = $request->per_page ?? 20;

    $query = Sale::with(['customer', 'saleItems.purchase', 'payments', 'returns'])
        ->whereBetween('created_at', [$startDate, $endDate]);

    if ($paymentType) {
        $query->where('payment_type', $paymentType);
    }

    if ($customerId) {
        $query->where('customer_id', $customerId);
    }

    $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

    // Enrich transaction data
    $transactions->getCollection()->transform(function ($sale) use ($startDate, $endDate) {
        $returnAmount = SaleReturn::where('sale_id', $sale->id)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('total_return_amount');
        
        // Calculate total sale amount and returned ratio
        $totalAmount = $sale->grand_total;
        $returnedRatio = $totalAmount > 0 ? ($returnAmount / $totalAmount) : 0;
        // Determine return status
        if ($returnedRatio >= 0.999) {
            $returnStatus = 'Full Return';
        } elseif ($returnedRatio > 0) {
            $returnStatus = 'Partial Return';
        } else {
            $returnStatus = 'No Return';
        }
        return [
            'id' => $sale->id,
            'voucher_no' => $sale->voucher_no,
            'date' => $sale->created_at->format('d M Y'),
            'time' => $sale->created_at->format('h:i A'),
            'customer' => $sale->customer->name ?? 'Walk-in',
            'payment_type' => ucfirst($sale->payment_type),
            'subtotal' => $sale->subtotal,
            'discount' => $sale->discount_amount,
            'tax' => $sale->tax,
            'grand_total' => $sale->grand_total,
            'paid_amount' => $sale->grand_total - $sale->remaining_balance,
            'remaining_balance' => $sale->remaining_balance,
            'return_amount' => $returnAmount,
            'net_amount' => $sale->grand_total - $returnAmount,
            'status' => $sale->status,
            'return_status' => $returnStatus,
            'items_count' => $sale->saleItems->count(),
            'items' => $sale->saleItems->map(function ($item) use ($startDate, $endDate) {
                $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('quantity_returned');

                $netQty = $item->quantity - $returnedQty;
                $netTotal = $item->quantity > 0
                    ? $item->total_after_discount * ($netQty / $item->quantity)
                    : 0;

                return [
                    'product' => $item->purchase->product_name ?? 'N/A',
                    'quantity' => $item->quantity,
                    'returned_quantity' => $returnedQty,
                    'net_quantity' => $netQty,
                    'price' => $item->price,
                    'total' => $item->total_after_discount,
                    'net_total' => $netTotal
                ];
            })
        ];
    });

    return response()->json($transactions);
}


public function salesTaxReport(Request $request)
{
    [$startDate, $endDate] = $this->parseDateRange($request);
    $groupBy = $request->group_by ?? 'daily';

    $taxData = collect();

    if ($groupBy === 'daily') {
        $sales = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN payment_type != "credit" THEN (grand_total - tax_amount) ELSE 0 END) as taxable_amount'),
            DB::raw('SUM(CASE WHEN payment_type != "credit" THEN tax_amount ELSE 0 END) as tax_collected')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Add paid portions of credit sales
        $creditSales = Sale::select(DB::raw('DATE(created_at) as date'))
            ->where('payment_type', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        foreach ($creditSales as $dateGroup) {
            $creditSalesForDate = Sale::where('payment_type', 'credit')
                ->whereDate('created_at', $dateGroup->date)
                ->get();

            $paidTaxableAmount = 0;
            $paidTaxAmount = 0;

            foreach ($creditSalesForDate as $creditSale) {
                $paidRatio = $creditSale->grand_total > 0 
                    ? ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total 
                    : 0;
                    $paidTaxableAmount += ($creditSale->grand_total - $creditSale->tax_amount) * $paidRatio;
                    $paidTaxAmount += $creditSale->tax_amount * $paidRatio;
            }

            $existingRecord = $sales->firstWhere('date', $dateGroup->date);
            if ($existingRecord) {
                $existingRecord->taxable_amount += $paidTaxableAmount;
                $existingRecord->tax_collected += $paidTaxAmount;
            } else {
                $sales->push((object)[
                    'date' => $dateGroup->date,
                    'taxable_amount' => $paidTaxableAmount,
                    'tax_collected' => $paidTaxAmount
                ]);
            }
        }

        // ✅ FIXED: total_return_amount already includes tax proportionally via actualPaymentRatio
        $returns = SaleReturn::with('sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($returns as $return) {
            $sale = $return->sale;
            if (!$sale) continue;

            $saleDate = $sale->created_at->format('Y-m-d');
            
            // total_return_amount already has discounts + proportional tax applied
            // We need to separate taxable amount from tax
            $actualPaymentRatio = $sale->subtotal > 0 ? ($sale->grand_total / $sale->subtotal) : 1;
            
            // Reverse calculate the base return amount (before actualPaymentRatio was applied)
            $baseReturnAmount = $actualPaymentRatio > 0 ? ($return->total_return_amount / $actualPaymentRatio) : 0;
            
            // Calculate what portion of subtotal was returned
            $returnRatio = $sale->subtotal > 0 ? ($baseReturnAmount / $sale->subtotal) : 0;
            
         // Calculate refunded tax (proportional to return ratio)
$refundedTax = $sale->tax_amount * $returnRatio;

// Refunded taxable amount (total return minus the tax portion)
$refundedTaxable = $return->total_return_amount - $refundedTax;

            $record = $sales->firstWhere('date', $saleDate);
            if ($record) {
                $record->taxable_amount -= $refundedTaxable;
                $record->tax_collected -= $refundedTax;
            }
        }

        $taxData = $sales->sortBy('date')->values();
    } 
    else {
        // === MONTHLY GROUPING ===
        $sales = Sale::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(CASE WHEN payment_type != "credit" THEN (grand_total - tax_amount) ELSE 0 END) as taxable_amount'),
            DB::raw('SUM(CASE WHEN payment_type != "credit" THEN tax_amount ELSE 0 END) as tax_collected')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $creditSales = Sale::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'))
            ->where('payment_type', 'credit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->get();

        foreach ($creditSales as $monthGroup) {
            $creditSalesForMonth = Sale::where('payment_type', 'credit')
                ->where(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), $monthGroup->month)
                ->get();

            $paidTaxableAmount = 0;
            $paidTaxAmount = 0;

            foreach ($creditSalesForMonth as $creditSale) {
                $paidRatio = $creditSale->grand_total > 0 
                    ? ($creditSale->grand_total - $creditSale->remaining_balance) / $creditSale->grand_total 
                    : 0;
                    $paidTaxableAmount += ($creditSale->grand_total - $creditSale->tax_amount) * $paidRatio;
                    $paidTaxAmount += $creditSale->tax_amount * $paidRatio;
            }

            $existingRecord = $sales->firstWhere('month', $monthGroup->month);
            if ($existingRecord) {
                $existingRecord->taxable_amount += $paidTaxableAmount;
                $existingRecord->tax_collected += $paidTaxAmount;
            } else {
                $sales->push((object)[
                    'month' => $monthGroup->month,
                    'taxable_amount' => $paidTaxableAmount,
                    'tax_collected' => $paidTaxAmount
                ]);
            }
        }

        // ✅ FIXED: total_return_amount already includes tax proportionally via actualPaymentRatio
        $returns = SaleReturn::with('sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($returns as $return) {
            $sale = $return->sale;
            if (!$sale) continue;

            $saleMonth = $sale->created_at->format('Y-m');
            
            // total_return_amount already has discounts + proportional tax applied
            $actualPaymentRatio = $sale->subtotal > 0 ? ($sale->grand_total / $sale->subtotal) : 1;
            
            // Reverse calculate the base return amount
            $baseReturnAmount = $actualPaymentRatio > 0 ? ($return->total_return_amount / $actualPaymentRatio) : 0;
            
            // Calculate what portion of subtotal was returned
            $returnRatio = $sale->subtotal > 0 ? ($baseReturnAmount / $sale->subtotal) : 0;
            
           // Calculate refunded tax
$refundedTax = $sale->tax_amount * $returnRatio;

// Refunded taxable amount
$refundedTaxable = $return->total_return_amount - $refundedTax;

            $record = $sales->firstWhere('month', $saleMonth);
            if ($record) {
                $record->taxable_amount -= $refundedTaxable;
                $record->tax_collected -= $refundedTax;
            }
        }

        $taxData = $sales->sortBy('month')->values();
    }

    // === Summary ===
    $taxData = $taxData->map(function ($item) {
        $item->tax_rate = $item->taxable_amount > 0 
            ? ($item->tax_collected / $item->taxable_amount) * 100 
            : 0;
        return $item;
    });

    $summary = [
        'total_taxable_amount' => $taxData->sum('taxable_amount'),
        'total_tax_collected' => $taxData->sum('tax_collected'),
        'avg_tax_rate' => $taxData->avg('tax_rate')
    ];

    return response()->json([
        'data' => $taxData,
        'summary' => $summary
    ]);
}


public function timeBasedAnalysis(Request $request)
{
    [$startDate, $endDate] = $this->parseDateRange($request);
    $analysisType = $request->analysis_type ?? 'hourly';

    $timeData = collect();

    if ($analysisType === 'hourly') {
        // Hourly analysis
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStart = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
            $hourEnd = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':59:59';

            $sales = Sale::whereBetween('created_at', [$startDate, $endDate])
                ->whereTime('created_at', '>=', $hourStart)
                ->whereTime('created_at', '<=', $hourEnd)
                ->get();

            $revenue = 0;
            $transactions = 0;

            foreach ($sales as $sale) {
                // Get returns for this sale - total_return_amount already includes all discounts + proportional tax
                $returnAmount = SaleReturn::where('sale_id', $sale->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_return_amount');

                // Determine if sale should be included
                $includeInRevenue = false;
                $saleRevenue = 0;

                if ($sale->payment_type !== 'credit') {
                    $includeInRevenue = true;
                    // Simply subtract the return amount (which already has tax included)
                    $saleRevenue = $sale->grand_total - $returnAmount;
                } else {
                    $paidAmount = $sale->grand_total - $sale->remaining_balance;
                    if ($paidAmount > 0) {
                        $includeInRevenue = true;
                        $paidRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
                        // Apply paid ratio to both grand_total and return amount
                        $saleRevenue = ($sale->grand_total * $paidRatio) - ($returnAmount * $paidRatio);
                    }
                }

                // Only count if there's positive revenue after returns
                if ($includeInRevenue && $saleRevenue > 0) {
                    $revenue += $saleRevenue;
                    $transactions++;
                }
            }

            if ($revenue > 0 || $transactions > 0) {
                $timeData->push([
                    'hour' => $hour,
                    'time_label' => date('h A', strtotime($hourStart)),
                    'day_label' => date('D, d M', strtotime($startDate)),
                    'revenue' => $revenue,
                    'transactions' => $transactions,
                    'avg_transaction_value' => $transactions > 0 ? $revenue / $transactions : 0
                ]);
            }
        }
    } elseif ($analysisType === 'daily') {
        // Day of week analysis
        $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        foreach ($daysOfWeek as $dayIndex => $dayName) {
            $sales = Sale::whereBetween('created_at', [$startDate, $endDate])
                ->whereRaw('DAYOFWEEK(created_at) = ?', [$dayIndex + 1])
                ->get();

            $revenue = 0;
            $transactions = 0;

            foreach ($sales as $sale) {
                // Get returns for this sale
                $returnAmount = SaleReturn::where('sale_id', $sale->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_return_amount');

                // Determine if sale should be included
                $includeInRevenue = false;
                $saleRevenue = 0;

                if ($sale->payment_type !== 'credit') {
                    $includeInRevenue = true;
                    $saleRevenue = $sale->grand_total - $returnAmount;
                } else {
                    $paidAmount = $sale->grand_total - $sale->remaining_balance;
                    if ($paidAmount > 0) {
                        $includeInRevenue = true;
                        $paidRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
                        $saleRevenue = ($sale->grand_total * $paidRatio) - ($returnAmount * $paidRatio);
                    }
                }

                // Only count if there's positive revenue after returns
                if ($includeInRevenue && $saleRevenue > 0) {
                    $revenue += $saleRevenue;
                    $transactions++;
                }
            }

            $timeData->push([
                'day_index' => $dayIndex,
                'day_name' => $dayName,
                'revenue' => $revenue,
                'transactions' => $transactions,
                'avg_transaction_value' => $transactions > 0 ? $revenue / $transactions : 0
            ]);
        }
    } else {
        // Weekly analysis
        $currentDate = $startDate->copy();
        $weekNumber = 1;

        while ($currentDate->lte($endDate)) {
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();

            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }

            $sales = Sale::whereBetween('created_at', [$weekStart, $weekEnd])->get();

            $revenue = 0;
            $transactions = 0;

            foreach ($sales as $sale) {
                // Get returns for this sale
                $returnAmount = SaleReturn::where('sale_id', $sale->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_return_amount');

                // Determine if sale should be included
                $includeInRevenue = false;
                $saleRevenue = 0;

                if ($sale->payment_type !== 'credit') {
                    $includeInRevenue = true;
                    $saleRevenue = $sale->grand_total - $returnAmount;
                } else {
                    $paidAmount = $sale->grand_total - $sale->remaining_balance;
                    if ($paidAmount > 0) {
                        $includeInRevenue = true;
                        $paidRatio = $sale->grand_total > 0 ? $paidAmount / $sale->grand_total : 0;
                        $saleRevenue = ($sale->grand_total * $paidRatio) - ($returnAmount * $paidRatio);
                    }
                }

                // Only count if there's positive revenue after returns
                if ($includeInRevenue && $saleRevenue > 0) {
                    $revenue += $saleRevenue;
                    $transactions++;
                }
            }

            $timeData->push([
                'week' => $weekNumber,
                'week_label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M'),
                'revenue' => $revenue,
                'transactions' => $transactions,
                'avg_transaction_value' => $transactions > 0 ? $revenue / $transactions : 0
            ]);

            $currentDate->addWeek();
            $weekNumber++;
        }
    }

    // Find peak times
    $peakRevenue = $timeData->sortByDesc('revenue')->first();
$peakTransactions = $timeData->sortByDesc('transactions')->first();

// Handle empty results
if (!$peakRevenue) {
    $peakRevenue = [
        'time_label' => 'N/A',
        'day_name' => 'N/A',
        'week_label' => 'N/A',
        'revenue' => 0,
        'transactions' => 0
    ];
}

if (!$peakTransactions) {
    $peakTransactions = [
        'time_label' => 'N/A',
        'day_name' => 'N/A',
        'week_label' => 'N/A',
        'revenue' => 0,
        'transactions' => 0
    ];
}

    return response()->json([
        'data' => $timeData->values(),
        'peak_revenue_time' => $peakRevenue,
        'peak_transactions_time' => $peakTransactions,
        'total_revenue' => $timeData->sum('revenue'),
        'total_transactions' => $timeData->sum('transactions')
    ]);
}

    // 7. EXPORT TO PDF
    // public function exportPDF(Request $request)
    // {
    //     $reportType = $request->report_type;
    //     $startDate = Carbon::parse($request->start_date);
    //     $endDate = Carbon::parse($request->end_date);

    //     $data = [];

    //     switch ($reportType) {
    //         case 'product':
    //             $data = $this->productWiseSales($request)->getData();
    //             $view = 'pages.reports.pdf.product-sales';
    //             break;
    //         case 'category':
    //             $data = $this->categoryWiseSales($request)->getData();
    //             $view = 'pages.reports.pdf.category-sales';
    //             break;
    //         case 'transaction':
    //             $data = $this->transactionLog($request)->getData();
    //             $view = 'pages.reports.pdf.transaction-log';
    //             break;
    //         case 'tax':
    //             $data = $this->salesTaxReport($request)->getData();
    //             $view = 'pages.reports.pdf.tax-report';
    //             break;
    //         default:
    //             return response()->json(['error' => 'Invalid report type'], 400);
    //     }

    //     $pdf = PDF::loadView($view, [
    //         'data' => $data,
    //         'startDate' => $startDate,
    //         'endDate' => $endDate,
    //         'generatedAt' => Carbon::now()
    //     ]);

    //     return $pdf->download($reportType . '-sales-report-' . date('Y-m-d') . '.pdf');
    // }

    // 8. EXPORT TO EXCEL
    // public function exportExcel(Request $request)
    // {
    //     // Placeholder - implement with maatwebsite/excel if needed
    //     return response()->json(['message' => 'Excel export functionality - install maatwebsite/excel package']);
    // }

    // HELPER METHODS

    private function getDateRange($dateRange, $customStart, $customEnd)
    {
        switch ($dateRange) {
            case 'today':
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            case 'yesterday':
                return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            case 'this_week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'last_week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            case 'this_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'last_month':
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            case 'this_quarter':
                return [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()];
            case 'this_year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            case 'custom':
                return [$customStart, $customEnd];
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
    }

    // CORRECTED: Get Period Sales Data (for overview comparison)
    private function getPeriodSalesData($startDate, $endDate)
    {
        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->get();
    
        $totalRevenue = 0;
        $totalTransactions = 0;
        $totalItemsSold = 0;
        $totalProfit = 0;
        $totalPurchaseCost = 0;
    
        foreach ($sales as $sale) {
            $saleItems = Sale_item::where('sale_id', $sale->id)->get();
            
            // Calculate sale-level discount ratio
            $saleSubtotal = DB::table('sale_items')
                ->where('sale_id', $sale->id)
                ->sum('total_after_discount');
            $discountRatio = $saleSubtotal > 0 ? $sale->grand_total / $saleSubtotal : 1;
    
            $includeInRevenue = false;
            $revenueRatio = 0;
            $saleRevenue = 0;
    
            if ($sale->payment_type !== 'credit') {
                $includeInRevenue = true;
                $revenueRatio = 1;
                $saleRevenue = $sale->grand_total;
            } else {
                $paidAmount = $sale->grand_total - $sale->remaining_balance;
                if ($paidAmount > 0) {
                    $includeInRevenue = true;
                    $revenueRatio = $paidAmount / $sale->grand_total;
                    $saleRevenue = $paidAmount;
                }
            }
    
            if ($includeInRevenue) {
                // Adjust for returns
                $saleReturnAmount = SaleReturn::where('sale_id', $sale->id)
                    ->sum('total_return_amount');
                
                if ($sale->payment_type === 'credit') {
                    $saleReturnAmount = $saleReturnAmount * $revenueRatio;
                }
                
                $netRevenue = $saleRevenue - $saleReturnAmount;
                
                // NEW: Check if sale has any remaining items (not fully returned)
                $hasRemainingItems = false;
                $saleItemsTotal = 0;
                
                foreach ($saleItems as $item) {
                    $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)
                        ->sum('quantity_returned');
                    $netQty = ($item->quantity - $returnedQty) * $revenueRatio;
                    
                    if ($netQty > 0) {
                        $hasRemainingItems = true;
                        $saleItemsTotal += $netQty;
                    }
                }
                
                // Only count transaction if it has remaining items AND positive revenue
                if ($netRevenue > 0 && $hasRemainingItems) {
                    $totalRevenue += $netRevenue;
                    $totalTransactions++; // Only count if sale has remaining items
                }
    
                // Calculate items sold and cost (only for remaining items)
                foreach ($saleItems as $item) {
                    $purchase = Purchase::find($item->purchase_id);
                    if ($purchase) {
                        $returnedQty = SaleReturnItem::where('sale_item_id', $item->id)
                            ->sum('quantity_returned');
                        $netQty = ($item->quantity - $returnedQty) * $revenueRatio;
                        
                        if ($netQty > 0) {
                            $totalItemsSold += $netQty;
                            
                            $itemCost = $purchase->purchased_price * $netQty;
                            $totalPurchaseCost += $itemCost;
                            
                            $itemRevenue = ($item->total_after_discount * (($item->quantity - $returnedQty) / $item->quantity)) * $discountRatio * $revenueRatio;
                            $totalProfit += ($itemRevenue - $itemCost);
                        }
                    }
                }
            }
        }
    
        return [
            'revenue' => $totalRevenue,
            'transactions' => $totalTransactions,
            'items_sold' => $totalItemsSold,
            'avg_order_value' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
            'profit' => $totalProfit
        ];
    }

    private function calculateChanges($current, $previous)
    {
        $changes = [];

        foreach (['revenue', 'transactions', 'items_sold', 'avg_order_value', 'profit'] as $metric) {
            $currentValue = $current[$metric];
            $previousValue = $previous[$metric];

            if ($previousValue > 0) {
                $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
            } else {
                $percentChange = $currentValue > 0 ? 100 : 0;
            }

            $changes[$metric] = [
                'value' => $currentValue - $previousValue,
                'percent' => $percentChange,
                'trend' => $percentChange > 0 ? 'up' : ($percentChange < 0 ? 'down' : 'stable')
            ];
        }

        return $changes;
    }

}