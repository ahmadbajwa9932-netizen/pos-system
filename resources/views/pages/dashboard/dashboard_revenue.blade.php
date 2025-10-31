@extends('layouts.app')

@section('title', 'Revenue Report')

@push('styles')
<link rel="stylesheet" href="{{asset('css/sales/revenue.css')}}">
<link rel="stylesheet" href="{{asset('css/components/toggle_eye.css')}}">
@endpush

@section('content')
<div class="revenue-container">
    <h2>📊 Revenue Dashboard</h2>

    <!-- Date Filter -->
    <form method="GET" class="filter-form">
        <label>Start:</label>
        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
        <label>End:</label>
        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
        <button type="submit">Apply</button>
    </form>

<!-- KPI Cards -->
<div class="kpi-grid">
<div class="kpi-card">
    <h4>{{ $totalReturnsAmount > 0 ? 'Net Revenue' : 'Total Revenue' }}
<svg class="toggle-visibility" onclick="toggleCardVisibility(this.closest('.kpi-card'))" 
             width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path class="eye-open" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
            <circle class="eye-open" cx="12" cy="12" r="3"></circle>

            <!-- hidden (eye slash) -->
            <path class="eye-closed" d="M3 3l18 18"></path>
        </svg>    
    </h4>

    <p>
        Rs 
        <span class="secure-value" data-value="{{ number_format($totalRevenue, 2) }}">****</span>
    </p>

    @if($totalReturnsAmount > 0)
        <small style="color: #d63384;">
            After Returns: -Rs 
            <span class="secure-value" data-value="{{ number_format($totalReturnsAmount, 2) }}">****</span>
        </small>
    @endif
</div>

        
        @if($totalReturnsAmount > 0)
        <div class="kpi-card">
            <h4>Gross Revenue
            <svg class="toggle-visibility" onclick="toggleCardVisibility(this.closest('.kpi-card'))" 
             width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path class="eye-open" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
            <circle class="eye-open" cx="12" cy="12" r="3"></circle>

            <!-- hidden (eye slash) -->
            <path class="eye-closed" d="M3 3l18 18"></path>
        </svg>
            </h4>
            <p>Rs 
                <span class="secure-value" data-value="{{ number_format($grossRevenue, 2) }}">****</span></p>
            <small style="color: #666;">Before Returns</small>
        </div>
        @endif
        
        <div class="kpi-card">
            <h4>{{ $totalReturnsAmount > 0 ? 'Net Purchases' : 'Total Purchases' }}</h4>
            <p>Rs {{ number_format($totalPurchaseCost, 2) }}</p>
            @if($totalReturnsAmount > 0)
                <small style="color: #666;">Excluding Returned Items</small>
            @endif
        </div>
        
        <div class="kpi-card">

            <h4>{{$totalProfit > 0 ? 'Gross Profit' : 'Gross Loss'}}
            <svg class="toggle-visibility" onclick="toggleCardVisibility(this.closest('.kpi-card'))" 
             width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path class="eye-open" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
            <circle class="eye-open" cx="12" cy="12" r="3"></circle>

            <!-- hidden (eye slash) -->
            <path class="eye-closed" d="M3 3l18 18"></path>
        </svg>
            </h4>
            <p style="color:{{$totalProfit<0 ? 'red' : 'black'}}">Rs 
                <span class="secure-value" data-value="{{ number_format(abs($totalProfit), 2) }}">****</span></p>
            <small style="color: #666;">Revenue - Purchase Cost</small>
        </div>

        <!-- Net Profit -->
<div class="kpi-card">
    <h4>{{$netProfit > 0 ? 'Net Profit' : 'Net Loss'}}
    <svg class="toggle-visibility" onclick="toggleCardVisibility(this.closest('.kpi-card'))" 
             width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path class="eye-open" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
            <circle class="eye-open" cx="12" cy="12" r="3"></circle>

            <!-- hidden (eye slash) -->
            <path class="eye-closed" d="M3 3l18 18"></path>
        </svg>
    </h4>
    <p style="color:{{$netProfit<0 ? 'red' : 'black'}}">Rs 
        <span class="secure-value" data-value="{{ number_format(abs($netProfit), 2) }}">****</span></p>
    <small style="color: #666;">Revenue - COGS - Expenses</small>
</div>
        
        <div class="kpi-card">
            <h4>{{ $totalReturnsAmount > 0 ? 'Net Credit Pending' : 'Credit Pending' }}</h4>
            <p>Rs {{ number_format($totalCredit, 2) }}</p>
            @if($totalReturnsAmount > 0)
                <small style="color: #666;">Adjusted for Returns</small>
            @endif
        </div>
        
        <!-- <div class="kpi-card">
            <h4>Total Paid</h4>
            <p>Rs {{ number_format($totalPaid, 2) }}</p>
            <small style="color: #666;">Payments Received</small>
        </div> -->
        <div class="kpi-card">
    <h4>Total All Purchases</h4>
    <p>Rs {{ number_format($totalAllPurchases, 2) }}</p>
    <small style="color: #666;">All Items (Sold + Unsold)</small>
</div>

<div class="kpi-card">
    <h4>Available Inventory Cost</h4>
    <p>Rs {{ number_format($availableInventoryCost, 2) }}</p>
    <small style="color: #666;">Unsold + Returned Items</small>
</div>

<div class="kpi-card">
    <h4>Sold Items Cost</h4>
    <p>Rs {{ number_format($totalPurchaseCost, 2) }}</p>
    <small style="color: #666;">Cost of Revenue-Generated Items</small>
</div>
<div class="kpi-card">
    <h4>Gross Margin</h4>
    <p>{{ number_format($grossMargin, 1) }}%</p>
    <small style="color: #666;">(Revenue - COGS) / Revenue</small>
</div>

<div class="kpi-card">
    <h4>Net Margin</h4>
    <p>{{ number_format($netMargin, 1) }}%</p>
    <small style="color: #666;">Net Profit / Revenue</small>
</div>

<div class="kpi-card">
    <h4>Total Transactions</h4>
    <p>{{ number_format($totalInvoices) }}</p>
    <small style="color: #666;">Revenue-Generating Sales</small>
</div>

<div class="kpi-card">
    <h4>Average Order Value</h4>
    <p>Rs {{ number_format($averageOrderValue, 2) }}</p>
    <small style="color: #666;">Revenue per Transaction</small>
</div>

<div class="kpi-card">
    <h4>Items Sold</h4>
    <p>{{ number_format($totalItemsSold) }}</p>
    <small style="color: #666;">Total Quantity Sold</small>
</div>

<div class="kpi-card">
    <h4>Returns/Refunds</h4>
    <p>Rs {{ number_format($totalReturnsAmount, 2) }}</p>
    <small style="color: #d63384;">Value of Returns</small>
</div>

<div class="kpi-card">
    <h4>Collection Ratio</h4>
    <p>{{ number_format($paymentCollectionRatio, 1) }}%</p>
    <small style="color: #666;">Cash Flow Health</small>
</div>

<div class="kpi-card">
    <h4>Tax Collected</h4>
    <p>Rs {{ number_format($totalTaxCollected, 2) }}</p>
    <small style="color: #666;">Total Tax Amount</small>
</div>
    </div>

    <!-- Charts -->
    <div class="chart-grid">
        <div class="chart-card">
            <h4 class="section-title">📈 Daily Revenue</h4>
            <div class="chart-container">
                <canvas id="dailyRevenueChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="section-title">📅 Monthly Revenue</h4>
            <div class="chart-container">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="section-title">Revenue by Payment Type</h4>
            <div class="chart-container">
                <canvas id="paymentTypeChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h4 class="section-title">Revenue by Category</h4>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
        <!-- New Charts -->
    <div class="chart-card">
        <h4 class="section-title">Profit by Category</h4>
        <div class="chart-container">
            <canvas id="profitCategoryChart"></canvas>
        </div>
    </div>
    </div>

    <!-- Top Selling Products -->
     <div class="table-grid">
    <div class="table-card">
        <h4 class="section-title">Top Selling Products</h4>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->total_qty }}</td>
                    <td>{{$product->unit}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Top Customers -->
    <div class="table-card">
        <h4 class="section-title">Top Customers</h4>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Total Spent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topCustomers as $customer)
                <tr>
                    <td>{{ $customer->customer ? $customer->customer->name : 'Walk-in Customer' }}</td>
                    <td>Rs {{ number_format($customer->total_spent, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- New Tables -->
    <div class="table-card">
        <h4 class="section-title">Outstanding Credits</h4>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Outstanding Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outstandingCredits as $credit)
                <tr>
                    <td>{{ $credit->customer ? $credit->customer->name : 'Walk-in Customer' }}</td>
                    <td>Rs {{ number_format($credit->outstanding, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-card">
    <h4 class="section-title">Customer Analysis</h4>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f8f9fa;">
                <th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600;">Customer Type</th>
                <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600;">Count</th>
                <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600;">Percentage</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #d4edda;">
                <td style="padding: 12px; border: 1px solid #ddd; font-weight: 500;">
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #28a745; border-radius: 50%; margin-right: 8px;"></span>
                    New Customers
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; font-size: 16px;">
                    {{ $customerTypes->new }}
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                    @php
                        $total = $customerTypes->new + $customerTypes->credit;
                        $newPercentage = $total > 0 ? round(($customerTypes->new / $total) * 100, 1) : 0;
                    @endphp
                    {{ $newPercentage }}%
                </td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td style="padding: 12px; border: 1px solid #ddd; font-weight: 500;">
                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #ffc107; border-radius: 50%; margin-right: 8px;"></span>
                    Credit Customers
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; font-size: 16px;">
                    {{ $customerTypes->credit }}
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                    @php
                        $creditPercentage = $total > 0 ? round(($customerTypes->credit / $total) * 100, 1) : 0;
                    @endphp
                    {{ $creditPercentage }}%
                </td>
            </tr>
            <tr style="background-color: #e9ecef; font-weight: bold;">
                <td style="padding: 12px; border: 1px solid #ddd; font-weight: 600;">
                    Total Customers
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; font-size: 16px;">
                    {{ $total }}
                </td>
                <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                    100%
                </td>
            </tr>
        </tbody>
    </table>
</div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart configuration with responsive options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            }
        },
        layout: {
            padding: 10
        }
    };

    // Store chart instances for proper cleanup
    const chartInstances = {};

    // Function to create or update chart
    function createChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        // Destroy existing chart if it exists
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }
        
        // Create new chart
        chartInstances[canvasId] = new Chart(canvas, config);
    }

    // Daily Revenue Chart
    createChart('dailyRevenueChart', {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyRevenue->pluck('date')) !!},
            datasets: [{
                label: 'Daily Revenue',
                data: {!! json_encode($dailyRevenue->pluck('total')) !!},
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Monthly Revenue Chart
    createChart('monthlyRevenueChart', {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: 'Monthly Revenue',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                backgroundColor: '#28a745'
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Payment Type Chart
    createChart('paymentTypeChart', {
        type: 'pie',
        data: {
            labels: {!! json_encode($salesByPaymentType->pluck('payment_type')) !!},
            datasets: [{
                data: {!! json_encode($salesByPaymentType->pluck('total')) !!},
                backgroundColor: ['#007bff','#ffc107','#28a745']
            }]
        },
        options: chartOptions
    });

    // Category Chart
    createChart('categoryChart', {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($revenueByCategory->pluck('category')) !!},
            datasets: [{
                data: {!! json_encode($revenueByCategory->pluck('total')) !!},
                backgroundColor: ['#6f42c1','#fd7e14','#20c997','#dc3545']
            }]
        },
        options: chartOptions
    });

// Profit by Category Chart
// Profit by Category Chart - DYNAMIC COLORS FOR ALL CATEGORIES
// Generate colors dynamically based on number of categories
const profitCategories = {!! json_encode($profitByCategory->pluck('category')) !!};
const profitData = {!! json_encode($profitByCategory->pluck('profit')) !!};

// Base color palette
const baseColors = [
    '#28a745',  // Green for highest profit
    '#17a2b8',  // Blue
    '#ffc107',  // Yellow  
    '#fd7e14',  // Orange
    '#6f42c1',  // Purple
    '#dc3545',  // Red
    '#20c997',  // Teal
    '#6c757d',  // Gray
    '#e83e8c',  // Pink
    '#007bff',  // Primary blue
    '#28a745',  // Light green
    '#ffc107'   // Amber
];

// Generate colors for all categories
function generateColors(count) {
    const colors = [];
    const borderColors = [];
    
    for (let i = 0; i < count; i++) {
        const color = baseColors[i % baseColors.length];
        colors.push(color);
        
        // Create darker border color
        const borderColor = color.replace('#', '#').slice(0, 7);
        const r = parseInt(borderColor.slice(1, 3), 16);
        const g = parseInt(borderColor.slice(3, 5), 16);
        const b = parseInt(borderColor.slice(5, 7), 16);
        
        // Darken by 20%
        const darkerR = Math.floor(r * 0.8).toString(16).padStart(2, '0');
        const darkerG = Math.floor(g * 0.8).toString(16).padStart(2, '0');
        const darkerB = Math.floor(b * 0.8).toString(16).padStart(2, '0');
        
        borderColors.push(`#${darkerR}${darkerG}${darkerB}`);
    }
    
    return { colors, borderColors };
}

const { colors, borderColors } = generateColors(profitCategories.length);

createChart('profitCategoryChart', {
    type: 'bar',
    data: {
        labels: profitCategories,
        datasets: [{
            label: 'Profit',
            data: profitData,
            backgroundColor: colors,
            borderColor: borderColors,
            borderWidth: 1
        }]
    },
    options: {
        ...chartOptions,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            ...chartOptions.plugins,
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Profit: Rs ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
<script src="{{asset('js/dashboard/toggle_eye.js')}}"></script>
@endsection