@extends('layouts.app')

@section('title', 'Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/add_button.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<style>
.dropdown {
    position: relative;
}
#searchType {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}
#searchType:focus {
    border-color: #007bff;
}
#searchbar-dropdown{
    display: flex;
    justify-content: center;
    gap:5px;
}

.spinner::-webkit-outer-spin-button,
.spinner::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
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

    .danger-row {
    background-color: #ffe6e6 !important;
    color:rgb(107, 92, 92) !important;
    
}
    .restock-btn {
        background: #007bff;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
    }
    .restock-btn:hover {
        background: #0056b3;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        text-align: center;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        & form{
            margin-top:10px;
           & .modal-field{
            display:flex;
            flex-direction:row;
            justify-content:flex-end;
            align-items:center;
            }
        } 
    }


    .modal-content input {
        width: 80%;
        padding: 8px;
        /* margin-top: 10px; */
    }
    .close-btn {
        background: #ccc;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        margin-top: 10px;
        cursor: pointer;
    }

    #restock-btn,#delete-button{
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

    #restock-btn:hover,#delete-button:hover{
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
        <h1>Products in the Store</h1>
    </div>

    <div class="container-child sub-text">
        <p>View Purchase Products</p>
    </div>

    <div class="sub-container">
        <div class="add-button">
            <button onclick="window.location.href='{{ route('purchase.add') }}'">
                <span>Add</span>
            </button>
        </div>

        <div class="report-search-container">
            <div class="report-format">
                <button id="openPrintPopup">Print</button> 
                <button id="openPdfPopup">PDF</button>
                <button onclick="exportToExcel()">Excel</button>
            </div>
            <div id="searchbar-dropdown">
<div style="display:flex;justify-content:center;gap:5px;">
            <p style="position:relative;top:9px;">Search by:</p>
            <select id="searchType" class="input" style="width: 140px;">
        <option value="product">Product Name</option>
        <option value="category">Category</option>
        <option value="unit">Unit</option>
        <option value="quantity">Quantity</option>
    </select>
    </div>
            <div class="search-container" id="search-container">
                <input type="text" id="purchaseSearch" name="text" class="input" placeholder="Search...">
                <!-- Quantity Range Search (Hidden by default) -->
    <div id="quantityRangeInputs" style="display:none; gap:5px; align-items:center;">
        <label>From:</label>
        <input type="number" id="quantityFrom" class="input spinner" style="width:80px;">
        <label>To:</label>
        <input type="number" id="quantityTo" class="input spinner" style="width:80px;">
    </div>
                <span class="icon1"> 
                    <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M22 22L20 20" stroke="#000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </span>
            </div>
</div>
        </div>

        <table id="purchaseTable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Category</th>
                    <!-- <th>P.Price</th> -->
                    <!-- <th>S.Price</th> -->
                    <!-- <th>Quantity</th> -->
                    <th>Unit</th>
                    <!-- <th>Total</th> -->
                    <!-- <th>Sold Quantity</th> -->
                    <th>Remaining Quantity</th>
                    <th>Date</th>
                    <!-- <th>See Details</th> -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="purchaseTableBody">
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

        <div class="custom-pagination" id="paginationContainer">
    <!-- Pagination will be loaded here via AJAX -->
</div>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal" id="restockModal">
    <div class="modal-content">
        <h3 id="restockProductName"></h3>
        <form id="restockForm" method="POST" action="{{ route('purchase.restock') }}">
            @csrf
            <input type="hidden" name="purchase_id" id="purchaseId">
            <div class="modal-field">
            <label>Quantity:</label>
            <input type="number" name="quantity" placeholder="Enter quantity to add" required>
            </div>
            <br><br>
            <div class="modal-field">
            <label>Purchase Date:</label>
            <input type="date" name="purchase_date" id="purchaseDate" required>
</div>
            <br>
            <button type="submit" class="restock-btn">Update Stock</button>
            <button type="button" class="close-btn" onclick="closeRestockModal()">Cancel</button>
        </form>
    </div>
</div>
<!-- Delete Confirmation Modal (single instance reused for all rows) -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="delete-modal-content" role="document">
    <h3 style="margin-top:0">Delete Purchase</h3>
    <p>This action uses <strong>soft delete</strong> and can be restored from Recycle Bin.</p>
    <p>This will remove the selected purchase record. Sales and return history will remain unaffected.</p>

    <form id="deleteForm" method="GET" action="">
      @csrf
      <input type="hidden" name="delete_option" id="deleteOption" value="">

      <div style="margin-top:12px;">
        <button type="button" class="btn btn-primary" onclick="submitDelete('only')">Delete Purchase</button>      </div>
      <div style="margin-top:14px;">
        <button type="button" class="btn btn-neutral" onclick="closeDeleteModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- PDF Popup Modal -->
<div class="modal" id="pdfPopup">
    <div class="modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('purchase.pdf.current') }}" class="restock-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('purchase.pdf.all') }}" class="restock-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>

<!-- Print Popup Modal -->
<div class="modal" id="printPopup">
    <div class="modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('purchase.print.current') }}" class="restock-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('purchase.print.all') }}" class="restock-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script>
// Global variables
let currentPage = 1;
let allPurchases = [];

// Load purchases on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPurchases(1);
    
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

// Function to load purchases via AJAX
function loadPurchases(page) {
    currentPage = page;
    
    fetch(`{{ route('purchase.data') }}?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allPurchases = data.data;
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

// Render table rows
function renderTable(purchases, pagination) {
    const tbody = document.getElementById('purchaseTableBody');
    
    if (purchases.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No purchases found</td></tr>';
        return;
    }
    
    let html = '';
    purchases.forEach((purchase, index) => {
        const rowNumber = pagination.first_item + index;
        const remaining = purchase.quantity - purchase.sold_quantity;
        
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td>${purchase.product_name}</td>
                <td>${purchase.category ? purchase.category.name : 'N/A'}</td>
                <td>${purchase.unit}</td>
                <td>${remaining}</td>
                <td>${formatDate(purchase.purchase_date)}</td>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle">⋮</button>
                        <div class="dropdown-menu">
                            <a href="javascript:void(0)" onclick="loadEditPage(${purchase.id})">Edit</a>
                            <button type="button"
                                id="delete-button"
                                data-action="/purchase/delete/${purchase.id}"
                                onclick="openDeleteModal(this)">
                                Delete
                            </button>
                            <a href="javascript:void(0)" onclick="loadDetailPage(${purchase.id})">View Detail</a>
                            <button id="restock-btn" type="button" onclick="openRestockModal(${purchase.id}, '${purchase.product_name}', '${purchase.purchase_date}')">
                                Restock
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
        html += `<a href="javascript:void(0)" onclick="loadPurchases(1)">« First</a>`;
        html += `<a href="javascript:void(0)" onclick="loadPurchases(${pagination.current_page - 1})">←</a>`;
    }
    
    const start = Math.max(pagination.current_page - 2, 1);
    const end = Math.min(pagination.current_page + 2, pagination.last_page);
    
    if (start > 1) {
        html += '<span class="dots">...</span>';
    }
    
    for (let page = start; page <= end; page++) {
        if (page === pagination.current_page) {
            html += `<span class="active">${page}</span>`;
        } else {
            html += `<a href="javascript:void(0)" onclick="loadPurchases(${page})">${page}</a>`;
        }
    }
    
    if (end < pagination.last_page) {
        html += '<span class="dots">...</span>';
    }
    
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadPurchases(${pagination.current_page + 1})">→</a>`;
        html += `<a href="javascript:void(0)" onclick="loadPurchases(${pagination.last_page})">Last »</a>`;
    } else {
        html += '<span class="disabled">→</span>';
        html += '<span class="disabled">Last »</span>';
    }
    
    container.innerHTML = html;
}

// Function to load edit page via AJAX
function loadEditPage(purchaseId) {
    // Show loading state
    const tbody = document.getElementById('purchaseTableBody');
    tbody.innerHTML = '<tr><td colspan="8"><div class="loading-spinner"><div class="spinner"></div><p>Loading edit form...</p></div></td></tr>';
    
    fetch(`/purchase/${purchaseId}/edit`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to edit page (or you can render inline if you prefer)
            window.location.href = `/purchase/${purchaseId}/edit`;
        } else {
            showPopupMessage('Failed to load edit form', 'error');
            loadPurchases(currentPage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showPopupMessage('An error occurred', 'error');
        loadPurchases(currentPage);
    });
}

// Function to load detail page via AJAX
function loadDetailPage(purchaseId) {
    // Show loading state
    const tbody = document.getElementById('purchaseTableBody');
    tbody.innerHTML = '<tr><td colspan="8"><div class="loading-spinner"><div class="spinner"></div><p>Loading details...</p></div></td></tr>';
    
    fetch(`/purchase/detail/${purchaseId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to detail page (or you can render inline if you prefer)
            window.location.href = `/purchase/detail/${purchaseId}`;
        } else {
            showPopupMessage('Failed to load details', 'error');
            loadPurchases(currentPage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showPopupMessage('An error occurred', 'error');
        loadPurchases(currentPage);
    });
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
    const deleteUrl = form.action;
    
    closeDeleteModal();
    
    fetch(deleteUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showPopupMessage(data.message, 'success');
            loadPurchases(currentPage);
        } else {
            showPopupMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showPopupMessage('An error occurred while deleting', 'error');
    });
}

// Restock modal functions
function openRestockModal(id, name, date) {
    document.getElementById('purchaseId').value = id;
    document.getElementById('restockProductName').innerText = 'Restock ' + name;
    document.getElementById('purchaseDate').value = date.split(' ')[0];
    document.getElementById('restockModal').style.display = 'flex';
}

function closeRestockModal() {
    document.getElementById('restockModal').style.display = 'none';
}

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${date.getDate()}-${months[date.getMonth()]}-${date.getFullYear()}`;
}

function showError(message) {
    const tbody = document.getElementById('purchaseTableBody');
    tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">${message}</td></tr>`;
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

// Attach dropdown listeners
function attachDropdownListeners() {
    const tableRows = document.querySelectorAll("#purchaseTable tbody tr");
    let activeMenu = null;

    function showMenuAtCursor(menu, e) {
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }
        menu.style.display = "block";
        menu.style.position = "fixed";
        menu.style.left = e.clientX + "px";
        menu.style.top = e.clientY + "px";
        menu.style.right = "auto";
        activeMenu = menu;
    }

    function showMenuUnderButton(menu, button) {
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }
        menu.style.display = "block";
        menu.style.position = "absolute";
        menu.style.right = "0";
        menu.style.top = "100%";
        menu.style.left = "auto";
        activeMenu = menu;
    }

    tableRows.forEach(row => {
        row.addEventListener("click", function (e) {
            if (e.target.closest("a") || e.target.closest("button") || e.target.closest("input") || e.target.closest(".dropdown")) return;
            let dropdown = this.querySelector(".dropdown");
            let menu = dropdown ? dropdown.querySelector(".dropdown-menu") : null;
            
            if (menu) {
                e.stopPropagation();
                if (menu.style.display === "block") {
                    menu.style.display = "none";
                    activeMenu = null;
                } else {
                    showMenuAtCursor(menu, e);
                }
            }
        });
    });

    document.querySelectorAll(".dropdown-toggle").forEach(button => {
        button.addEventListener("click", function (e) {
            e.stopPropagation();
            let menu = this.nextElementSibling;
            if (menu.style.display === "block") {
                menu.style.display = "none";
                activeMenu = null;
            } else {
                showMenuUnderButton(menu, this);
            }
        });
    });

    document.addEventListener("click", function () {
        if (activeMenu) {
            activeMenu.style.display = "none";
            activeMenu = null;
        }
    });
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
