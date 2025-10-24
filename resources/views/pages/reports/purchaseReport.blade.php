@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{asset('css/components/container.css')}}">
<link rel="stylesheet" href="{{ asset('css/reports/purchaseReports.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="sales-report-container">
    <!-- Header -->
    <div class="report-header">
        <div class="header-left">
            <h2><i class="fas fa-box"></i> Purchase & Supplier Report</h2>
            <p class="header-subtitle">Comprehensive purchase analysis and supplier insights</p>
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
            <div class="filter-col custom-date-col" id="customStartDate" style="display: none;">
                <label class="filter-label">Start Date</label>
                <input type="date" class="filter-input" id="startDate" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="filter-col custom-date-col" id="customEndDate" style="display: none;">
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

    <!-- Summary Cards -->
    <div class="comparison-cards" id="summaryCards">
        <div class="metric-card metric-primary">
            <i class="fas fa-shopping-cart metric-icon"></i>
            <div class="metric-label">Total Purchases</div>
            <div class="metric-value" id="totalPurchases">0</div>
        </div>
        <div class="metric-card metric-success">
            <i class="fas fa-dollar-sign metric-icon"></i>
            <div class="metric-label">Total Amount</div>
            <div class="metric-value" id="totalAmount">Rs 0.00</div>
        </div>
        <div class="metric-card metric-info">
            <i class="fas fa-users metric-icon"></i>
            <div class="metric-label">Active Suppliers</div>
            <div class="metric-value" id="totalSuppliers">0</div>
        </div>
        <div class="metric-card metric-warning">
            <i class="fas fa-boxes metric-icon"></i>
            <div class="metric-label">Total Products</div>
            <div class="metric-value" id="totalProducts">0</div>
        </div>
        <div class="metric-card metric-danger">
            <i class="fas fa-warehouse metric-icon"></i>
            <div class="metric-label">Stock in Hand</div>
            <div class="metric-value" id="stockInHand">0</div>
        </div>
        <div class="metric-card metric-danger2">
            <i class="fas fa-box-open metric-icon"></i>
            <div class="metric-label">Available Inventory</div>
            <div class="metric-value" id="purchaseAvailableAmount">0</div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <a href="#purchases" class="tab-link active" data-tab="purchases">
                <i class="fas fa-list"></i> Purchase List
            </a>
            <a href="#suppliers" class="tab-link" data-tab="suppliers">
                <i class="fas fa-truck"></i> Suppliers
            </a>
            <a href="#products" class="tab-link" data-tab="products">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="#categories" class="tab-link" data-tab="categories">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="#trends" class="tab-link" data-tab="trends">
                <i class="fas fa-chart-line"></i> Trends
            </a>
        </div>

        <!-- Tab Content -->
        <div class="tabs-content">
            <!-- 1. PURCHASES TAB -->
<div class="tab-pane active" id="purchases">
    
    <!-- 🔹 FILTERS SECTION -->
    <div class="content-card">
        <div class="card-body">
            <div class="filter-row">
                <div class="filter-col">
                    <label class="filter-label">Supplier</label>
                    <select class="filter-select" id="filterSupplier">
                        <option value="">All Suppliers</option>
                    </select>
                </div>
                <div class="filter-col">
                    <label class="filter-label">Category</label>
                    <select class="filter-select" id="filterCategory">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="filter-col">
                    <label class="filter-label">Search Product</label>
                    <input type="text" class="filter-input" id="searchProduct" placeholder="Product name...">
                </div>
                <div class="filter-col">
                    <label class="filter-label">&nbsp;</label>
                    <button class="btn btn-primary btn-block" onclick="loadPurchaseList()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 PURCHASE RECORDS SECTION -->
    <div class="content-card">
        <div class="card-header-flex">
            <h5>Purchase Records</h5>
            <div class="header-controls">
                <select class="filter-select-sm" id="purchaseSortBy" onchange="loadPurchaseList()">
                    <option value="purchase_date">Sort by Date</option>
                    <option value="product_name">Sort by Product</option>
                    <option value="purchased_price">Sort by Price</option>
                    <option value="quantity">Sort by Quantity</option>
                </select>
                <select class="filter-select-sm" id="purchaseSortOrder" onchange="loadPurchaseList()">
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
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Sold</th>
                            <th class="text-right">Stock</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseTableBody">
                        <tr>
                            <td colspan="11" class="loading-cell">
                                <div class="spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="purchasePagination" class="pagination-container"></div>
        </div>
    </div>
</div>

            <!-- 2. SUPPLIERS TAB -->
            <div class="tab-pane" id="suppliers">
                <div class="content-card">
                    <div class="card-header">
                        <h5>Supplier-wise Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Supplier Name</th>
                                        <th>Company</th>
                                        <th>Contact</th>
                                        <th class="text-right">Purchases</th>
                                        <th class="text-right">Products</th>
                                        <th class="text-right">Total Amount</th>
                                        <th class="text-right">Avg Purchase</th>
                                        <th>Last Purchase</th>
                                    </tr>
                                </thead>
                                <tbody id="supplierTableBody">
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

                <div class="grid-two-col">
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Top Suppliers by Amount</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="supplierChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Supplier Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="supplierPieChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. PRODUCTS TAB -->
            <div class="tab-pane" id="products">
                <div class="content-card">
                    <div class="card-header">
                        <h5>Product-wise Purchase Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th class="text-right">Purchases</th>
                                        <th class="text-right">Total Qty</th>
                                        <th class="text-right">Sold</th>
                                        <th class="text-right">Stock</th>
                                        <th class="text-right">Avg Price</th>
                                        <th class="text-right">Total Cost</th>
                                        <th class="text-right">Stock Value</th>
                                        <th class="text-right">Turnover %</th>
                                    </tr>
                                </thead>
                                <tbody id="productTableBody">
                                    <tr>
                                        <td colspan="12" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. CATEGORIES TAB -->
            <div class="tab-pane" id="categories">
                <div class="grid-two-col">
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Category Purchase Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" class="chart-canvas"></canvas>
                        </div>
                    </div>
                    <div class="content-card">
                        <div class="card-header">
                            <h5>Category Stock Value</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryStockChart" class="chart-canvas"></canvas>
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
                                        <th class="text-right">Products</th>
                                        <th class="text-right">Purchases</th>
                                        <th class="text-right">Total Quantity</th>
                                        <th class="text-right">Total Cost</th>
                                        <th class="text-right">Stock Value</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryTableBody">
                                    <tr>
                                        <td colspan="7" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

<!-- 5. TRENDS TAB -->
<div class="tab-pane" id="trends">
    <div class="content-card">
        <div class="card-header">
            <h5>Monthly Purchase Trend</h5>
        </div>
        <div class="card-body">
            <canvas id="trendChart" class="chart-canvas-lg"></canvas>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h5>Monthly Trend Analysis</h5>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Purchases</th>
                            <th class="text-right">Suppliers</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-right">Total Quantity</th>
                            <th class="text-right">Avg Purchase Value</th>
                        </tr>
                    </thead>
                    <tbody id="trendTableBody">
                        <tr>
                            <td colspan="6" class="loading-cell">
                                <div class="spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let charts = {};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Initializing Purchase Report');
    
    // Tab switching
    const tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            
            tabLinks.forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
            
            loadTabData(targetTab);
        });
    });
    
    // Load initial data
    setTimeout(() => {
        loadSummary();
        loadFilterDropdowns();
        loadPurchaseList();
    }, 100);
});

function loadTabData(tabId) {
    switch(tabId) {
        case 'purchases': loadPurchaseList(); break;
        case 'suppliers': loadSupplierSummary(); break;
        case 'products': loadProductSummary(); break;
        case 'categories': loadCategoryAnalysis(); break;
        case 'trends': loadTrendAnalysis(); break;
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
    loadSummary();
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

// Load Summary Cards
function loadSummary() {
    fetch(`/reports/purchase/summary?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalPurchases').textContent = data.total_purchases;
            document.getElementById('totalAmount').textContent = 
                'Rs ' + parseFloat(data.total_purchase_amount).toLocaleString('en-PK', {minimumFractionDigits: 2});
            document.getElementById('totalSuppliers').textContent = data.total_suppliers;
            document.getElementById('totalProducts').textContent = data.total_products;
            document.getElementById('stockInHand').textContent = 
                parseFloat(data.stock_in_hand).toFixed(2);
                document.getElementById('purchaseAvailableAmount').textContent = 
                'Rs ' + parseFloat(data.available_purchase_amount).toLocaleString('en-PK', {minimumFractionDigits: 2});
        })
        .catch(error => console.error('Error loading summary:', error));
}

// Load Filter Dropdowns
function loadFilterDropdowns() {
    // Load suppliers
    fetch('/reports/purchase/suppliers-list')
        .then(response => response.json())
        .then(suppliers => {
            const select = document.getElementById('filterSupplier');
            select.innerHTML = '<option value="">All Suppliers</option>';
            suppliers.forEach(supplier => {
                select.innerHTML += `<option value="${supplier.id}">${supplier.label}</option>`;
            });
        });
    
    // Load categories
    fetch('/reports/purchase/categories-list')
        .then(response => response.json())
        .then(categories => {
            const select = document.getElementById('filterCategory');
            select.innerHTML = '<option value="">All Categories</option>';
            categories.forEach(category => {
                select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
            });
        });
}

// 1. PURCHASE LIST
function loadPurchaseList(page = 1) {
    const supplierId = document.getElementById('filterSupplier').value;
    const categoryId = document.getElementById('filterCategory').value;
    const search = document.getElementById('searchProduct').value;
    const sortBy = document.getElementById('purchaseSortBy').value;
    const sortOrder = document.getElementById('purchaseSortOrder').value;
    
    let params = getDateParams();
    if (supplierId) params += `&supplier_id=${supplierId}`;
    if (categoryId) params += `&category_id=${categoryId}`;
    if (search) params += `&search=${search}`;
    params += `&sort_by=${sortBy}&sort_order=${sortOrder}&page=${page}`;
    
    fetch(`/reports/purchase/purchases?${params}`)
        .then(response => response.json())
        .then(data => {
            displayPurchaseTable(data);
            displayPagination(data, 'purchasePagination', loadPurchaseList);
        })
        .catch(error => console.error('Error loading purchases:', error));
}

function displayPurchaseTable(data) {
    const tbody = document.getElementById('purchaseTableBody');
    
    if (data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="no-data">No purchases found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.data.map((purchase, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${purchase.purchase_date}</td>
            <td>
                <strong>${purchase.supplier_name}</strong>
                ${purchase.supplier_company ? `<br><small class="text-muted">${purchase.supplier_company}</small>` : ''}
            </td>
            <td><strong>${purchase.product_name}</strong></td>
            <td><span class="badge badge-secondary">${purchase.category}</span></td>
            <td class="text-right">${parseFloat(purchase.quantity).toFixed(2)} ${purchase.unit}</td>
            <td class="text-right">${parseFloat(purchase.sold_quantity).toFixed(2)}</td>
            <td class="text-right ${purchase.remaining_stock <= 5 ? 'text-danger' : ''}">${parseFloat(purchase.remaining_stock).toFixed(2)}</td>
            <td class="text-right">Rs ${parseFloat(purchase.purchased_price).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right"><strong>Rs ${parseFloat(purchase.total_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong></td>
            <td class="text-right">Rs ${parseFloat(purchase.stock_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

// 2. SUPPLIER SUMMARY
function loadSupplierSummary() {
    fetch(`/reports/purchase/supplier-summary?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            displaySupplierTable(data);
            displaySupplierCharts(data);
        })
        .catch(error => console.error('Error loading supplier summary:', error));
}

function displaySupplierTable(suppliers) {
    const tbody = document.getElementById('supplierTableBody');
    
    if (suppliers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="no-data">No suppliers found</td></tr>';
        return;
    }
    
    tbody.innerHTML = suppliers.map((supplier, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${supplier.name}</strong></td>
            <td>${supplier.company || 'N/A'}</td>
            <td>${supplier.contact_info || 'N/A'}</td>
            <td class="text-right">${supplier.total_purchases}</td>
            <td class="text-right">${supplier.total_products}</td>
            <td class="text-right"><strong>Rs ${parseFloat(supplier.total_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong></td>
            <td class="text-right">Rs ${parseFloat(supplier.avg_purchase_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td>${supplier.last_purchase_date}</td>
        </tr>
    `).join('');
}

function displaySupplierCharts(suppliers) {
    const top10 = suppliers.slice(0, 10);
    
    // Bar Chart
    if (charts.supplierChart) charts.supplierChart.destroy();
    const ctxBar = document.getElementById('supplierChart').getContext('2d');
    charts.supplierChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: top10.map(s => s.name),
            datasets: [{
                label: 'Total Amount',
                data: top10.map(s => s.total_amount),
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
    
    // Pie Chart
    if (charts.supplierPieChart) charts.supplierPieChart.destroy();
    const ctxPie = document.getElementById('supplierPieChart').getContext('2d');
    charts.supplierPieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: top10.map(s => s.name),
            datasets: [{
                data: top10.map(s => s.total_amount),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(199, 199, 199, 0.6)',
                    'rgba(83, 102, 255, 0.6)',
                    'rgba(255, 99, 255, 0.6)',
                    'rgba(99, 255, 132, 0.6)'
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

// 3. PRODUCT SUMMARY
function loadProductSummary() {
    fetch(`/reports/purchase/product-summary?${getDateParams()}`)
        .then(response => response.json())
        .then(data => displayProductTable(data))
        .catch(error => console.error('Error loading product summary:', error));
}

function displayProductTable(products) {
    const tbody = document.getElementById('productTableBody');
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="no-data">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map((product, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${product.product_name}</strong></td>
            <td><span class="badge badge-secondary">${product.category}</span></td>
            <td>${product.unit}</td>
            <td class="text-right">${product.purchase_count}</td>
            <td class="text-right">${parseFloat(product.total_quantity).toFixed(2)}</td>
            <td class="text-right">${parseFloat(product.total_sold).toFixed(2)}</td>
            <td class="text-right ${product.remaining_stock <= 5 ? 'text-danger' : ''}">
                ${parseFloat(product.remaining_stock).toFixed(2)}
            </td>
            <td class="text-right">Rs ${parseFloat(product.avg_purchase_price).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(product.total_cost).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(product.stock_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">
                <span class="badge ${product.stock_turnover >= 70 ? 'badge-success' : product.stock_turnover >= 40 ? 'badge-warning' : 'badge-danger'}">
                    ${parseFloat(product.stock_turnover).toFixed(1)}%
                </span>
            </td>
        </tr>
    `).join('');
}

// 4. CATEGORY ANALYSIS
function loadCategoryAnalysis() {
    fetch(`/reports/purchase/category-analysis?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            displayCategoryTable(data);
            displayCategoryCharts(data);
        })
        .catch(error => console.error('Error loading category analysis:', error));
}

function displayCategoryTable(categories) {
    const tbody = document.getElementById('categoryTableBody');
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No categories found</td></tr>';
        return;
    }
    
    tbody.innerHTML = categories.map((cat, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${cat.category}</strong></td>
            <td class="text-right">${cat.total_products}</td>
            <td class="text-right">${cat.total_purchases}</td>
            <td class="text-right">${parseFloat(cat.total_quantity).toFixed(2)}</td>
            <td class="text-right">Rs ${parseFloat(cat.total_cost).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">Rs ${parseFloat(cat.stock_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

function displayCategoryCharts(categories) {
    // Bar Chart - Total Cost
    if (charts.categoryChart) charts.categoryChart.destroy();
    const ctxBar = document.getElementById('categoryChart').getContext('2d');
    charts.categoryChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                label: 'Total Cost',
                data: categories.map(c => c.total_cost),
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
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
    
    // Pie Chart - Stock Value
    if (charts.categoryStockChart) charts.categoryStockChart.destroy();
    const ctxPie = document.getElementById('categoryStockChart').getContext('2d');
    charts.categoryStockChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: categories.map(c => c.category),
            datasets: [{
                data: categories.map(c => c.stock_value),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)'
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

// 5. TREND ANALYSIS
function loadTrendAnalysis() {
    fetch(`/reports/purchase/monthly-trend?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            displayTrendChart(data);
            displayTrendTable(data);
        })
        .catch(error => console.error('Error loading trend analysis:', error));
}

function displayTrendChart(data) {
    if (charts.trendChart) charts.trendChart.destroy();
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    charts.trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.month),
            datasets: [{
                label: 'Total Amount',
                data: data.map(item => item.total_amount),
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Total Purchases',
                data: data.map(item => item.total_purchases),
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

function displayTrendTable(data) {
    const tbody = document.getElementById('trendTableBody');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No trend data found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(item => `
        <tr>
            <td><strong>${item.month}</strong></td>
            <td class="text-right">${item.total_purchases}</td>
            <td class="text-right">${item.suppliers_count}</td>
            <td class="text-right">Rs ${parseFloat(item.total_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
            <td class="text-right">${parseFloat(item.total_quantity).toFixed(2)}</td>
            <td class="text-right">Rs ${parseFloat(item.avg_purchase_value).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
        </tr>
    `).join('');
}

// Pagination Helper
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

// Export Functions
function exportReport(type) {
    alert('Export to ' + type.toUpperCase() + ' will be implemented');
}

function printReport() {
    window.print();
}
</script>

@endsection