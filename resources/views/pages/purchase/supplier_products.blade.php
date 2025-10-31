@extends('layouts.app')

@section('title', 'suppliers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<!-- <link rel="stylesheet" href="{{ asset('css/components/popup.css') }}"> -->
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">

<style>
.loading-spinner {
    text-align: center;
    padding: 40px;
    font-size: 16px;
    color: #666;
}
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
    <h1 >Suppliers Directory</h1>
</div>
<div class="container-child sub-text">
    <p>Products supplied by {{$supplier->name ?? 'N/A'}}</p>
</div>
<div class="sub-container">
    <!-- <div class="add-button">
        <button onclick="window.location.href='{{route('purchase.add')}}'">
          <span>Add</span>
        </button></div> -->
    <div class="report-search-container">
    <div class="report-format">
        <button id="openPrintPopup">Print</button>
        <button id="openPdfPopup">Pdf</button>
        <button onclick="exportToExcel()">Excel</button>
</div>
<!-- From Uiverse.io by boryanakrasteva --> 
<div class="search-container">
    <input type="text" name="text" id="singleInputSearch" class="input" placeholder="Search...">
    <span class="icon1"> 
      <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="1" d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M22 22L20 20" stroke="#000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
    </span>
  </div>
</div>
<table id="singleSearchTable">
    <thead>
        <tr>
            <th>No.</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>Purchased Price</th>
            <!-- <th>S. Price</th> -->
            <th>Quantity</th>
            <th>Unit</th>
            <th>Total</th>
            <th>Date</th>
            <!-- <th>See Details</th>
            <th>Actions</th> -->
        </tr>
    </thead>
    <tbody id="purchasesTableBody">
    <!-- Loading spinner -->
    <tr>
        <td colspan="8">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading purchases...</p>
            </div>
        </td>
    </tr>
</tbody>
</table>


<div class="custom-pagination"  id="paginationContainer">
    </div>
</div>
</div>
<!-- PDF Popup Modal -->
<div class="pdfModal" id="pdfPopup">
    <div class="pdf-modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('supplier.purchases.pdf.current',$supplier->id) }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('supplier.purchases.pdf.all',$supplier->id) }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('supplier.purchases.print.current',$supplier->id) }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('supplier.purchases.print.all',$supplier->id) }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script>
// Get supplier ID from the page
const supplierId = {{ $supplier->id }};
let currentPage = 1;

// Load purchases on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPurchases(1);
});

// Function to load purchases via AJAX
function loadPurchases(page) {
    currentPage = page;
    
    fetch(`/supplier/show/${supplierId}/purchases?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderTable(data.data, data.pagination);
            renderPagination(data.pagination);
        } else {
            showError('Failed to load purchases');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading purchases');
    });
}

// Render table rows - ADJUST COLUMNS based on your actual table structure
function renderTable(purchases, pagination) {
    const tbody = document.getElementById('purchasesTableBody');
    
    if (purchases.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">No purchases found.</td></tr>';
        return;
    }
    
    let html = '';
    purchases.forEach((purchase, index) => {
        const rowNumber = pagination.first_item + index;
        
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td>${purchase.product_name}</td>
                <td>${purchase.category ? purchase.category.name : 'N/A'}</td>
                <td>Rs ${formatNumber(purchase.purchased_price)}</td>
                <td>${purchase.quantity}</td>
                <td>${purchase.unit}</td>
                <td>Rs ${formatNumber(purchase.purchased_price * purchase.quantity)}</td>
                <td>${formatDate(purchase.purchase_date)}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    let html = '';
    
    if (pagination.on_first_page) {
        html += '<span class="disabled">←</span>';
    } else {
        html += `<a href="javascript:void(0)" onclick="loadPurchases(${pagination.current_page - 1})">←</a>`;
    }
    
    html += `<span class="active">${pagination.current_page}</span>`;
    
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadPurchases(${pagination.current_page + 1})">→</a>`;
    } else {
        html += '<span class="disabled">→</span>';
    }
    
    container.innerHTML = html;
}

// Helper functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'N/A';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function formatNumber(num) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function showError(message) {
    const tbody = document.getElementById('purchasesTableBody');
    tbody.innerHTML = `<tr><td colspan="8" style="color: red; text-align: center;">${message}</td></tr>`;
}
</script>
<script src="{{asset('js/search.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection
