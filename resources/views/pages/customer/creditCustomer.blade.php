@extends('layouts.app')

@section('title', 'Credit Customers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/delete_button.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/loading-spinner.css') }}">
<style>
    #customerSearchType {
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
    <h1 > Credit Customer List</h1>
</div>
<div class="container-child sub-text">
    <p>View all credit customers</p>
</div>
<div class="sub-container">
    <!-- <div class="add-button">
        <button onclick="window.location.href='{{route('purchase.add')}}'">
          <span>Add</span>
        </button></div> -->
    <div class="report-search-container">
    <div class="report-format">
        <button  id="openPrintPopup">Print</button>
        <button id="openPdfPopup">Pdf</button>
        <button onclick="exportToExcel()">Excel</button>
</div>
<div id="customerSearchbar-dropdown">
  <div style="display:flex;justify-content:center;gap:5px">
      <p style="position:relative;top:9px;">Search by:</p>
      <select id="customerSearchType" class="input" style="width: 140px;">
          <!-- <option value="voucher">Voucher No</option> -->
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
            <th>No.</th>
            <th>Customer Name</th>
            <th>Shop Name</th>
                    <th>Contact</th>
                    <th>City</th>
                    <th>Number of Sales</th>
                    <th>Last Balance</th>
                    <th>Last Purchase</th>
                    <th>View Purchases</th>
                    <th>Action</th>
        </tr>
    </thead>
    <tbody id="creditCustomerTableBody">
    <!-- Loading spinner -->
    <tr>
        <td colspan="10">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading credit customers...</p>
            </div>
        </td>
    </tr>
</tbody>
</table>

<div class="custom-pagination" id="paginationContainer">
    <!-- Pagination will be loaded here via AJAX -->
</div>
</div>
</div>
<!-- Delete Confirmation Modal (single instance reused for all rows) -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="delete-modal-content" role="document">
    <h3 style="margin-top:0">Delete Credit Customer</h3>
    <p>This will remove all <strong>credit sales</strong> of this customer.</p>
    <p>If this customer has no cash or card sales, the customer record will also be deleted automatically.</p>

    <form id="deleteForm" method="GET" action="">
      @csrf
      <input type="hidden" name="delete_option" id="deleteOption" value="">

      <div style="margin-top:12px;">
        <button type="button" class="btn btn-primary" onclick="submitDelete('only_sales')">Delete</button>  
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
            <a href="{{ route('customers.credit.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('customers.credit.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('customers.credit.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('customers.credit.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script>
// Global variables
let currentPage = 1;
let allCreditCustomers = [];

// Load credit customers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCreditCustomers(1);
});

// Function to load credit customers via AJAX
function loadCreditCustomers(page) {
    currentPage = page;
    
    fetch(`{{ route('customers.credit.data') }}?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allCreditCustomers = data.data;
            renderTable(data.data, data.pagination);
            renderPagination(data.pagination);
            filterTable(); // Apply any active filters
        } else {
            showError('Failed to load credit customers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading credit customers');
    });
}
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
function renderTable(customers, pagination) {
    const tbody = document.getElementById('creditCustomerTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10">No Record found.</td></tr>';
        return;
    }
    
    let html = '';
    customers.forEach((customer, index) => {
        const rowNumber = pagination.first_item + index;
        const latestCreditSale = customer.sales && customer.sales.length > 0 ? customer.sales[0] : null;
        
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td>${customer.name || 'Walk-in Customer'}</td>
                <td>${customer.shop_name || 'N/A'}</td>
                <td>${customer.contact || 'N/A'}</td>
                <td>${customer.city || 'N/A'}</td>
                <td>${customer.credit_sales_count || 0}</td>
                <td>${customer.current_balance || 0}</td>
                <td>${latestCreditSale ? formatDateTime(latestCreditSale.created_at) : 'N/A'}</td>
                <td><a href="/customers/credit/${customer.id}/purchases"><i style="font-size:18px; margin-left:13px; color:#5c6670" class="fa fa-eye"></i></a></td>
                <td>
                    <button type="button"
                        id="delete-button"
                        class="delete-button"
                        data-action="/customers/credit/${customer.id}/delete"
                        onclick="openDeleteModal(this)">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Render pagination (same as customer page)
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
        html += `<a href="javascript:void(0)" onclick="loadCreditCustomers(1)">« First</a>`;
        html += `<a href="javascript:void(0)" onclick="loadCreditCustomers(${pagination.current_page - 1})">←</a>`;
    }
    
    const start = Math.max(pagination.current_page - 2, 1);
    const end = Math.min(pagination.current_page + 2, pagination.last_page);
    
    if (start > 1) html += '<span class="dots">...</span>';
    
    for (let page = start; page <= end; page++) {
        if (page === pagination.current_page) {
            html += `<span class="active">${page}</span>`;
        } else {
            html += `<a href="javascript:void(0)" onclick="loadCreditCustomers(${page})">${page}</a>`;
        }
    }
    
    if (end < pagination.last_page) html += '<span class="dots">...</span>';
    
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadCreditCustomers(${pagination.current_page + 1})">→</a>`;
        html += `<a href="javascript:void(0)" onclick="loadCreditCustomers(${pagination.last_page})">Last »</a>`;
    } else {
        html += '<span class="disabled">→</span>';
        html += '<span class="disabled">Last »</span>';
    }
    
    container.innerHTML = html;
}

// Search functionality
const searchInput = document.getElementById("customerSearchInput");
const searchType = document.getElementById("customerSearchType");

function fuzzyMatch(text, token) {
    let tIndex = 0;
    for (let i = 0; i < text.length && tIndex < token.length; i++) {
        if (text[i] === token[tIndex]) tIndex++;
    }
    return tIndex === token.length;
}

function filterTable() {
    const filter = searchInput.value.toLowerCase().trim();
    const tokens = filter.split(/\s+/);
    const type = searchType.value;

    const tbody = document.getElementById('creditCustomerTableBody');
    const rows = tbody.querySelectorAll('tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        const customerName = (cells[1]?.innerText || '').toLowerCase();
        const shopName = (cells[2]?.innerText || '').toLowerCase();
        const contact = (cells[3]?.innerText || '').toLowerCase();

        let textToSearch = '';
        if (type === 'customer') textToSearch = customerName;
        else if (type === 'shop_name') textToSearch = shopName;
        else if (type === 'contact') textToSearch = contact;

        const match = tokens.every(token => textToSearch.includes(token) || fuzzyMatch(textToSearch, token));
        row.style.display = match ? '' : 'none';
    });
}

searchInput.addEventListener('input', filterTable);
searchType.addEventListener('change', filterTable);

function showError(message) {
    const tbody = document.getElementById('creditCustomerTableBody');
    tbody.innerHTML = `<tr><td colspan="10" style="color: red; text-align: center;">${message}</td></tr>`;
}
</script>
<script src="{{asset('js/delete_modal.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>

@endsection
