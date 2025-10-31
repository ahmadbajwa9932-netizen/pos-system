@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{asset('css/components/container.css')}}">
<link rel="stylesheet" href="{{ asset('css/reports/financialReport.css') }}">
<link rel="stylesheet" href="{{ asset('css/reports/toggle_eye.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="financial-report-container">
    <!-- Header -->
    <div class="report-header">
        <div class="header-left">
            <h2><i class="fas fa-money-bill-wave"></i> Financial Summary Report</h2>
            <p class="header-subtitle">Comprehensive profit & loss analysis</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline-primary" onclick="exportFinancialPDF()">
                <i class="fas fa-file-pdf"></i> Export PDF
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
                <button class="btn btn-primary btn-block" onclick="loadFinancialReport()">
                    <i class="fas fa-search"></i> Generate
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <a href="#summary" class="tab-link active" data-tab="summary">
                <i class="fas fa-chart-pie"></i> Summary
            </a>
            <a href="#revenue" class="tab-link" data-tab="revenue">
                <i class="fas fa-dollar-sign"></i> Revenue
            </a>
            <a href="#expenses" class="tab-link" data-tab="expenses">
                <i class="fas fa-money-bill-wave"></i> Expenses
            </a>
            <a href="#profitability" class="tab-link" data-tab="profitability">
                <i class="fas fa-chart-line"></i> Profitability
            </a>
            <a href="#breakdown" class="tab-link" data-tab="breakdown">
                <i class="fas fa-list-ul"></i> Breakdown
            </a>
        </div>

        <!-- Tab Content -->
        <div class="tabs-content">
            <!-- 1. SUMMARY TAB -->
            <div class="tab-pane active" id="summary">
                <!-- Key Metrics -->
                <div class="key-metrics-grid">
                    <div class="metric-card metric-revenue">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Net Sales Revenue</div>
                            <div class="metric-value" id="summaryNetSales">Rs 0.00</div>
                            <div class="metric-detail" id="summaryGrossSales">Gross: Rs 0.00</div>
                        </div>
                    </div>

                    <div class="metric-card metric-cogs">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Cost of Goods Sold</div>
                            <div class="metric-value" id="summaryCOGS">Rs 0.00</div>
                            <div class="metric-detail" id="summaryCOGSRatio">0% of revenue</div>
                        </div>
                    </div>

                    <div class="metric-card metric-gross-profit">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Gross Profit</div>
                            <div class="metric-value" id="summaryGrossProfit">Rs 0.00</div>
                            <div class="metric-detail" id="summaryGrossProfitMargin">Margin: 0%</div>
                        </div>
                    </div>

                    <div class="metric-card metric-operating-expenses">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Operating Expenses</div>
                            <div class="metric-value" id="summaryOperatingExpenses">Rs 0.00</div>
                            <div class="metric-detail" id="summaryExpenseRatio">0% of revenue</div>
                        </div>
                    </div>

                    <div class="metric-card metric-operating-profit">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Operating Profit</div>
                            <div class="metric-value" id="summaryOperatingProfit">Rs 0.00</div>
                            <div class="metric-detail">EBIT</div>
                        </div>
                    </div>

                    <div class="metric-card metric-net-profit">
                        <div class="metric-icon-wrapper">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="metric-content">
                            <div class="metric-label">Net Profit</div>
                            <div class="metric-value" id="summaryNetProfit">Rs 0.00</div>
                            <div class="metric-detail" id="summaryNetProfitMargin">Margin: 0%</div>
                        </div>
                    </div>
                </div>

                <!-- Financial Ratios -->
                <div class="content-card">
                    <div class="card-header-primary">
                        <h5>📊 Key Financial Ratios</h5>
                    </div>
                    <div class="card-body">
                        <div class="ratios-grid">
                            <div class="ratio-item">
                                <div class="ratio-label">Gross Profit Margin</div>
                                <div class="ratio-value" id="ratioGrossProfitMargin">0%</div>
                                <div class="ratio-bar">
                                    <div class="ratio-fill ratio-fill-primary" id="barGrossProfitMargin" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="ratio-item">
                                <div class="ratio-label">Net Profit Margin</div>
                                <div class="ratio-value" id="ratioNetProfitMargin">0%</div>
                                <div class="ratio-bar">
                                    <div class="ratio-fill ratio-fill-success" id="barNetProfitMargin" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="ratio-item">
                                <div class="ratio-label">Expense Ratio</div>
                                <div class="ratio-value" id="ratioExpenseRatio">0%</div>
                                <div class="ratio-bar">
                                    <div class="ratio-fill ratio-fill-warning" id="barExpenseRatio" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="ratio-item">
                                <div class="ratio-label">COGS Ratio</div>
                                <div class="ratio-value" id="ratioCOGSRatio">0%</div>
                                <div class="ratio-bar">
                                    <div class="ratio-fill ratio-fill-danger" id="barCOGSRatio" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profit & Loss Statement -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>📄 Profit & Loss Statement</h5>
                    </div>
                    <div class="card-body">
                        <table class="pl-statement-table">
                            <tbody id="plStatementBody">
                                <tr>
                                    <td colspan="2" class="loading-cell">
                                        <div class="spinner"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. REVENUE TAB -->
            <div class="tab-pane" id="revenue">
                <!-- Revenue Breakdown -->
                <div class="grid-three-col">
                    <div class="stat-card stat-primary">
                        <div class="stat-label">Gross Sales</div>
                        <div class="stat-value" id="revenueGrossSales">Rs 0.00</div>
                        <div class="stat-detail" id="revenueSalesCount">0 transactions</div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-label">Total Discounts</div>
                        <div class="stat-value" id="revenueTotalDiscounts">Rs 0.00</div>
                        <div class="stat-detail">Given to customers</div>
                    </div>
                    <div class="stat-card stat-danger">
                        <div class="stat-label">Total Returns</div>
                        <div class="stat-value" id="revenueTotalReturns">Rs 0.00</div>
                        <div class="stat-detail">Refunded amount</div>
                    </div>
                </div>

                <!-- Payment Type Breakdown -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>💳 Payment Type Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-grid">
                            <div class="payment-card payment-cash">
                                <div class="payment-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-label">Cash Sales</div>
                                    <div class="payment-amount" id="paymentCashAmount">Rs 0.00</div>
                                    <div class="payment-count" id="paymentCashCount">0 transactions</div>
                                </div>
                            </div>
                            <div class="payment-card payment-card-type">
                                <div class="payment-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-label">Card Sales</div>
                                    <div class="payment-amount" id="paymentCardAmount">Rs 0.00</div>
                                    <div class="payment-count" id="paymentCardCount">0 transactions</div>
                                </div>
                            </div>
                            <div class="payment-card payment-credit">
                                <div class="payment-icon">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="payment-info">
                                    <div class="payment-label">Credit Sales</div>
                                    <div class="payment-amount" id="paymentCreditAmount">Rs 0.00</div>
                                    <div class="payment-detail">
                                        <span id="paymentCreditPaid">Paid: Rs 0.00</span> | 
                                        <span id="paymentCreditOutstanding" class="text-danger">Outstanding: Rs 0.00</span>
                                    </div>
                                    <div class="payment-count" id="paymentCreditCount">0 transactions</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Details Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>Revenue Breakdown Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <tbody id="revenueDetailsBody">
                                <tr>
                                    <td colspan="2" class="loading-cell">
                                        <div class="spinner"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. EXPENSES TAB -->
            <div class="tab-pane" id="expenses">
                <!-- Expense Summary -->
                <div class="grid-two-col">
                    <div class="stat-card stat-danger">
                        <div class="stat-label">Total Operating Expenses</div>
                        <div class="stat-value" id="expenseTotalAmount">Rs 0.00</div>
                        <div class="stat-detail" id="expenseCount">0 expense records</div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-label">Average Daily Expense</div>
                        <div class="stat-value" id="expenseAvgDaily">Rs 0.00</div>
                        <div class="stat-detail">Based on period</div>
                    </div>
                </div>

                <!-- Expense Breakdown Chart -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>📊 Expense Category Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="expenseChart" class="chart-canvas-lg"></canvas>
                    </div>
                </div>

                <!-- Expense Details Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>Expense Details by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-right">Count</th>
                                        <th class="text-right">% of Total</th>
                                        <th class="text-right">% of Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="expenseTableBody">
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

            <!-- 4. PROFITABILITY TAB -->
            <div class="tab-pane" id="profitability">
                <!-- Top Products by Profit -->
                <div class="content-card">
                    <div class="card-header-flex">
                        <h5>🏆 Top Products by Profitability</h5>
                        <div class="header-controls">
                            <select class="filter-select-sm" id="topProductsSortBy" onchange="loadTopProducts()">
                                <option value="revenue">By Revenue</option>
                                <option value="profit" selected>By Profit</option>
                                <option value="quantity">By Quantity</option>
                            </select>
                            <select class="filter-select-sm" id="topProductsLimit" onchange="loadTopProducts()">
                                <option value="5">Top 5</option>
                                <option value="10" selected>Top 10</option>
                                <option value="20">Top 20</option>
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
                                        <th class="text-right"><span style="color: #28a745">Profit</span> / <span style="color: #dc3545">Loss</span></th>
                                        <th class="text-right">Margin %</th>
                                    </tr>
                                </thead>
                                <tbody id="topProductsTableBody">
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

                <!-- Category Performance -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>📦 Category Profitability Analysis</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryProfitChart" class="chart-canvas"></canvas>
                        <div class="table-container table-margin-top">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th class="text-right">Revenue</th>
                                        <th class="text-right">Cost</th>
                                        <th class="text-right"><span style="color: #28a745">Profit</span> / <span style="color: #dc3545">Loss</span></th>
                                        <th class="text-right">Margin %</th>
                                        <th class="text-right">Contribution %</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryProfitTableBody">
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

            <!-- 5. BREAKDOWN TAB -->
            <div class="tab-pane" id="breakdown">
                <!-- Tax Information -->
                <div class="content-card">
                    <div class="card-header-success">
                        <h5>💵 Tax Collection Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="tax-summary-grid">
                            <div class="tax-item">
                                <div class="tax-label">Total Taxable Amount</div>
                                <div class="tax-value" id="taxTaxableAmount">Rs 0.00</div>
                            </div>
                            <div class="tax-item">
                                <div class="tax-label">Total Tax Collected</div>
                                <div class="tax-value" id="taxCollected">Rs 0.00</div>
                            </div>
                            <div class="tax-item">
                                <div class="tax-label">Average Tax Rate</div>
                                <div class="tax-value" id="taxAvgRate">0.00%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complete Financial Breakdown -->
                <div class="content-card">
                    <div class="card-header">
                        <h5>📋 Complete Financial Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="breakdown-section">
                            <h6 class="breakdown-title">💰 Revenue Analysis</h6>
                            <table class="breakdown-table">
                                <tbody id="breakdownRevenueBody">
                                    <tr>
                                        <td colspan="2" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="breakdown-section">
                            <h6 class="breakdown-title">📦 Cost of Goods Sold</h6>
                            <table class="breakdown-table">
                                <tbody id="breakdownCOGSBody">
                                    <tr>
                                        <td colspan="2" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="breakdown-section">
                            <h6 class="breakdown-title">💸 Operating Expenses</h6>
                            <table class="breakdown-table">
                                <tbody id="breakdownExpensesBody">
                                    <tr>
                                        <td colspan="2" class="loading-cell">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="breakdown-section">
                            <h6 class="breakdown-title">🎯 Final Profitability</h6>
                            <table class="breakdown-table">
                                <tbody id="breakdownProfitBody">
                                    <tr>
                                        <td colspan="2" class="loading-cell">
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let charts = {};
let financialData = null;
// Eye icon for toggle visibility
const eyeIcon = `
<svg class="toggle-visibility" onclick="toggleValue(this)" 
    width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path class="eye-open" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
    <circle class="eye-open" cx="12" cy="12" r="3"></circle>
    <path class="eye-closed" d="M3 3l18 18"></path>
</svg>
`;
document.addEventListener('DOMContentLoaded', function() {
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
    setTimeout(() => { loadFinancialReport(); }, 100);
});

function loadTabData(tabId) {
    if (!financialData) return;
    switch(tabId) {
        case 'summary': displaySummaryTab(financialData); break;
        case 'revenue': displayRevenueTab(financialData); break;
        case 'expenses': displayExpensesTab(financialData); break;
        case 'profitability': loadTopProducts(); loadCategoryPerformance(); break;
        case 'breakdown': displayBreakdownTab(financialData); break;
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

function getDateParams() {
    const dateRange = document.getElementById('dateRange').value;
    let params = { date_range: dateRange };
    if (dateRange === 'custom') {
        params.start_date = document.getElementById('startDate').value;
        params.end_date = document.getElementById('endDate').value;
    }
    return new URLSearchParams(params).toString();
}

function loadFinancialReport() {
    fetch(`/reports/financial/summary?${getDateParams()}`)
        .then(response => response.json())
        .then(data => {
            financialData = data;
            displaySummaryTab(data);
        })
        .catch(error => console.error('Error:', error));
}

function displaySummaryTab(data) {
    const netSalesEl = document.getElementById('summaryNetSales');
    netSalesEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.net_sales_revenue)}">****</span>`;
    document.getElementById('summaryGrossSales').textContent = 'Gross: ' + formatCurrency(data.sales_revenue.gross_sales);
    const cogsEl = document.getElementById('summaryCOGS');
    cogsEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.cogs.total_cogs)}">****</span>`;
    
    document.getElementById('summaryCOGSRatio').textContent = data.financial_ratios.cogs_ratio.toFixed(1) + '% of revenue';
    
    const grossProfitEl = document.getElementById('summaryGrossProfit');
    grossProfitEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.gross_profit.amount)}">****</span>`;
    document.getElementById('summaryGrossProfitMargin').textContent = 'Margin: ' + data.gross_profit.margin_percentage.toFixed(1) + '%';
    const opExpensesEl = document.getElementById('summaryOperatingExpenses');
    opExpensesEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_expenses.total_expenses)}">****</span>`;
    
    document.getElementById('summaryExpenseRatio').textContent = data.financial_ratios.expense_ratio.toFixed(1) + '% of revenue';
    
    const opProfitEl = document.getElementById('summaryOperatingProfit');
    opProfitEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_profit)}">****</span>`;
    
    const netProfitEl = document.getElementById('summaryNetProfit');
    netProfitEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.net_profit.amount)}">****</span>`;
    document.getElementById('summaryNetProfitMargin').textContent = 'Margin: ' + data.net_profit.margin_percentage.toFixed(1) + '%';
    
    document.getElementById('ratioGrossProfitMargin').textContent = data.financial_ratios.gross_profit_margin.toFixed(1) + '%';
    document.getElementById('ratioNetProfitMargin').textContent = data.financial_ratios.net_profit_margin.toFixed(1) + '%';
    document.getElementById('ratioExpenseRatio').textContent = data.financial_ratios.expense_ratio.toFixed(1) + '%';
    document.getElementById('ratioCOGSRatio').textContent = data.financial_ratios.cogs_ratio.toFixed(1) + '%';
    
    document.getElementById('barGrossProfitMargin').style.width = Math.min(data.financial_ratios.gross_profit_margin, 100) + '%';
    document.getElementById('barNetProfitMargin').style.width = Math.min(data.financial_ratios.net_profit_margin, 100) + '%';
    document.getElementById('barExpenseRatio').style.width = Math.min(data.financial_ratios.expense_ratio, 100) + '%';
    document.getElementById('barCOGSRatio').style.width = Math.min(data.financial_ratios.cogs_ratio, 100) + '%';
    
    displayPLStatement(data);
}

function displayPLStatement(data) {
    const tbody = document.getElementById('plStatementBody');
    tbody.innerHTML = `
        <tr class="pl-section-header"><td colspan="2"><strong>REVENUE</strong></td></tr>
        <tr><td class="pl-item-indent">Gross Sales</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.gross_sales)}">****</span></td></tr>
        <tr><td class="pl-item-indent">Less: Discounts</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_discounts)})</td></tr>
        <tr><td class="pl-item-indent">Less: Returns</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_returns)})</td></tr>
        <tr class="pl-subtotal"><td><strong>Net Sales Revenue</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.net_sales_revenue)}">****</span></strong></td></tr>
        <tr class="pl-spacer"><td colspan="2"></td></tr>
        <tr class="pl-section-header"><td colspan="2"><strong>COST OF GOODS SOLD</strong></td></tr>
        <tr><td class="pl-item-indent">Total COGS</td><td class="text-right text-danger">(${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.cogs.total_cogs)}">****</span>)</td></tr>
        <tr class="pl-subtotal"><td><strong>Gross Profit</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.gross_profit.amount)}">****</span></strong></td></tr>
        <tr class="pl-spacer"><td colspan="2"></td></tr>
        <tr class="pl-section-header"><td colspan="2"><strong>OPERATING EXPENSES</strong></td></tr>
        <tr><td class="pl-item-indent">Total Operating Expenses</td><td class="text-right text-danger">(${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_expenses.total_expenses)}">****</span>)</td></tr>
        <tr class="pl-subtotal"><td><strong>Operating Profit (EBIT)</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_profit)}">****</span></strong></td></tr>
 <tr class="pl-spacer"><td colspan="2"></td></tr>
        <tr class="pl-section-header"><td colspan="2"><strong>TAX</strong></td></tr>
        <tr><td class="pl-item-indent">Tax Collected</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.tax_info.total_tax_collected)}">****</span></td></tr>
        <tr class="pl-spacer"><td colspan="2"></td></tr>
        <tr class="pl-total"><td><strong>NET PROFIT</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.net_profit.amount)}">****</span></strong></td></tr>
    `;
}

function displayRevenueTab(data) {
    const grossSalesEl = document.getElementById('revenueGrossSales');
    grossSalesEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.gross_sales)}">****</span>`;    document.getElementById('revenueSalesCount').textContent = data.sales_revenue.sales_count + ' transactions';
    document.getElementById('revenueTotalDiscounts').textContent = formatCurrency(data.sales_revenue.total_discounts);
    document.getElementById('revenueTotalReturns').textContent = formatCurrency(data.sales_revenue.total_returns);
    
    document.getElementById('paymentCashAmount').textContent = formatCurrency(data.payment_breakdown.cash.amount);
    document.getElementById('paymentCashCount').textContent = data.payment_breakdown.cash.count + ' transactions';
    document.getElementById('paymentCardAmount').textContent = formatCurrency(data.payment_breakdown.card.amount);
    document.getElementById('paymentCardCount').textContent = data.payment_breakdown.card.count + ' transactions';
    document.getElementById('paymentCreditAmount').textContent = formatCurrency(data.payment_breakdown.credit.amount);
    document.getElementById('paymentCreditPaid').textContent = 'Paid: ' + formatCurrency(data.payment_breakdown.credit.paid);
    document.getElementById('paymentCreditOutstanding').textContent = 'Outstanding: ' + formatCurrency(data.payment_breakdown.credit.outstanding);
    document.getElementById('paymentCreditCount').textContent = data.payment_breakdown.credit.count + ' transactions';
    
    const revenueBody = document.getElementById('revenueDetailsBody');
    revenueBody.innerHTML = `
        <tr><td><strong>Gross Sales Revenue</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.gross_sales)}">****</span></strong></td></tr>
        <tr><td>Total Sales Count</td><td class="text-right">${data.sales_revenue.sales_count}</td></tr>
        <tr><td>Total Items Sold</td><td class="text-right">${parseFloat(data.sales_revenue.items_sold).toFixed(2)}</td></tr>
        <tr><td>Average Transaction Value</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.avg_transaction_value)}">****</span></td></tr>
        <tr class="table-divider"><td colspan="2"></td></tr>
        <tr><td>Total Discounts Given</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_discounts)})</td></tr>
        <tr><td>Total Returns</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_returns)})</td></tr>
        <tr class="table-divider"><td colspan="2"></td></tr>
        <tr class="table-total"><td><strong>Net Sales Revenue</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.net_sales_revenue)}">****</span></strong></td></tr>
    `;
}

function displayExpensesTab(data) {
    const expenseTotalEl = document.getElementById('expenseTotalAmount');
    expenseTotalEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_expenses.total_expenses)}">****</span>`;
    document.getElementById('expenseCount').textContent = data.operating_expenses.expense_count + ' expense records';
    
    const avgDaily = data.period.days > 0 ? data.operating_expenses.total_expenses / data.period.days : 0;
    const avgDailyEl = document.getElementById('expenseAvgDaily');
    avgDailyEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(avgDaily)}">****</span>`;
    
    if (data.operating_expenses.breakdown.length > 0) {
        if (charts.expenseChart) charts.expenseChart.destroy();
        const ctx = document.getElementById('expenseChart').getContext('2d');
        charts.expenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.operating_expenses.breakdown.map(e => e.category),
                datasets: [{
                    data: data.operating_expenses.breakdown.map(e => e.amount),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(201, 203, 207, 0.6)'
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
    
    const tbody = document.getElementById('expenseTableBody');
    if (data.operating_expenses.breakdown.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data">No expenses found</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.operating_expenses.breakdown.map((expense, index) => {
        const percentOfTotal = data.operating_expenses.total_expenses > 0 ? (expense.amount / data.operating_expenses.total_expenses) * 100 : 0;
        const percentOfRevenue = data.sales_revenue.net_sales_revenue > 0 ? (expense.amount / data.sales_revenue.net_sales_revenue) * 100 : 0;
        return `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${expense.category}</strong></td>
                <td class="text-right">${formatCurrency(expense.amount)}</td>
                <td class="text-right">${expense.count}</td>
                <td class="text-right">${percentOfTotal.toFixed(1)}%</td>
                <td class="text-right">${percentOfRevenue.toFixed(1)}%</td>
            </tr>
        `;
    }).join('');
}

function loadTopProducts() {
    const sortBy = document.getElementById('topProductsSortBy').value;
    const limit = document.getElementById('topProductsLimit').value;
    
    fetch(`/reports/financial/top-products?${getDateParams()}&sort_by=${sortBy}&limit=${limit}`)
        .then(response => response.json())
        .then(products => {
            const tbody = document.getElementById('topProductsTableBody');
            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="no-data">No products found</td></tr>';
                return;
            }
            tbody.innerHTML = products.map((product, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${product.product_name}</strong></td>
                    <td><span class="badge badge-secondary">${product.unit}</span></td>
                    <td class="text-right">${parseFloat(product.quantity_sold).toFixed(2)}</td>
                    <td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(product.revenue)}">****</span></td>
                    <td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(product.cost)}">****</span></td>
                    <td class="text-right ${product.profit >= 0 ? 'text-success' : 'text-danger'}">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(product.profit)}">****</span></td>
                    <td class="text-right"><span class="badge ${product.profit_margin >= 0 ? 'badge-success' : 'badge-danger'}">${parseFloat(product.profit_margin).toFixed(1)}%</span></td>
                </tr>
            `).join('');
        });
}

function loadCategoryPerformance() {
    fetch(`/reports/financial/category-performance?${getDateParams()}`)
        .then(response => response.json())
        .then(categories => {
            if (categories.length > 0) {
                if (charts.categoryProfitChart) charts.categoryProfitChart.destroy();
                const ctx = document.getElementById('categoryProfitChart').getContext('2d');
                charts.categoryProfitChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: categories.map(c => c.category),
                        datasets: [{
                            label: 'Revenue',
                            data: categories.map(c => c.revenue),
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Profit',
                            data: categories.map(c => c.profit),
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
            
            const tbody = document.getElementById('categoryProfitTableBody');
            if (categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">No categories found</td></tr>';
                return;
            }
            tbody.innerHTML = categories.map((cat, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${cat.category}</strong></td>
                    <td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(cat.revenue)}">****</span></td>
                    <td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(cat.cost)}">****</span></td>
                    <td class="text-right ${cat.profit >= 0 ? 'text-success' : 'text-danger'}">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(cat.profit)}">****</span></td>
                    <td class="text-right"><span class="badge ${cat.profit_margin >= 0 ? 'badge-success' : 'badge-danger'}">${cat.profit_margin.toFixed(1)}%</span></td>
                    <td class="text-right">${cat.contribution_percentage.toFixed(1)}%</td>
                </tr>
            `).join('');
        });
}

function displayBreakdownTab(data) {
    const taxableAmountEl = document.getElementById('taxTaxableAmount');
    taxableAmountEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.tax_info.total_taxable_amount)}">****</span>`;
    
    const taxCollectedEl = document.getElementById('taxCollected');
    taxCollectedEl.innerHTML = `${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.tax_info.total_tax_collected)}">****</span>`;
    document.getElementById('taxAvgRate').textContent = data.tax_info.avg_tax_rate.toFixed(2) + '%';
    
    document.getElementById('breakdownRevenueBody').innerHTML = `
        <tr><td>Gross Sales</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.gross_sales)}">****</span></td></tr>
        <tr><td>Less: Discounts</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_discounts)})</td></tr>
        <tr><td>Less: Returns</td><td class="text-right text-danger">(${formatCurrency(data.sales_revenue.total_returns)})</td></tr>
        <tr class="table-total"><td><strong>Net Sales Revenue</strong></td><td class="text-right"><strong>${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.sales_revenue.net_sales_revenue)}">****</span></strong></td></tr>
    `;
    
    document.getElementById('breakdownCOGSBody').innerHTML = `
        <tr><td>Total Cost of Goods Sold</td><td class="text-right text-danger">(${formatCurrency(data.cogs.total_cogs)})</td></tr>
        <tr><td>COGS as % of Revenue</td><td class="text-right">${data.financial_ratios.cogs_ratio.toFixed(1)}%</td></tr>
    `;
    
    const expenseRows = data.operating_expenses.breakdown.map(e => 
        `<tr><td>${e.category}</td><td class="text-right">${formatCurrency(e.amount)}</td></tr>`
    ).join('');
    document.getElementById('breakdownExpensesBody').innerHTML = `
        ${expenseRows}
        <tr class="table-total"><td><strong>Total Operating Expenses</strong></td><td class="text-right"><strong>${formatCurrency(data.operating_expenses.total_expenses)}</strong></td></tr>
    `;
    
    document.getElementById('breakdownProfitBody').innerHTML = `
        <tr><td>Gross Profit</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.gross_profit.amount)}">****</span></td></tr>
        <tr><td>Gross Profit Margin</td><td class="text-right">${data.gross_profit.margin_percentage.toFixed(1)}%</td></tr>
        <tr><td>Operating Profit (EBIT)</td><td class="text-right">${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.operating_profit)}">****</span></td></tr>
        <tr class="table-divider"><td colspan="2"></td></tr>
        <tr><td>Net Profit Margin</td><td class="text-right"><strong>${data.net_profit.margin_percentage.toFixed(1)}%</strong></td></tr>
        <tr class="table-total"><td><strong>Net Profit</strong></td><td class="text-right"><strong> ${eyeIcon} <span class="secure-value" data-value="${formatCurrency(data.net_profit.amount)}">****</span></strong></td></tr>
    `;
}

function formatCurrency(amount) {
    return 'Rs ' + parseFloat(amount).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function exportFinancialPDF() {
    const params = getDateParams();
    window.open('/reports/financial/export-pdf?' + params, '_blank');
}

function printReport() {
    window.print();
}
</script>
<script src="{{asset('js/salesReports/toggle_eye.js')}}"></script>
@endsection