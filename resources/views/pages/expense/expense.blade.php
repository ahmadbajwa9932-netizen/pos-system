@extends('layouts.app')

@section('title', 'Expenses')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/add_button.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/delete_button.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
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
</style>
@endpush

@section('content')
<div id="popup-message" class="popup" style="display: none;"></div>

<div class="container">
    <div class="container-child main-text">
        <h1>Expense Records</h1>
    </div>
    <div class="container-child sub-text">
        <p>Manage All Expenses</p>
    </div>

    <div class="sub-container">
        <div class="add-button">
            <button onclick="window.location.href='{{ route('expenses.create') }}'">
                <span>Add</span>
            </button>
        </div>

        <div class="report-search-container">
            <div class="report-format">
                <button id="openPrintPopup">Print</button> 
                <button id="openPdfPopup">PDF</button>
                <button onclick="exportToExcel()">Excel</button>
            </div>

            <div class="search-container">
                <input type="text" name="text" id="singleInputSearch" class="input" placeholder="search...">
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

        <table id="singleSearchTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount (Rs)</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="expenseTableBody">
                <!-- Loading spinner -->
                <tr>
                    <td colspan="6">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Loading expenses...</p>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="delete-modal-content" role="document">
        <h3 style="margin-top:0">Delete Expense</h3>
        <p>This action uses <strong>soft delete</strong> and can be restored from Recycle Bin.</p>
        <p>This will remove the selected Expense record.</p>

        <form id="deleteForm" method="GET" action="">
            @csrf
            <input type="hidden" name="delete_option" id="deleteOption" value="">

            <div style="margin-top:12px;">
                <button type="button" class="btn btn-primary" onclick="submitDelete('only')">Delete Expense</button>
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
            <a href="{{ route('expenses.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('expenses.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>

<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('expenses.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('expenses.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>

<script src="{{asset('js/search.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>

<script>
// Global variables
let currentPage = 1;
let allExpenses = []; // Store all expenses for search

// Load expenses on page load
document.addEventListener('DOMContentLoaded', function() {
    loadExpenses(1);
    
    // Check for flash messages in URL
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        showPopupMessage(successMsg, 'success');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (errorMsg) {
        showPopupMessage(errorMsg, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Function to load expenses via AJAX
function loadExpenses(page) {
    currentPage = page;
    
    fetch(`{{ route('expenses.data') }}?page=${page}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allExpenses = data.data; // Store for search
            renderTable(data.data, data.pagination);
            renderPagination(data.pagination);
        } else {
            showError('Failed to load expenses');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while loading expenses');
    });
}

// Render table rows
function renderTable(expenses, pagination) {
    const tbody = document.getElementById('expenseTableBody');
    
    if (expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No Expenses found</td></tr>';
        return;
    }
    
    let html = '';
    expenses.forEach((expense, index) => {
        const rowNumber = pagination.first_item + index;
        html += `
            <tr>
                <td>${rowNumber}</td>
                <td>${capitalizeFirst(expense.category)}</td>
                <td>${expense.description || 'N/A'}</td>
                <td>Rs ${formatNumber(expense.amount)}</td>
                <td>${formatDate(expense.date)}</td>
                <td>
                    <button type="button"
                        id="delete-button"
                        class="delete-button"
                        data-action="/expenses/delete/${expense.id}"
                        onclick="openDeleteModal(this)">
                        Delete
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
    
    // First & Previous
    if (pagination.on_first_page) {
        html += '<span class="disabled">« First</span>';
        html += '<span class="disabled">←</span>';
    } else {
        html += `<a href="javascript:void(0)" onclick="loadExpenses(1)">« First</a>`;
        html += `<a href="javascript:void(0)" onclick="loadExpenses(${pagination.current_page - 1})">←</a>`;
    }
    
    // Page numbers
    const start = Math.max(pagination.current_page - 2, 1);
    const end = Math.min(pagination.current_page + 2, pagination.last_page);
    
    if (start > 1) {
        html += '<span class="dots">...</span>';
    }
    
    for (let page = start; page <= end; page++) {
        if (page === pagination.current_page) {
            html += `<span class="active">${page}</span>`;
        } else {
            html += `<a href="javascript:void(0)" onclick="loadExpenses(${page})">${page}</a>`;
        }
    }
    
    if (end < pagination.last_page) {
        html += '<span class="dots">...</span>';
    }
    
    // Next & Last
    if (pagination.has_more_pages) {
        html += `<a href="javascript:void(0)" onclick="loadExpenses(${pagination.current_page + 1})">→</a>`;
        html += `<a href="javascript:void(0)" onclick="loadExpenses(${pagination.last_page})">Last »</a>`;
    } else {
        html += '<span class="disabled">→</span>';
        html += '<span class="disabled">Last »</span>';
    }
    
    container.innerHTML = html;
}
// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${date.getDate()}-${months[date.getMonth()]}-${date.getFullYear()}`;
}
// Delete modal functions (updated for AJAX)
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
    
    // Close modal
    closeDeleteModal();
    
    // Send AJAX delete request
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
            // Reload current page
            loadExpenses(currentPage);
        } else {
            showPopupMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showPopupMessage('An error occurred while deleting', 'error');
    });
}

// Helper functions
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function showError(message) {
    const tbody = document.getElementById('expenseTableBody');
    tbody.innerHTML = `<tr><td colspan="6" style="color: red; text-align: center;">${message}</td></tr>`;
}

function showPopupMessage(message, type) {
    const popup = document.getElementById('popup-message');
    popup.textContent = message;
    popup.className = `popup ${type}`;
    popup.style.display = 'block';
    
    setTimeout(() => {
        popup.style.display = 'none';
    }, 3000);
}

// Export to Excel function (if you have it)
function exportToExcel() {
    // Your existing Excel export logic
    alert('Excel export functionality');
}
</script>

@endsection