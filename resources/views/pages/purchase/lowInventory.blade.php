@extends('layouts.app')

@section('title', 'Low Inventory Products')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<style>
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
        <h1>Low Inventory Products</h1>
    </div>

    <div class="container-child sub-text">
        <p>Low inventory alert: products with 5 or fewer units</p>
    </div>

    <div class="sub-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Remaining</th>
                    <th>Purchase Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="lowInventoryTableBody">
                <tr>
                    <td colspan="7">
                    <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading low inventory products...</p>
            </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="custom-pagination" id="paginationContainer">
            <!-- Pagination links will be dynamically inserted here -->
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

<script>
// Global variables
let currentPage = 1;

// Load low inventory products on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLowInventory(1);
    
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

// Function to load low inventory products via AJAX
function loadLowInventory(page) {
    currentPage = page;
    
    fetch(`{{ route('purchase.lowInventory.data') }}?page=${page}`, {
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
            showError('Failed to load low inventory products');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading products');
    });
}

// Render table rows
function renderTable(purchases, pagination) {
    const tbody = document.getElementById('lowInventoryTableBody');
    
    if (purchases.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No low inventory products found.</td></tr>';
        return;
    }

    // 🔍 DEBUG - Check what data we're getting
    console.log('First purchase object:', purchases[0]);
    console.log('Purchase date field:', purchases[0].purchase_date);
    console.log('Created at field:', purchases[0].created_at);
    
    let html = '';
    purchases.forEach((purchase, index) => {
        const rowNumber = pagination.first_item + index;
        const isDanger = purchase.remaining == 0;
        
        html += `
            <tr class="${isDanger ? 'danger-row' : ''}">
                <td>${rowNumber}</td>
                <td>${purchase.product_name}</td>
                <td>${purchase.category ? purchase.category.name : 'N/A'}</td>
                <td>${purchase.supplier ? purchase.supplier.name : 'N/A'}</td>
                <td style="color: red; font-weight: bold;">${formatNumber(purchase.remaining)}</td>
                <td>${formatDate(purchase.purchase_date)}</td>
                <td>
                    <button class="restock-btn" onclick="openRestockModal(${purchase.id}, '${purchase.product_name}', '${formatDateInput(purchase.purchase_date)}')">
                        Restock
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
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
        html += `<a href="javascript:void(0)" onclick="loadLowInventory(1)">« First</a>`;
        html += `<a href="javascript:void(0)" onclick="loadLowInventory(${pagination.current_page - 1})">←</a>`;
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
            html += `<a href="javascript:void(0)" onclick="loadLowInventory(${page})">${page}</a>`;
        }
    }
    
    if (end < pagination.last_page) {
        html += '<span class="dots">...</span>';
    }
    
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadLowInventory(${pagination.current_page + 1})">→</a>`;
        html += `<a href="javascript:void(0)" onclick="loadLowInventory(${pagination.last_page})">Last »</a>`;
    } else {
        html += '<span class="disabled">→</span>';
        html += '<span class="disabled">Last »</span>';
    }
    
    container.innerHTML = html;
}

// Restock modal functions
function openRestockModal(id, name, date) {
    document.getElementById('purchaseId').value = id;
    document.getElementById('restockProductName').innerText = 'Restock ' + name;
    document.getElementById('purchaseDate').value = date;
    document.getElementById('restockModal').style.display = 'flex';
}

function closeRestockModal() {
    document.getElementById('restockModal').style.display = 'none';
}

// Helper functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    
    // Check if date is valid
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    
    return `${day} ${month} ${year}`;
}

function formatDateInput(dateString) {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatNumber(num) {
    return Math.floor(num);
}

function showError(message) {
    const tbody = document.getElementById('lowInventoryTableBody');
    tbody.innerHTML = `<tr><td colspan="7" style="color: red; text-align: center;">${message}</td></tr>`;
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

// Handle restock form submission to reload data
document.getElementById('restockForm')?.addEventListener('submit', function(e) {
    // Let form submit normally, but reload data after redirect back
    setTimeout(() => {
        loadLowInventory(currentPage);
    }, 100);
});
</script>
@endsection
