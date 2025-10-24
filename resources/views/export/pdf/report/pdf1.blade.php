<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Report - {{ $startDate->format('d M Y') }} to {{ $endDate->format('d M Y') }}</title>
    <style>
        @page{
            size: A4 landscape;
            margin:20px;
        }
        /* PDF STYLES - DOMPDF COMPATIBLE */
        /* * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } */
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Header */
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #333;
        }
        
        .report-header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .report-header .period {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .report-header .generated {
            font-size: 9px;
            color: #999;
        }
        
        /* Section Headers */
        .section-header {
            background: #2c3e50;
            color: white;
            padding: 10px;
            margin: 20px 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* Metrics Grid */
        .metrics-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .metric-box {
            width: 14%;
            float: left;
            margin-right: 1.5%;
            border: 2px solid #ddd;
            padding: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .metric-box:nth-child(5n) {
            margin-right: 0;
        }
        
        .metric-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .metric-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .metric-change {
            font-size: 8px;
            padding: 3px 6px;
            border-radius: 3px;
        }
        
        .change-up {
            background: #d4edda;
            color: #155724;
        }
        
        .change-down {
            background: #f8d7da;
            color: #721c24;
        }
        
        .change-stable {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .clearfix {
            clear: both;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        table th {
            background: #2c3e50;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        
        table td {
            border-bottom: 1px solid #ddd;
            padding: 6px 5px;
            font-size: 9px;
        }
        
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-success {
            color: #27ae60;
            font-weight: bold;
        }
        
        .text-danger {
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #27ae60;
            color: white;
        }
        
        .badge-danger {
            background: #e74c3c;
            color: white;
        }
        
        .badge-warning {
            background: #f39c12;
            color: white;
        }
        
        .badge-info {
            background: #3498db;
            color: white;
        }
        
        .badge-secondary {
            background: #95a5a6;
            color: white;
        }
        
        /* Comparison Table */
        .comparison-table {
            width: 48%;
            float: left;
            margin-right: 4%;
        }
        
        .comparison-table:nth-child(2) {
            margin-right: 0;
        }
        
        .comparison-table h3 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        /* Summary Boxes */
        .summary-box {
            border: 2px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .summary-label {
            display: table-cell;
            width: 60%;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .summary-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            color: #666;
        }
        
        /* Chart Representation (Text-based) */
        .chart-bar {
            width: 100%;
            height: 15px;
            background: #e0e0e0;
            margin-bottom: 5px;
            position: relative;
        }
        
        .chart-bar-fill {
            height: 100%;
            background: #3498db;
        }
        
        .chart-label {
            font-size: 8px;
            margin-bottom: 2px;
        }
        
        .chart-value {
            position: absolute;
            right: 5px;
            top: 2px;
            font-size: 8px;
            color: white;
            font-weight: bold;
        }
        
        /* Footer */
        .report-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- REPORT HEADER -->
    <div class="report-header">
        <h1>📊 COMPREHENSIVE SALES REPORT</h1>
        <div class="period">
            Period: <strong>{{ $startDate->format('d M Y') }}</strong> to <strong>{{ $endDate->format('d M Y') }}</strong>
        </div>
        <div class="generated">
            Generated on: {{ \Carbon\Carbon::now()->format('d M Y h:i A') }}
        </div>
    </div>

    <!-- ========================================
         SECTION 1: OVERVIEW & KEY METRICS
         ======================================== -->
    <div class="section-header">1. OVERVIEW & KEY PERFORMANCE INDICATORS</div>
    
    <div class="metrics-grid">
        <div class="metric-box">
            <div class="metric-label">REVENUE</div>
            <div class="metric-value">Rs {{ number_format($overviewData->current->revenue, 2) }}</div>
            <div class="metric-change change-{{ $overviewData->changes->revenue->trend }}">
                {{ $overviewData->changes->revenue->trend === 'up' ? '↑' : ($overviewData->changes->revenue->trend === 'down' ? '↓' : '→') }}
                {{ number_format(abs($overviewData->changes->revenue->percent), 1) }}%
            </div>
        </div>
        
        <div class="metric-box">
            <div class="metric-label">TRANSACTIONS</div>
            <div class="metric-value">{{ number_format($overviewData->current->transactions, 0) }}</div>
            <div class="metric-change change-{{ $overviewData->changes->transactions->trend }}">
                {{ $overviewData->changes->transactions->trend === 'up' ? '↑' : ($overviewData->changes->transactions->trend === 'down' ? '↓' : '→') }}
                {{ number_format(abs($overviewData->changes->transactions->percent), 1) }}%
            </div>
        </div>
        
        <div class="metric-box">
            <div class="metric-label">ITEMS SOLD</div>
            <div class="metric-value">{{ number_format($overviewData->current->items_sold, 0) }}</div>
            <div class="metric-change change-{{ $overviewData->changes->items_sold->trend }}">
                {{ $overviewData->changes->items_sold->trend === 'up' ? '↑' : ($overviewData->changes->items_sold->trend === 'down' ? '↓' : '→') }}
                {{ number_format(abs($overviewData->changes->items_sold->percent), 1) }}%
            </div>
        </div>
        
        <div class="metric-box">
            <div class="metric-label">AVG ORDER VALUE</div>
            <div class="metric-value">Rs {{ number_format($overviewData->current->avg_order_value, 2) }}</div>
            <div class="metric-change change-{{ $overviewData->changes->avg_order_value->trend }}">
                {{ $overviewData->changes->avg_order_value->trend === 'up' ? '↑' : ($overviewData->changes->avg_order_value->trend === 'down' ? '↓' : '→') }}
                {{ number_format(abs($overviewData->changes->avg_order_value->percent), 1) }}%
            </div>
        </div>
        
        <div class="metric-box">
            <div class="metric-label">PROFIT</div>
            <div class="metric-value">Rs {{ number_format($overviewData->current->profit, 2) }}</div>
            <div class="metric-change change-{{ $overviewData->changes->profit->trend }}">
                {{ $overviewData->changes->profit->trend === 'up' ? '↑' : ($overviewData->changes->profit->trend === 'down' ? '↓' : '→') }}
                {{ number_format(abs($overviewData->changes->profit->percent), 1) }}%
            </div>
        </div>
    </div>
    <div class="clearfix"></div>

    <!-- Period Comparison -->
    <div class="comparison-table">
        <h3>Current Period ({{ $overviewData->period->current->start }} - {{ $overviewData->period->current->end }})</h3>
        <table>
            <tr>
                <td><strong>Revenue:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->current->revenue, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Transactions:</strong></td>
                <td class="text-right">{{ number_format($overviewData->current->transactions, 0) }}</td>
            </tr>
            <tr>
                <td><strong>Items Sold:</strong></td>
                <td class="text-right">{{ number_format($overviewData->current->items_sold, 0) }}</td>
            </tr>
            <tr>
                <td><strong>Avg Order Value:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->current->avg_order_value, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Profit:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->current->profit, 2) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="comparison-table">
        <h3>Previous Period ({{ $overviewData->period->previous->start }} - {{ $overviewData->period->previous->end }})</h3>
        <table>
            <tr>
                <td><strong>Revenue:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->previous->revenue, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Transactions:</strong></td>
                <td class="text-right">{{ number_format($overviewData->previous->transactions, 0) }}</td>
            </tr>
            <tr>
                <td><strong>Items Sold:</strong></td>
                <td class="text-right">{{ number_format($overviewData->previous->items_sold, 0) }}</td>
            </tr>
            <tr>
                <td><strong>Avg Order Value:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->previous->avg_order_value, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Profit:</strong></td>
                <td class="text-right">Rs {{ number_format($overviewData->previous->profit, 2) }}</td>
            </tr>
        </table>
    </div>
    <div class="clearfix"></div>

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 2: PRODUCT-WISE SALES ANALYSIS
         ======================================== -->
    <div class="section-header">2. PRODUCT-WISE SALES ANALYSIS</div>
    
    @if($productData->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Product Name</th>
                    <th style="width: 8%;">Unit</th>
                    <th style="width: 10%;" class="text-right">Qty Sold</th>
                    <th style="width: 12%;" class="text-right">Revenue</th>
                    <th style="width: 12%;" class="text-right">Cost</th>
                    <th style="width: 12%;" class="text-right">Profit</th>
                    <th style="width: 8%;" class="text-right">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productData as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $product->product_name }}</strong></td>
                    <td><span class="badge badge-secondary">{{ $product->unit }}</span></td>
                    <td class="text-right">{{ number_format($product->quantity_sold, 2) }}</td>
                    <td class="text-right">Rs {{ number_format($product->revenue, 2) }}</td>
                    <td class="text-right">Rs {{ number_format($product->cost, 2) }}</td>
                    <td class="text-right {{ $product->profit >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($product->profit, 2) }}
                    </td>
                    <td class="text-right">
                        <span class="badge {{ $product->profit_margin >= 0 ? 'badge-success' : 'badge-danger' }}">
                            {{ number_format($product->profit_margin, 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Top 5 Products Chart Representation -->
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Top 5 Products by Revenue</h3>
        @foreach($productData->take(5) as $index => $product)
            @php
                $maxRevenue = $productData->first()->revenue;
                $percentage = $maxRevenue > 0 ? ($product->revenue / $maxRevenue) * 100 : 0;
            @endphp
            <div class="chart-label">{{ $index + 1 }}. {{ $product->product_name }} - Rs {{ number_format($product->revenue, 2) }}</div>
            <div class="chart-bar">
                <div class="chart-bar-fill" style="width: {{ $percentage }}%;"></div>
            </div>
        @endforeach
    @else
        <div class="no-data">No product data available for this period</div>
    @endif

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 3: CATEGORY-WISE SALES ANALYSIS
         ======================================== -->
    <div class="section-header">3. CATEGORY-WISE SALES ANALYSIS</div>
    
    @if($categoryData->count() > 0)        
    <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Category</th>
                    <th style="width: 15%;" class="text-right">Revenue</th>
                    <th style="width: 15%;" class="text-right">Cost</th>
                    <th style="width: 15%;" class="text-right">Profit</th>
                    <th style="width: 10%;" class="text-right">Margin %</th>
                    <th style="width: 8%;" class="text-right">Trans.</th>
                    <th style="width: 7%;" class="text-right">Items</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryData as $index => $category)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $category->category }}</strong></td>
                    <td class="text-right">Rs {{ number_format($category->revenue, 2) }}</td>
                    <td class="text-right">Rs {{ number_format($category->cost, 2) }}</td>
                    <td class="text-right {{ $category->profit >= 0 ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($category->profit, 2) }}
                    </td>
                    <td class="text-right">
                        <span class="badge {{ $category->profit_margin >= 0 ? 'badge-success' : 'badge-danger' }}">
                            {{ number_format($category->profit_margin, 1) }}%
                        </span>
                    </td>
                    <td class="text-right">{{ $category->transactions }}</td>
                    <td class="text-right">{{ number_format($category->items_sold, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Category Revenue Distribution -->
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Category Revenue Distribution</h3>
        @php
            $totalCategoryRevenue = $categoryData->sum('revenue');
        @endphp
        @foreach($categoryData as $index => $category)
            @php
                $percentage = $totalCategoryRevenue > 0 ? ($category->revenue / $totalCategoryRevenue) * 100 : 0;
            @endphp
            <div class="chart-label">{{ $category->category }} - Rs {{ number_format($category->revenue, 2) }} ({{ number_format($percentage, 1) }}%)</div>
            <div class="chart-bar">
                <div class="chart-bar-fill" style="width: {{ $percentage }}%; background: #27ae60;"></div>
            </div>
        @endforeach
    @else
        <div class="no-data">No category data available for this period</div>
    @endif

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 4: TRANSACTION LOG (First 30)
         ======================================== -->
    <div class="section-header">4. TRANSACTION LOG (Top 30 Transactions)</div>
    
    @if(isset($transactionData->data) && is_array($transactionData->data) && count($transactionData->data) > 0)        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Voucher</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 15%;">Customer</th>
                    <th style="width: 8%;">Type</th>
                    <th style="width: 12%;" class="text-right">Total</th>
                    <th style="width: 12%;" class="text-right">Net Amt</th>
                    <th style="width: 12%;" class="text-right">Paid</th>
                    <th style="width: 11%;" class="text-right">Balance</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach(array_slice($transactionData->data, 0, 30) as $transaction)               
             <tr>
                    <td><strong>{{ $transaction->voucher_no }}</strong></td>
                    <td style="font-size: 8px;">{{ $transaction->date }}<br>{{ $transaction->time }}</td>
                    <td>{{ $transaction->customer }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower($transaction->payment_type) === 'cash' ? 'success' : (strtolower($transaction->payment_type) === 'credit' ? 'warning' : 'info') }}">
                            {{ $transaction->payment_type }}
                        </span>
                    </td>
                    <td class="text-right">Rs {{ number_format($transaction->grand_total, 2) }}</td>
                    <td class="text-right">Rs {{ number_format($transaction->net_amount, 2) }}</td>
                    <td class="text-right text-success">Rs {{ number_format($transaction->paid_amount, 2) }}</td>
                    <td class="text-right {{ $transaction->remaining_balance > 0 ? 'text-danger' : '' }}">
                        Rs {{ number_format($transaction->remaining_balance, 2) }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $transaction->return_status === 'Full Return' ? 'badge-danger' : ($transaction->return_status === 'Partial Return' ? 'badge-warning' : 'badge-success') }}">
                            {{ $transaction->return_status === 'Full Return' ? 'Full' : ($transaction->return_status === 'Partial Return' ? 'Partial' : 'OK') }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @if(count($transactionData->data) > 30)
        <div style="text-align: center; color: #999; font-style: italic; margin-top: 10px;">
                Showing 30 of {{ count($transactionData->data) }} transactions. Full list available in system.
            </div>
        @endif
    @else
        <div class="no-data">No transactions found for this period</div>
    @endif

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 5: TAX REPORT
         ======================================== -->
    <div class="section-header">5. TAX COLLECTION REPORT</div>
    
    @if(isset($taxData->summary))
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">Total Taxable Amount:</div>
                <div class="summary-value">Rs {{ number_format($taxData->summary->total_taxable_amount, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Tax Collected:</div>
                <div class="summary-value">Rs {{ number_format($taxData->summary->total_tax_collected, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Average Tax Rate:</div>
                <div class="summary-value">{{ number_format($taxData->summary->avg_tax_rate, 2) }}%</div>
            </div>
        </div>
    @endif
    
    @if(isset($taxData->data) && count($taxData->data) > 0)
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Daily Tax Collection Details</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Period</th>
                    <th style="width: 30%;" class="text-right">Taxable Amount</th>
                    <th style="width: 30%;" class="text-right">Tax Collected</th>
                    <th style="width: 20%;" class="text-right">Tax Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxData->data as $tax)
                <tr>
                    <td><strong>{{ isset($tax->date) ? $tax->date : $tax->month }}</strong></td>
                    <td class="text-right">Rs {{ number_format($tax->taxable_amount, 2) }}</td>
                    <td class="text-right">Rs {{ number_format($tax->tax_collected, 2) }}</td>
                    <td class="text-right">{{ number_format($tax->tax_rate, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Tax Collection Trend (Bar representation) -->
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Tax Collection Trend</h3>
        @php
            $maxTax = collect($taxData->data)->max('tax_collected');
        @endphp
        @foreach(collect($taxData->data)->take(10) as $index => $tax)
            @php
                $percentage = $maxTax > 0 ? ($tax->tax_collected / $maxTax) * 100 : 0;
            @endphp
            <div class="chart-label">{{ isset($tax->date) ? \Carbon\Carbon::parse($tax->date)->format('d M') : $tax->month }} - Rs {{ number_format($tax->tax_collected, 2) }}</div>
            <div class="chart-bar">
                <div class="chart-bar-fill" style="width: {{ $percentage }}%; background: #17a2b8;"></div>
            </div>
        @endforeach
    @else
        <div class="no-data">No tax data available for this period</div>
    @endif

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 6: TIME-BASED ANALYSIS
         ======================================== -->
    <div class="section-header">6. TIME-BASED SALES ANALYSIS</div>
    
    @if(isset($timeData->data) && count($timeData->data) > 0)
        <!-- Peak Times Summary -->
        <div class="summary-box">
            <h3 style="font-size: 12px; margin-bottom: 10px; color: #2c3e50;">Peak Performance Times</h3>
            
            @if(isset($timeData->peak_revenue_time))
            <div class="summary-row">
                <div class="summary-label">🔥 Peak Revenue Time:</div>
                <div class="summary-value">
                    <strong>{{ $timeData->peak_revenue_time->day_name ?? 'N/A' }}</strong> - 
                    Rs {{ number_format($timeData->peak_revenue_time->revenue, 2) }}
                </div>
            </div>
            @endif
            
            @if(isset($timeData->peak_transactions_time))
            <div class="summary-row">
                <div class="summary-label">📈 Peak Transaction Time:</div>
                <div class="summary-value">
                    <strong>{{ $timeData->peak_transactions_time->day_name ?? 'N/A' }}</strong> - 
                    {{ $timeData->peak_transactions_time->transactions }} transactions
                </div>
            </div>
            @endif
            
            <div class="summary-row">
                <div class="summary-label">Total Revenue (Period):</div>
                <div class="summary-value">Rs {{ number_format($timeData->total_revenue, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Transactions (Period):</div>
                <div class="summary-value">{{ number_format($timeData->total_transactions, 0) }}</div>
            </div>
        </div>
        
        <!-- Day-wise Analysis Table -->
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Day-wise Sales Performance</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Day</th>
                    <th style="width: 25%;" class="text-right">Revenue</th>
                    <th style="width: 20%;" class="text-right">Transactions</th>
                    <th style="width: 25%;" class="text-right">Avg Transaction</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeData->data as $index => $time)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $time->day_name ?? ($time->time_label ?? $time->week_label ?? 'N/A') }}</strong></td>
                    <td class="text-right">Rs {{ number_format($time->revenue, 2) }}</td>
                    <td class="text-right">{{ $time->transactions }}</td>
                    <td class="text-right">Rs {{ number_format($time->avg_transaction_value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Visual Revenue Distribution -->
        <h3 style="font-size: 12px; margin: 15px 0 10px 0;">Revenue Distribution by Day</h3>
        @php
            $maxTimeRevenue = collect($timeData->data)->max('revenue');
        @endphp
        @foreach($timeData->data as $time)
            @php
                $percentage = $maxTimeRevenue > 0 ? ($time->revenue / $maxTimeRevenue) * 100 : 0;
            @endphp
            <div class="chart-label">
                {{ $time->day_name ?? ($time->time_label ?? $time->week_label ?? 'N/A') }} - 
                Rs {{ number_format($time->revenue, 2) }} 
                ({{ $time->transactions }} trans.)
            </div>
            <div class="chart-bar">
                <div class="chart-bar-fill" style="width: {{ $percentage }}%; background: #9b59b6;"></div>
            </div>
        @endforeach
    @else
        <div class="no-data">No time-based data available for this period</div>
    @endif

    <div class="page-break"></div>

    <!-- ========================================
         SECTION 7: SUMMARY & INSIGHTS
         ======================================== -->
    <div class="section-header">7. EXECUTIVE SUMMARY & KEY INSIGHTS</div>
    
    <div class="summary-box">
        <h3 style="font-size: 13px; margin-bottom: 10px; color: #2c3e50;">📊 Performance Summary</h3>
        
        <div class="summary-row">
            <div class="summary-label">Reporting Period:</div>
            <div class="summary-value">{{ $startDate->format('d M Y') }} to {{ $endDate->format('d M Y') }}</div>
        </div>
        
        <div class="summary-row">
            <div class="summary-label">Total Revenue Generated:</div>
            <div class="summary-value" style="color: #27ae60; font-weight: bold;">Rs {{ number_format($overviewData->current->revenue, 2) }}</div>
        </div>
        
        <div class="summary-row">
            <div class="summary-label">Total Profit Earned:</div>
            <div class="summary-value" style="color: #27ae60; font-weight: bold;">Rs {{ number_format($overviewData->current->profit, 2) }}</div>
        </div>
        
        @php
            $profitMargin = $overviewData->current->revenue > 0 
                ? ($overviewData->current->profit / $overviewData->current->revenue) * 100 
                : 0;
        @endphp
        <div class="summary-row">
            <div class="summary-label">Overall Profit Margin:</div>
            <div class="summary-value">{{ number_format($profitMargin, 2) }}%</div>
        </div>
        
        <div class="summary-row">
            <div class="summary-label">Total Transactions Completed:</div>
            <div class="summary-value">{{ number_format($overviewData->current->transactions, 0) }}</div>
        </div>
        
        <div class="summary-row">
            <div class="summary-label">Total Items Sold:</div>
            <div class="summary-value">{{ number_format($overviewData->current->items_sold, 0) }}</div>
        </div>
        
        <div class="summary-row">
            <div class="summary-label">Average Order Value:</div>
            <div class="summary-value">Rs {{ number_format($overviewData->current->avg_order_value, 2) }}</div>
        </div>
    </div>
    
    <div class="summary-box" style="margin-top: 15px;">
        <h3 style="font-size: 13px; margin-bottom: 10px; color: #2c3e50;">📈 Period-over-Period Growth</h3>
        
        <table style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 40%;">Metric</th>
                    <th style="width: 30%;" class="text-right">Change</th>
                    <th style="width: 30%;" class="text-right">Growth %</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Revenue</strong></td>
                    <td class="text-right {{ $overviewData->changes->revenue->trend === 'up' ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($overviewData->changes->revenue->value, 2) }}
                    </td>
                    <td class="text-right">
                        <span class="badge badge-{{ $overviewData->changes->revenue->trend === 'up' ? 'success' : 'danger' }}">
                            {{ $overviewData->changes->revenue->trend === 'up' ? '+' : '' }}{{ number_format($overviewData->changes->revenue->percent, 1) }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Transactions</strong></td>
                    <td class="text-right {{ $overviewData->changes->transactions->trend === 'up' ? 'text-success' : 'text-danger' }}">
                        {{ number_format($overviewData->changes->transactions->value, 0) }}
                    </td>
                    <td class="text-right">
                        <span class="badge badge-{{ $overviewData->changes->transactions->trend === 'up' ? 'success' : 'danger' }}">
                            {{ $overviewData->changes->transactions->trend === 'up' ? '+' : '' }}{{ number_format($overviewData->changes->transactions->percent, 1) }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Profit</strong></td>
                    <td class="text-right {{ $overviewData->changes->profit->trend === 'up' ? 'text-success' : 'text-danger' }}">
                        Rs {{ number_format($overviewData->changes->profit->value, 2) }}
                    </td>
                    <td class="text-right">
                        <span class="badge badge-{{ $overviewData->changes->profit->trend === 'up' ? 'success' : 'danger' }}">
                            {{ $overviewData->changes->profit->trend === 'up' ? '+' : '' }}{{ number_format($overviewData->changes->profit->percent, 1) }}%
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    @if(count($productData) > 0)
    <div class="summary-box" style="margin-top: 15px;">
        <h3 style="font-size: 13px; margin-bottom: 10px; color: #2c3e50;">🏆 Top Performers</h3>
        
        <div style="margin-bottom: 8px;">
            <strong>Best Selling Product:</strong> 
            {{ $productData->sortByDesc('quantity_sold')->first()->product_name ?? 'N/A' }}
            ({{ number_format($productData->sortByDesc('quantity_sold')->first()->quantity_sold ?? 0, 0) }} units)
        </div>
        
        <div style="margin-bottom: 8px;">
            <strong>Highest Revenue Product:</strong> 
            {{ $productData->first()->product_name ?? 'N/A' }}
            (Rs {{ number_format($productData->first()->revenue ?? 0, 2) }})
        </div>
        
        <div style="margin-bottom: 8px;">
            <strong>Most Profitable Product:</strong> 
            {{ $productData->sortByDesc('profit')->first()->product_name ?? 'N/A' }}
            (Rs {{ number_format($productData->sortByDesc('profit')->first()->profit ?? 0, 2) }})
        </div>
        
        @if(count($categoryData) > 0)
        <div>
            <strong>Top Category:</strong> 
            {{ $categoryData->first()->category ?? 'N/A' }}
            (Rs {{ number_format($categoryData->first()->revenue ?? 0, 2) }})
        </div>
        @endif
    </div>
    @endif

    <!-- Report Footer -->
    <div style="margin-top: 30px; padding-top: 15px; border-top: 2px solid #333; text-align: center;">
        <div style="font-size: 10px; color: #666; margin-bottom: 5px;">
        <p>Generated by POS System | © {{ date('Y') }}</p>
            <strong>End of Report</strong>
        </div>
        <div style="font-size: 8px; color: #999;">
            This report is computer-generated and contains confidential business information.
        </div>
        <div style="font-size: 8px; color: #999; margin-top: 3px;">
            Generated by POS System | © {{ date('Y') }}
        </div>
    </div>
</body>
</html>

<!-- {{ \Carbon\Carbon::now()->format('l, d F Y \a\t h:i A') }} -->