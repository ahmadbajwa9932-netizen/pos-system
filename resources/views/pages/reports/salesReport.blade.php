@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{asset('css/components/container.css')}}">
<link rel="stylesheet" href="{{ asset('css/reports/salesReport.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="sales-report-container">
    <!-- Header -->
    <div class="report-header">
        <div class="header-left">
            <h2><i class="fas fa-chart-bar"></i> Sales Report</h2>
            <p class="header-subtitle">Detailed sales analysis and insights</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline-primary" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button class="btn btn-outline-success" onclick="exportReport('excel')">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button class="btn btn-primary" onclick="printReport()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-card">
        <div class="filter-row">
            <div class="filter-col">
                <label class="filter-label">Date Range</label>
                <select class="filter-select" id="dateRange" onchange="handleDateRangeChange()">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="this_week">This Week</option>
                    <option value="last_week">Last Week</option>
                    <option value="this_month" selected>This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_quarter">This Quarter</option>
                    <option value="this_year">This Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="filter-col custom-date-col" id="customStartDate">
                <label class="filter-label">Start Date</label>
                <input type="date" class="filter-input" id="startDate" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="filter-col custom-date-col" id="customEndDate">
                <label class="filter-label">End Date</label>
                <input type="date" class="filter-input" id="endDate" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="filter-col">
                <label class="filter-label">&nbsp;</label>
                <button class="btn btn-primary btn-block" onclick="loadAllReports()">
                    <i class="fas fa-search"></i> Generate
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <a href="#overview" class="tab-link active" data-tab="overview">
                <i class="fas fa-chart-line"></i> Overview
            </a>
            <a href="#products" class="tab-link" data-tab="products">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="#categories" class="tab-link" data-tab="categories">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="#transactions" class="tab-link" data-tab="transactions">
                <i class="fas fa-receipt"></i> Transactions
            </a>
            <a href="#tax" class="tab-link" data-tab="tax">
                <i class="fas fa-percentage"></i> Tax Report
            </a>
            <a href="#time" class="tab-link" data-tab="time">
                <i class="fas fa-clock"></i> Time Analysis
            </a>
        </div>

        <!-- Tab Content -->
        <div class="tabs-content">
            <!-- 1. OVERVIEW TAB -->
            <div class="tab-pane active" id="overview">
                <div class="comparison-cards" id="comparisonCards">
                    <!-- Will be populated by JavaScript -->
                </div>
                <div class="content-card">
                    <div class="card-header-primary">
                        <h5>Period Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div id="comparisonDetails" class="loading-state">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. PRODUCTS TAB -->
            <div class="tab-pane" id="products">
                <div class="content-card">
                    <div class="card-header-flex">
                        <h5>Product-wise Sales Report</h5>
                        <div class="header-controls">
                            <select class="filter-select-sm" id="productSortBy" onchange="loadProductReport()">
                                <option value="revenue">Sort by Revenue</option>
                                <option value="quantity">Sort by Quantity</option>
                                <option value="profit">Sort by Profit</option>
                            </select>
                            <select class="filter-select-sm" id="productSortOrder" onchange="loadProductReport()">
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>Unit</th>
                                        <th class="text-right">Qty Sold</th>
                                        <th class="text-right">Revenue</th>
                                        <th class="text-right">Cost</th>
                                        <th class="text-right">Profit</th>
                                        <th class="text-right">Profit %</th>
                                        <th class="text-right">Avg Price</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <tr>
                                        <td colspan="9" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. CATEGORIES TAB -->
            <div class="tab-pane" id="categories">
                <div class="grid-two-col">
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Category Performance</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Category Profit Margin</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryProfitChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
                <div class="content-card">
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th class="text-right">Revenue</th>
                                        <th class="text-right">Cost</th>
                                        <th class="text-right">Profit</th>
                                        <th class="text-right">Margin %</th>
                                        <th class="text-right">Transactions</th>
                                        <th class="text-right">Items Sold</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryTableBody">
                                    <tr>
                                        <td colspan="8" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. TRANSACTIONS TAB -->
            <div class="tab-pane" id="transactions">
                <div class="content-card">
                    <div class="card-body">
                        <div class="filter-row">
                            <div class="filter-col">
                                <label class="filter-label">Payment Type</label>
                                <select class="filter-select" id="filterPaymentType" onchange="loadTransactionLog()">
                                    <option value="">All</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                            <div class="filter-col">
                                <label class="filter-label">Customer</label>
                                <select class="filter-select" id="filterCustomer" onchange="loadTransactionLog()">
                                    <option value="">All Customers</option>
                                </select>
                            </div>
                            <div class="filter-col">
                                <label class="filter-label">Search Invoice</label>
                                <input type="text" class="filter-input" id="searchInvoice" placeholder="Voucher No...">
                            </div>
                            <div class="filter-col">
                                <label class="filter-label">&nbsp;</label>
                                <button class="btn btn-primary btn-block" onclick="loadTransactionLog()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table table-compact">
                                <thead>
                                    <tr>
                                        <th>Voucher</th>
                                        <th>Date & Time</th>
                                        <th>Customer</th>
                                        <th>Payment</th>
                                        <th class="text-right">Subtotal</th>
                                        <th class="text-right">Discount</th>
                                        <th class="text-right">Tax</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">Net Amount</th>
                                        <th class="text-right">Paid</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-right">Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTableBody">
                                    <tr>
                                        <td colspan="11" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="transactionPagination" class="pagination-container"></div>
                    </div>
                </div>
            </div>

            <!-- 5. TAX REPORT TAB -->
            <div class="tab-pane" id="tax">
                <div class="grid-three-col">
                    <div class="stat-card stat-primary">
                        <div class="stat-label">Total Taxable Amount</div>
                        <div class="stat-value" id="totalTaxableAmount">Rs 0.00</div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-label">Total Tax Collected</div>
                        <div class="stat-value" id="totalTaxCollected">Rs 0.00</div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-label">Average Tax Rate</div>
                        <div class="stat-value" id="avgTaxRate">0.00%</div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header-flex">
                        <h5>Tax Collection Details</h5>
                        <select class="filter-select-sm" id="taxGroupBy" onchange="loadTaxReport()">
                            <option value="daily">Daily</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <canvas id="taxChart" class="chart-canvas-lg"></canvas>
                        <div class="table-container table-margin-top">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th class="text-right">Taxable Amount</th>
                                        <th class="text-right">Tax Collected</th>
                                        <th class="text-right">Tax Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="taxTableBody">
                                    <tr>
                                        <td colspan="4" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. TIME ANALYSIS TAB -->
            <div class="tab-pane" id="time">
                <div class="content-card">
                    <div class="card-header-flex">
                        <h5>Sales Pattern Analysis</h5>
                        <select class="filter-select-sm" id="timeAnalysisType" onchange="loadTimeAnalysis()">
                            <option value="hourly">Hourly</option>
                            <option value="daily">Day of Week</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <canvas id="timeAnalysisChart" class="chart-canvas-lg"></canvas>
                    </div>
                </div>

                <div class="grid-two-col">
                    <div class="content-card">
                        <div class="card-header-success">
                            <h5>🔥 Peak Revenue Time</h5>
                        </div>
                        <div class="card-body" id="peakRevenueTime">
                            <div class="loading-state">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header-info">
                            <h5>📈 Peak Transaction Time</h5>
                        </div>
                        <div class="card-body" id="peakTransactionTime">
                            <div class="loading-state">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal" id="transactionModal">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Transaction Details</h5>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Will be populated dynamically -->
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let charts = {};

// Initialize tabs
// Initialize tabs
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Initializing Sales Report');
    
    // Tab switching
    const tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs
            tabLinks.forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
            
            // Load data for active tab
            loadTabData(targetTab);
        });
    });
    
    // Load initial overview data with delay to ensure DOM is ready
    setTimeout(() => {
        loadOverview();
    }, 100);
});

function loadTabData(tabId) {
    switch(tabId) {
        case 'overview': loadOverview(); break;
        case 'products': loadProductReport(); break;
        case 'categories': loadCategoryReport(); break;
        case 'transactions': loadTransactionLog(); break;
        case 'tax': loadTaxReport(); break;
        case 'time': loadTimeAnalysis(); break;
    }
}

function handleDateRangeChange() {
    const dateRange = document.getElementById('dateRange').value;
    const customStartDate = document.getElementById('customStartDate');
    const customEndDate = document.getElementById('customEndDate');
    
    if (dateRange === 'custom') {
        customStartDate.style.display = 'block';
        customEndDate.style.display = 'block';
    } else {
        customStartDate.style.display = 'none';
        customEndDate.style.display = 'none';
    }
}

function loadAllReports() {
    const activeTab = document.querySelector('.tab-link.active').getAttribute('data-tab');
    loadTabData(activeTab);
}

function getDateParams() {
    const dateRange = document.getElementById('dateRange').value;
    let params = { date_range: dateRange };
    
    if (dateRange === 'custom') {
        params.start_date = document.getElementById('startDate').value;
        params.end_date = document.getElementById('endDate').value;
    }
    
    return new URLSearchParams(params).toString();
}

// 1. OVERVIEW
function loadOverview() {
    console.log('Loading overview...', getDateParams());
    
    fetch(`/reports/sales/date-range?${getDateParams()}`)
        .then(response => {
            console.log('Response received:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            displayComparisonCards(data);
            displayComparisonDetails(data);
        })
        .catch(error => {
            console.error('Error loading overview:', error);
            document.getElementById('comparisonCards').innerHTML = 
                '<div class="error-message">Error loading data. Please refresh the page.</div>';
            document.getElementById('comparisonDetails').innerHTML = 
                '<div class="error-message">Error loading comparison data.</div>';
        });
}

function displayComparisonCards(data) {
    const cards = document.getElementById('comparisonCards');
    const metrics = [
        { key: 'revenue', label: 'Revenue', icon: 'fa-dollar-sign', color: 'primary' },
        { key: 'transactions', label: 'Transactions', icon: 'fa-receipt', color: 'success' },
        { key: 'items_sold', label: 'Items Sold', icon: 'fa-box', color: 'info' },
        { key: 'avg_order_value', label: 'Avg Order Value', icon: 'fa-chart-line', color: 'warning' },
        { key: 'profit', label: 'Profit', icon: 'fa-hand-holding-usd', color: 'danger' }
    ];
    
    cards.innerHTML = metrics.map(metric => `
        <div class="metric-card metric-${metric.color}">
            <i class="fas ${metric.icon} metric-icon"></i>
            <div class="metric-label">${metric.label}</div>
            <div class="metric-value">${metric.key === 'transactions' || metric.key === 'items_sold' ? 
                Math.round(data.current[metric.key]) : 
                'Rs ' + parseFloat(data.current[metric.key]).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})
            }</div>
            <div class="metric-badge badge-${data.changes[metric.key].trend}">
                <i class="fas fa-arrow-${data.changes[metric.key].trend === 'up' ? 'up' : data.changes[metric.key].trend === 'down' ? 'down' : 'right'}"></i>
                ${Math.abs(data.changes[metric.key].percent).toFixed(1)}%
            </div>
        </div>
    `).join('');
}

function displayComparisonDetails(data) {
    const details = document.getElementById('comparisonDetails');
    details.innerHTML = `
        <div class="comparison-grid">
            <div class="comparison-col">
                <h5>Current Period (${data.period.current.start} - ${data.period.current.end})</h5>
                <table class="comparison-table">
                    <tr><td>Revenue:</td><td class="value-bold">Rs ${parseFloat(data.current.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                    <tr><td>Transactions:</td><td class="value-bold">${Math.round(data.current.transactions)}</td></tr>
                    <tr><td>Items Sold:</td><td class="value-bold">${Math.round(data.current.items_sold)}</td></tr>
                    <tr><td>Avg Order Value:</td><td class="value-bold">Rs ${parseFloat(data.current.avg_order_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                    <tr><td>Profit:</td><td class="value-bold">Rs ${parseFloat(data.current.profit).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                </table>
            </div>
            <div class="comparison-col">
                <h5>Previous Period (${data.period.previous.start} - ${data.period.previous.end})</h5>
                <table class="comparison-table">
                    <tr><td>Revenue:</td><td>Rs ${parseFloat(data.previous.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                    <tr><td>Transactions:</td><td>${Math.round(data.previous.transactions)}</td></tr>
                    <tr><td>Items Sold:</td><td>${Math.round(data.previous.items_sold)}</td></tr>
                    <tr><td>Avg Order Value:</td><td>Rs ${parseFloat(data.previous.avg_order_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                    <tr><td>Profit:</td><td>Rs ${parseFloat(data.previous.profit).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td></tr>
                </table>
            </div>
        </div>
    `;
}

// 2. PRODUCT REPORT
function loadProductReport() {
    const sortBy = document.getElementById('productSortBy').value;
    const sortOrder = document.getElementById('productSortOrder').value;
    
    fetch(`/reports/sales/products?${getDateParams()}&sort_by=${sortBy}&sort_order=${sortOrder}`)
        .then(response => response.json())
        .then(data => {
            displayProductTable(data);
        });
}

function displayProductTable(products) {
    const tbody = document.getElementById('productTableBody');
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="no-data">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map((product, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${product.product_name}</strong></td>
            <td><span class="badge badge-secondary">${product.unit}</span></td>
            <td class="text-right">${parseFloat(product.quantity_sold).toFixed(2)}</td>
            <td class="text-right">Rs ${parseFloat(product.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(product.cost).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right ${product.profit >= 0 ? 'text-success' : 'text-danger'}">
                Rs ${parseFloat(product.profit).toLocaleString('en-PK', {minimumFractionDigits: 2})}
            </td>
            <td class="text-right">
                <span class="badge ${product.profit_margin >= 0 ? 'badge-success' : 'badge-danger'}">
                    ${parseFloat(product.profit_margin).toFixed(1)}%
                </span>
            </td>
            <td class="text-right">Rs ${parseFloat(product.avg_selling_price).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

// 3. CATEGORY REPORT
function loadCategoryReport() {
    fetch(`/reports/sales/categories?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            displayCategoryTable(data);
            displayCategoryCharts(data);
        });
}

function displayCategoryTable(categories) {
    const tbody = document.getElementById('categoryTableBody');
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">No categories found</td></tr>';
        return;
    }
    
    tbody.innerHTML = categories.map((cat, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${cat.category}</strong></td>
            <td class="text-right">Rs ${parseFloat(cat.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(cat.cost).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right ${cat.profit >= 0 ? 'text-success' : 'text-danger'}">
                Rs ${parseFloat(cat.profit).toLocaleString('en-PK', {minimumFractionDigits: 2})}
            </td>
            <td class="text-right"><span class="badge ${cat.profit_margin >= 0 ? 'badge-success' : 'badge-danger'}">${parseFloat(cat.profit_margin).toFixed(1)}%</span></td>
            <td class="text-right">${cat.transactions}</td>
            <td class="text-right">${parseFloat(cat.items_sold).toFixed(2)}</td>
        </tr>
    `).join('');
}

function displayCategoryCharts(categories) {
    if (charts.categoryChart) charts.categoryChart.destroy();
    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    charts.categoryChart = new Chart(ctxCategory, {
        type: 'bar',
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                label: 'Revenue',
                data: categories.map(c => c.revenue),
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    if (charts.categoryProfitChart) charts.categoryProfitChart.destroy();
    const ctxProfit = document.getElementById('categoryProfitChart').getContext('2d');
    charts.categoryProfitChart = new Chart(ctxProfit, {
        type: 'doughnut',
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                data: categories.map(c => c.profit_margin),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// 4. TRANSACTION LOG
function loadTransactionLog(page = 1) {
    const paymentType = document.getElementById('filterPaymentType').value;
    const customerId = document.getElementById('filterCustomer').value;
    let params = getDateParams();
    
    if (paymentType) params += `&payment_type=${paymentType}`;
    if (customerId) params += `&customer_id=${customerId}`;
    params += `&page=${page}`;
    
    fetch(`/reports/sales/transactions?${params}`)
        .then(response => response.json())
        .then(data => {
            displayTransactionTable(data);
            displayPagination(data, 'transactionPagination', loadTransactionLog);
        });
}

function displayTransactionTable(data) {
    const tbody = document.getElementById('transactionTableBody');
    
    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="no-data">No transactions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.data.map(transaction => `
        <tr>
            <td><strong>${transaction.voucher_no}</strong></td>
            <td>${transaction.date}<br><small class="text-muted">${transaction.time}</small></td>
            <td>${transaction.customer}</td>
            <td><span class="badge badge-${transaction.payment_type.toLowerCase()}">${transaction.payment_type}</span></td>
            <td class="text-right">Rs ${parseFloat(transaction.subtotal).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(transaction.discount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(transaction.tax).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right"><strong>Rs ${parseFloat(transaction.grand_total).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong></td>
            <td class="text-right">Rs ${parseFloat(transaction.net_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right text-success">Rs ${parseFloat(transaction.paid_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right ${transaction.remaining_balance > 0 ? 'text-danger' : ''}">Rs ${parseFloat(transaction.remaining_balance).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-center">
    <span class="badge ${
        transaction.return_status === 'Full Return'
            ? 'badge-danger'
            : transaction.return_status === 'Partial Return'
                ? 'badge-warning'
                : 'badge-success'
    }">
        ${transaction.return_status}
    </span>
</td>

            <td class="text-center">
                <button class="btn-action" onclick="viewTransactionDetails(${transaction.id})">
                    <i class="fas fa-eye"></i> View
                </button>
            </td>
        </tr>
    `).join('');
}

function viewTransactionDetails(saleId) {
    fetch(`/reports/sales/transactions?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            const transaction = data.data.find(t => t.id === saleId);
            if (transaction) {
                const modalBody = document.getElementById('transactionDetails');
                modalBody.innerHTML = `
                    <div class="transaction-details">
                    <div class="detail-row">
    <strong>Status:</strong>
    <span class="${transaction.return_status === 'Full Return' ? 'text-danger' : 
                 transaction.return_status === 'Partial Return' ? 'text-warning' : 'text-success'}">
        ${transaction.return_status}
    </span>
</div>
                        <div class="detail-row">
                            <strong>Voucher No:</strong>
                            <span>${transaction.voucher_no}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Date & Time:</strong>
                            <span>${transaction.date} ${transaction.time}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Customer:</strong>
                            <span>${transaction.customer}</span>
                        </div>
                        <hr>
                        <h6>Items:</h6>
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
${transaction.items.map(item => `
    <tr>
        <td>${item.product}</td>
        <td class="text-right">
            ${item.net_quantity} 
            ${item.returned_quantity > 0 
                ? `<br><small class="text-warning">(${item.returned_quantity} returned)</small>` 
                : ''}
        </td>
        <td class="text-right">Rs ${parseFloat(item.price).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
        <td class="text-right">Rs ${parseFloat(item.total).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
    </tr>
`).join('')}
</tbody>

                        </table>
                        <hr>
                        <div class="detail-row">
                            <strong>Subtotal:</strong>
                            <span>Rs ${parseFloat(transaction.subtotal).toLocaleString('en-PK', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Discount:</strong>
                            <span>Rs ${parseFloat(transaction.discount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Tax:</strong>
                            <span>Rs ${parseFloat(transaction.tax).toLocaleString('en-PK', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="detail-row detail-total">
                            <strong>Grand Total:</strong>
                            <strong>Rs ${parseFloat(transaction.grand_total).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong>
                        </div>
                         <div class="detail-row" style="font-size:22px">
                            <strong>Net Amount:</strong>
                            <strong>Rs ${parseFloat(transaction.net_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong>
                        </div>
                    </div>
                `;
                document.getElementById('transactionModal').classList.add('active');
            }
        });
}

function closeModal() {
    document.getElementById('transactionModal').classList.remove('active');
}

function displayPagination(data, containerId, loadFunction) {
    const container = document.getElementById(containerId);
    if (!data.last_page || data.last_page <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination">';
    
    html += `<button class="page-btn ${data.current_page === 1 ? 'disabled' : ''}" 
        onclick="${data.current_page > 1 ? loadFunction.name + '(' + (data.current_page - 1) + ')' : 'return false;'}">
        Previous
    </button>`;
    
    for (let i = 1; i <= data.last_page; i++) {
        html += `<button class="page-btn ${i === data.current_page ? 'active' : ''}" 
            onclick="${loadFunction.name}(${i})">${i}</button>`;
    }
    
    html += `<button class="page-btn ${data.current_page === data.last_page ? 'disabled' : ''}" 
        onclick="${data.current_page < data.last_page ? loadFunction.name + '(' + (data.current_page + 1) + ')' : 'return false;'}">
        Next
    </button>`;
    
    html += '</div>';
    container.innerHTML = html;
}

// 5. TAX REPORT
function loadTaxReport() {
    const groupBy = document.getElementById('taxGroupBy').value;
    
    fetch(`/reports/sales/tax?${getDateParams()}&group_by=${groupBy}`)
        .then(response => response.json())
        .then(result => {
            displayTaxSummary(result.summary);
            displayTaxTable(result.data, groupBy);
            displayTaxChart(result.data, groupBy);
        });
}

function displayTaxSummary(summary) {
    document.getElementById('totalTaxableAmount').textContent = 
        'Rs ' + parseFloat(summary.total_taxable_amount).toLocaleString('en-PK', {minimumFractionDigits: 2});
    document.getElementById('totalTaxCollected').textContent = 
        'Rs ' + parseFloat(summary.total_tax_collected).toLocaleString('en-PK', {minimumFractionDigits: 2});
    document.getElementById('avgTaxRate').textContent = 
        parseFloat(summary.avg_tax_rate).toFixed(2) + '%';
}

function displayTaxTable(data, groupBy) {
    const tbody = document.getElementById('taxTableBody');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="no-data">No tax data found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(item => `
        <tr>
            <td><strong>${groupBy === 'daily' ? item.date : item.month}</strong></td>
            <td class="text-right">Rs ${parseFloat(item.taxable_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(item.tax_collected).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">${parseFloat(item.tax_rate).toFixed(2)}%</td>
        </tr>
    `).join('');
}

function displayTaxChart(data, groupBy) {
    if (charts.taxChart) charts.taxChart.destroy();
    
    const ctx = document.getElementById('taxChart').getContext('2d');
    charts.taxChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => groupBy === 'daily' ? item.date : item.month),
            datasets: [{
                label: 'Tax Collected',
                data: data.map(item => item.tax_collected),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// 6. TIME ANALYSIS
function loadTimeAnalysis() {
    const analysisType = document.getElementById('timeAnalysisType').value;
    
    fetch(`/reports/sales/time-analysis?${getDateParams()}&analysis_type=${analysisType}`)
        .then(response => response.json())
        .then(result => {
            displayTimeChart(result.data, analysisType);
            displayPeakTimes(result);
        });
}

function displayTimeChart(data, analysisType) {
    if (charts.timeAnalysisChart) charts.timeAnalysisChart.destroy();
    
    const ctx = document.getElementById('timeAnalysisChart').getContext('2d');
    
    let labels, revenueData;
    if (analysisType === 'hourly') {
        labels = data.map(item => item.time_label);
        revenueData = data.map(item => item.revenue);
    } else if (analysisType === 'daily') {
        labels = data.map(item => item.day_name);
        revenueData = data.map(item => item.revenue);
    } else {
        labels = data.map(item => item.week_label);
        revenueData = data.map(item => item.revenue);
    }
    
    charts.timeAnalysisChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function displayPeakTimes(result) {
    const peakRevenue = document.getElementById('peakRevenueTime');
    const peakTransactions = document.getElementById('peakTransactionTime');
    
    const analysisType = document.getElementById('timeAnalysisType').value;
    let timeLabel;
    
    if (analysisType === 'hourly') {
        timeLabel = result.peak_revenue_time.time_label;
    } else if (analysisType === 'daily') {
        timeLabel = result.peak_revenue_time.day_name;
    } else {
        timeLabel = result.peak_revenue_time.week_label;
    }
    
    peakRevenue.innerHTML = `
        <h3 class="peak-time">${timeLabel}</h3>
        <p class="peak-detail">Revenue: <strong>Rs ${parseFloat(result.peak_revenue_time.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong></p>
        <p class="peak-detail">Transactions: <strong>${result.peak_revenue_time.transactions}</strong></p>
    `;
    
    if (analysisType === 'hourly') {
        timeLabel = result.peak_transactions_time.time_label;
    } else if (analysisType === 'daily') {
        timeLabel = result.peak_transactions_time.day_name;
    } else {
        timeLabel = result.peak_transactions_time.week_label;
    }
    
    peakTransactions.innerHTML = `
        <h3 class="peak-time">${timeLabel}</h3>
        <p class="peak-detail">Transactions: <strong>${result.peak_transactions_time.transactions}</strong></p>
        <p class="peak-detail">Revenue: <strong>Rs ${parseFloat(result.peak_transactions_time.revenue).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong></p>
    `;
}

function exportReport(type) {
    if (type === 'pdf') {
        // Get current filter values
        const dateRange = document.getElementById('dateRange').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        // Create form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/reports/sales/export-pdf-full';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);
        
        // Add date range
        const dateRangeInput = document.createElement('input');
        dateRangeInput.type = 'hidden';
        dateRangeInput.name = 'date_range';
        dateRangeInput.value = dateRange;
        form.appendChild(dateRangeInput);
        
        if (dateRange === 'custom') {
            const startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'start_date';
            startInput.value = startDate;
            form.appendChild(startInput);
            
            const endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'end_date';
            endInput.value = endDate;
            form.appendChild(endInput);
        }
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    } else if (type === 'excel') {
        // Existing excel export logic
        alert('Excel export - to be implemented');
    }
}

function printReport() {
    window.print();
}
</script>

@endsection
