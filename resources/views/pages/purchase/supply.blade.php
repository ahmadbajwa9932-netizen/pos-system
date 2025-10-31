@extends('layouts.app')

@section('title', 'suppliers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/delete_modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<style>
    .dropdown {
    position: relative;
}
.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 1000;
    min-width: 120px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
#delete-button{
        display: block;
        text-align:center;
  width: 100%;
  padding: 8px 12px;
  font-size: 14px;
  color: #000;
  background: transparent;
  border: none;
  cursor: pointer;
  text-decoration: none; 
    }

    #delete-button:hover{
        background: #f0f0f0; /* same as anchor hover */
        color: #000;
    }

    /* Loading spinner styles */
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
    <p>View and manage store suppliers</p>
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
            <th>Supplier Name</th>
            <th>Campany Name</th>
            <th>Address</th>
            <th>Contact Info</th>
            <th>Total Purchases</th>
            <th>Total Purchases (Rs)</th>
            <th>See Details</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="supplierTableBody">
    <tr>
        <td colspan="9">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading suppliers...</p>
            </div>
        </td>
    </tr>
</tbody>
</table>

    <div class="custom-pagination" id="paginationContainer">
        <!-- Pagination links will be dynamically inserted here -->
    </div>
    
<!-- Delete Confirmation Modal (single instance reused for all rows) -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="delete-modal-content" role="document">
    <h3 style="margin-top:0">Delete Supplier</h3>
    <p>Choose an option — this action uses <strong>soft delete</strong> and can be restored from Recycle Bin.</p>

    <form id="deleteForm" method="GET" action="">
      @csrf
      <input type="hidden" name="delete_option" id="deleteOption" value="">

      <div style="margin-top:12px;">
        <button type="button" class="btn btn-primary" onclick="submitDelete('only')">Delete Only Supplier</button>
        <button type="button" class="btn btn-danger" onclick="submitDelete('with_purchases')">Delete Supplier &amp; Purchases</button>
      </div>

      <div style="margin-top:14px;">
        <button type="button" class="btn btn-neutral" onclick="closeDeleteModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

</div>
</div>

<!-- PDF Popup Modal -->
<div class="pdfModal" id="pdfPopup">
    <div class="pdf-modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('supplier.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('supplier.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('supplier.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('supplier.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script src="{{asset('js/search.js')}}"></script>
<script>
// Global variables
let currentPage = 1;

// Load suppliers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers(1);
    
    // Check for flash messages in URL
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        showPopupMessage(successMsg, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (errorMsg) {
        showPopupMessage(errorMsg, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Function to load suppliers via AJAX
function loadSuppliers(page) {
    currentPage = page;
    
    fetch(`{{ route('supplier.data') }}?page=${page}`, {
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
            showError('Failed to load suppliers');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading suppliers');
    });
}

// Render table rows
function renderTable(suppliers, pagination) {
    const tbody = document.getElementById('supplierTableBody');
    
    if (suppliers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9">No Record found.</td></tr>';
        return;
    }
    
    let html = '';
    suppliers.forEach((supplier, index) => {
        const rowNumber = pagination.first_item + index;
        
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td>${supplier.name ?? 'N/A'}</td>
                <td>${supplier.company ?? 'N/A'}</td>
                <td>${supplier.address ?? 'N/A'}</td>
                <td>${supplier.contact_info ?? 'N/A'}</td>
                <td>${supplier.purchases_count}</td>
                <td>Rs ${formatNumber(supplier.total_purchase_amount)}</td>
                <td>
                    <a href="/supplier/show/${supplier.id}">
                        <i style="font-size:18px; margin-left:13px; color:#5c6670;" class="fa fa-eye"></i>
                    </a>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle">⋮</button>
                        <div class="dropdown-menu">
                            <a href="/supplier/${supplier.id}/edit">Edit</a>
                            <button type="button"
                                id="delete-button"
                                data-action="/supplier/${supplier.id}/delete"
                                onclick="openDeleteModal(this)">
                                Delete
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    attachDropdownListeners();
}

// Render pagination (simplified for simplePaginate)
function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    let html = '';
    
    // Previous button
    if (pagination.on_first_page) {
        html += '<span class="disabled">←</span>';
    } else {
        html += `<a href="javascript:void(0)" onclick="loadSuppliers(${pagination.current_page - 1})">←</a>`;
    }
    
    // Current page number
    html += `<span class="active">${pagination.current_page}</span>`;
    
    // Next button
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadSuppliers(${pagination.current_page + 1})">→</a>`;
    } else {
        html += '<span class="disabled">→</span>';
    }
    
    container.innerHTML = html;
}

// Delete modal functions
function openDeleteModal(button) {
    const deleteUrl = button.getAttribute('data-action');
    document.getElementById('deleteForm').action = deleteUrl;
    document.getElementById('deleteModal').style.display = 'flex';
    document.getElementById('deleteModal').setAttribute('aria-hidden', 'false');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('deleteModal').setAttribute('aria-hidden', 'true');
}

function submitDelete(option) {
    const form = document.getElementById('deleteForm');
    document.getElementById('deleteOption').value = option;
    form.submit();
}

// Attach dropdown listeners
function attachDropdownListeners() {
    let activeMenu = null;

    function showMenuUnderButton(menu, button) {
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }

        if (menu.style.display === "block") {
            menu.style.display = "none";
            activeMenu = null;
        } else {
            menu.style.display = "block";
            menu.style.position = "absolute";
            menu.style.right = "0";
            menu.style.top = button.offsetHeight + "px";
            activeMenu = menu;
        }
    }

    document.querySelectorAll(".dropdown-toggle").forEach(button => {
        button.addEventListener("click", function (e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            showMenuUnderButton(menu, this);
        });
    });

    document.addEventListener("click", function () {
        if (activeMenu) {
            activeMenu.style.display = "none";
            activeMenu = null;
        }
    });
}

// Helper functions
function formatNumber(num) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function showError(message) {
    const tbody = document.getElementById('supplierTableBody');
    tbody.innerHTML = `<tr><td colspan="9" style="color: red; text-align: center;">${message}</td></tr>`;
}

function showPopupMessage(message, type) {
    let popup = document.getElementById('popup-message');
    if (!popup) {
        popup = document.createElement('div');
        popup.id = 'popup-message';
        popup.className = 'popup';
        document.body.insertBefore(popup, document.body.firstChild);
    }
    popup.textContent = message;
    popup.className = `popup ${type}`;
    popup.style.display = 'block';
    
    setTimeout(() => {
        popup.style.display = 'none';
    }, 3000);
}

// PDF/Print popup functions
document.getElementById('openPdfPopup')?.addEventListener('click', function() {
    document.getElementById('pdfPopup').style.display = 'flex';
});

document.getElementById('closePdfPopup')?.addEventListener('click', function() {
    document.getElementById('pdfPopup').style.display = 'none';
});

document.getElementById('openPrintPopup')?.addEventListener('click', function() {
    document.getElementById('printPopup').style.display = 'flex';
});

document.getElementById('closePrintPopup')?.addEventListener('click', function() {
    document.getElementById('printPopup').style.display = 'none';
});

// Export to Excel function
function exportToExcel() {
    alert('Excel export functionality');
}
</script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
<script src="{{asset('js/delete_modal.js')}}"></script>
@endsection
