@extends('layouts.app')

@section('title', 'customers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_button.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/loading-spinner.css') }}">
<style>
    #customerSearchType, #customerTypeFilter {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}
#customerSearchType:focus {
    border-color: #007bff;
}
#customerSearchbar-dropdown{
    display: flex;
    justify-content: center;
    gap:5px;
}

.customerTypeDropdown{
    display: flex; 
    justify-content: flex-end; 
}

/* used to hide rows during search (strong override so it can't be accidentally overridden) */
.search-hidden { display: none !important; }

/* marker for rows explicitly opened by the toggle (so we don't auto-close them) */
.open-by-toggle {}

/* optional: ensure nested table uses normal table display if CSS sets it otherwise */
.sub-container table { width: 100%; }
</style>
@endpush

@section('content')
@if(session('success') || session('error'))
    <div id="popup-message" class="popup {{ session('success') ? 'success' : 'error' }}">
        {{ session('success') ?? session('error') }}
    </div>
@endif
<div class="container">
    <div class="container-child main-text">
    <h1 >Customer List</h1>
</div>
<div class="container-child sub-text">
    <p>View all customers</p>
</div>
<div class="sub-container">
        <div class="customerTypeDropdown">
    <select id="customerTypeFilter" class="input" style="width: 200px;">
        <option value="all">All Customers</option>
        <option value="credit">Credit Customers</option>
        <option value="cash">Cash Customers</option>
        <option value="card">Card Customers</option>
    </select>
</div>
    <div class="report-search-container">
    <div class="report-format">
        <button id="openPrintPopup">Print</button>
        <button id="openPdfPopup">Pdf</button>
        <button onclick="exportToExcel()">Excel</button>
</div>
<div id="customerSearchbar-dropdown">
  <div style="display:flex;justify-content:center;gap:5px">
      <p style="position:relative;top:9px;">Search by:</p>
      <select id="customerSearchType" class="input" style="width: 140px;">
          <option value="customer">Customer</option>
          <option value="shop_name">Shop Name</option>
          <option value="contact">Contact</option>
      </select>
  </div>
<div class="search-container">
    <input type="text" name="text" id="customerSearchInput" class="input" placeholder="Search...">
    <span class="icon1"> 
      <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="1" d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M22 22L20 20" stroke="#000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
    </span>
  </div>
</div>
</div>
<table id="customerSalesTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Customer Name</th>
            <th>Shop Name</th>
            <th>Contact</th>
            <th>City</th>
            <th>Total Sales</th>
            <th>Has Credit Sales</th>
            <th>Last Purchase</th>
            <th>View Purchases</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="customerTableBody">
    <!-- Loading spinner -->
    <tr>
        <td colspan="10">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading customers...</p>
            </div>
        </td>
    </tr>
</tbody>
</table>

    <div class="custom-pagination" id="paginationContainer">
    </div>
</div>
</div>
<!-- Delete Confirmation Modal (single instance reused for all rows) -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="delete-modal-content" role="document">
    <h3 style="margin-top:0">Delete Customer</h3>
    <p>This action uses <strong>soft delete</strong> and can be restored from Recycle Bin.</p>
    <p>This will remove the selected customer and all related sales record.</p>

    <form id="deleteForm" method="GET" action="">
      @csrf
      <input type="hidden" name="delete_option" id="deleteOption" value="">

      <div style="margin-top:12px;">
        <button type="button" class="btn btn-primary" onclick="submitDelete('only')">Delete only Customer</button>  
        <button type="button" class="btn btn-danger" onclick="submitDelete('with_sales')">Delete Customer &amp; Sales</button>
        </div>
      <div style="margin-top:14px;">
        <button type="button" class="btn btn-neutral" onclick="closeDeleteModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
<!-- PDF Popup Modal -->
<div class="pdfModal" id="pdfPopup">
    <div class="pdf-modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('customers.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('customers.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('customers.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('customers.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script>
// Global variables
let currentPage = 1;
let allCustomers = [];
let allSalesBreakdown = {};

// Load customers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCustomers(1);
});

// Function to load customers via AJAX
function loadCustomers(page) {
    currentPage = page;
    
    fetch(`{{ route('customers.data') }}?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allCustomers = data.data;
            allSalesBreakdown = data.salesBreakdown;
            renderTable(data.data, data.salesBreakdown, data.pagination);
            renderPagination(data.pagination);
            attachToggleHandlers();
            filterTable(); // Apply any active filters
        } else {
            showError('Failed to load customers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading customers');
    });
}

// Helper functions
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);

    const day = String(date.getDate()).padStart(2, '0');

    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", 
                        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const month = monthNames[date.getMonth()];

    const year = date.getFullYear();

    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12;

    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
}

// Render table rows
function renderTable(customers, salesBreakdown, pagination) {
    const tbody = document.getElementById('customerTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10">No Record found.</td></tr>';
        return;
    }
    
    let html = '';
    customers.forEach((customer, index) => {
        const rowNumber = pagination.first_item + index;
        const hasCreditSales = salesBreakdown[customer.id] && salesBreakdown[customer.id].credit && salesBreakdown[customer.id].credit.count > 0;
        const lastPurchase = customer.sales && customer.sales.length > 0 ? customer.sales[0].created_at : null;
        
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td style="text-align:left;">
                    <button class="toggle-details" data-id="${customer.id}" style="background:none;border:none;cursor:pointer;">
                        &#x25BC;
                    </button>
                    ${customer.name || 'Walk-in Customer'}
                </td>
                <td>${customer.shop_name || 'N/A'}</td>
                <td>${customer.contact || 'N/A'}</td>
                <td>${customer.city || 'N/A'}</td>
                <td>${customer.sales_count || 0}</td>
                <td>${hasCreditSales ? 'Yes' : 'No'}</td>
                <td>${formatDateTime(lastPurchase) || 'N/A'}</td>
                <td><a href="/customers/${customer.id}/purchases"><i style="font-size:18px; margin-left:13px; color:#5c6670" class="fa fa-eye"></i></a></td>
                <td>
                    <button type="button"
                        id="delete-button"
                        class="delete-button"
                        data-action="/customers/${customer.id}/delete"
                        onclick="openDeleteModal(this)">
                        Delete
                    </button>
                </td>
            </tr>
            <tr class="details-row" id="details-${customer.id}" style="display:none;background:#f9f9f9;">
                <td colspan="10">
                    <table class="table table-sm" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th>Number of Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${renderSalesBreakdown(customer.id, salesBreakdown)}
                        </tbody>
                    </table>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Render sales breakdown
function renderSalesBreakdown(customerId, salesBreakdown) {
    const breakdown = salesBreakdown[customerId] || {};
    const types = ['credit', 'cash', 'card'];
    let html = '';
    let hasData = false;
    
    types.forEach(type => {
        if (breakdown[type]) {
            html += `
                <tr>
                    <td>${type.charAt(0).toUpperCase() + type.slice(1)}</td>
                    <td>${breakdown[type].count}</td>
                </tr>
            `;
            hasData = true;
        }
    });
    
    if (!hasData) {
        html = '<tr><td colspan="2">No sales found.</td></tr>';
    }
    
    return html;
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.last_page <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    if (pagination.on_first_page) {
        html += '<span class="disabled">« First</span>';
        html += '<span class="disabled">←</span>';
    } else {
        html += `<a href="javascript:void(0)" onclick="loadCustomers(1)">« First</a>`;
        html += `<a href="javascript:void(0)" onclick="loadCustomers(${pagination.current_page - 1})">←</a>`;
    }
    
    const start = Math.max(pagination.current_page - 2, 1);
    const end = Math.min(pagination.current_page + 2, pagination.last_page);
    
    if (start > 1) html += '<span class="dots">...</span>';
    
    for (let page = start; page <= end; page++) {
        if (page === pagination.current_page) {
            html += `<span class="active">${page}</span>`;
        } else {
            html += `<a href="javascript:void(0)" onclick="loadCustomers(${page})">${page}</a>`;
        }
    }
    
    if (end < pagination.last_page) html += '<span class="dots">...</span>';
    
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadCustomers(${pagination.current_page + 1})">→</a>`;
        html += `<a href="javascript:void(0)" onclick="loadCustomers(${pagination.last_page})">Last »</a>`;
    } else {
        html += '<span class="disabled">→</span>';
        html += '<span class="disabled">Last »</span>';
    }
    
    container.innerHTML = html;
}

// Toggle expandable rows
function attachToggleHandlers() {
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const detailsRow = document.getElementById('details-' + id);
            if (!detailsRow) return;

            const isOpen = detailsRow.classList.contains('open-by-toggle');

            if (isOpen) {
                detailsRow.classList.remove('open-by-toggle');
                detailsRow.style.display = 'none';
                this.innerHTML = '\u25BC';
            } else {
                detailsRow.style.display = 'table-row';
                detailsRow.classList.add('open-by-toggle');
                this.innerHTML = '\u25B2';
            }
        });
    });
}

// Search and filter functionality
const searchInput = document.getElementById("customerSearchInput");
const searchType = document.getElementById("customerSearchType");
const customerTypeFilter = document.getElementById("customerTypeFilter");

function fuzzyMatch(text, token) {
    if (!token) return true;
    let tIndex = 0;
    for (let i = 0; i < text.length && tIndex < token.length; i++) {
        if (text[i] === token[tIndex]) tIndex++;
    }
    return tIndex === token.length;
}

function filterTable() {
    const filter = searchInput.value.toLowerCase().trim();
    const tokens = filter === '' ? [] : filter.split(/\s+/);
    const type = searchType.value;
    const selectedCustomerType = customerTypeFilter.value;

    const tbody = document.getElementById('customerTableBody');
    const mainRows = tbody.querySelectorAll('tr:not(.details-row)');

    mainRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells || cells.length < 2) return;

        const customerName = (cells[1]?.innerText || '').toLowerCase();
        const shopName = (cells[2]?.innerText || '').toLowerCase();
        const contact = (cells[3]?.innerText || '').toLowerCase();
        const nextRow = row.nextElementSibling;

        let textToSearch = '';
        if (type === 'customer') textToSearch = customerName;
        else if (type === 'shop_name') textToSearch = shopName;
        else if (type === 'contact') textToSearch = contact;

        let matchesSearch = tokens.length === 0 || tokens.every(token => textToSearch.includes(token) || fuzzyMatch(textToSearch, token));

        let matchesType = true;
        if (selectedCustomerType !== 'all' && nextRow && nextRow.classList.contains('details-row')) {
            const nestedTable = nextRow.querySelector('table');
            if (nestedTable) {
                const paymentTypes = Array.from(nestedTable.querySelectorAll('tbody tr td:first-child')).map(td => td.innerText.toLowerCase());
                matchesType = paymentTypes.includes(selectedCustomerType);
            }
        }

        if (matchesSearch && matchesType) {
            row.style.display = '';
            if (nextRow && nextRow.classList.contains('details-row')) {
                if (!nextRow.classList.contains('open-by-toggle')) {
                    nextRow.style.display = 'none';
                }
            }
        } else {
            row.style.display = 'none';
            if (nextRow && nextRow.classList.contains('details-row')) {
                nextRow.style.display = 'none';
            }
        }
    });
}

searchInput.addEventListener('input', filterTable);
searchType.addEventListener('change', filterTable);
customerTypeFilter.addEventListener('change', filterTable);

function showError(message) {
    const tbody = document.getElementById('customerTableBody');
    tbody.innerHTML = `<tr><td colspan="10" style="color: red; text-align: center;">${message}</td></tr>`;
}
</script>
<script src="{{asset('js/delete_modal.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection