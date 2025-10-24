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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            <tbody>
            @forelse ($purchases as $index => $purchase)
                <tr>
                    <td>{{ $purchases->firstItem() + $index }}</td>
                    <td>{{ $purchase->product_name }}</td>
                    <td>{{ $purchase->category->name }}</td>
                    <!-- <td>Rs {{ number_format($purchase->purchased_price, 2) }}</td> -->
                    <!-- <td>Rs {{ number_format($purchase->sold_price, 2) }}</td> -->
                    <!-- <td>{{ number_format($purchase->quantity, 0) }}</td> -->
                    <td>{{ $purchase->unit }}</td>
                    <!-- <td>Rs {{ number_format($purchase->purchased_price * $purchase->quantity, 2) }}</td> -->
                    <!-- <td>{{ $purchase->sold_quantity }}</td> -->
                    <td>{{ $purchase->quantity - $purchase->sold_quantity }}</td>
                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d-M-Y') }}</td>
                    <td>
    <div class="dropdown">
        <button class="dropdown-toggle">⋮</button>
        <div class="dropdown-menu">
            <a href="{{ route('purchase.update', $purchase->id) }}">Edit</a>
            <button type="button"
        id="delete-button"
        data-action="{{ route('purchase.delete', $purchase->id) }}"
        onclick="openDeleteModal(this)">
    Delete
</button>
            <a href="{{route('purchase.detail',$purchase->id)}}">View Detail</a>
            <button id="restock-btn" type="button" onclick="openRestockModal({{ $purchase->id }}, '{{ $purchase->product_name }}', '{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}')">
    Restock
</button>
        </div>
    </div>
</td>
                </tr>
            @empty
                <tr><td colspan="13">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="custom-pagination">
            {{-- First & Previous --}}
            @if ($purchases->onFirstPage())
                <span class="disabled">« First</span>
                <span class="disabled">←</span>
            @else
                <a href="{{ $purchases->url(1) }}">« First</a>
                <a href="{{ $purchases->previousPageUrl() }}">←</a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($purchases->currentPage() - 2, 1);
                $end = min($purchases->currentPage() + 2, $purchases->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $purchases->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $purchases->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $purchases->lastPage())
                <span class="dots">...</span>
            @endif

            {{-- Next & Last --}}
            @if ($purchases->hasMorePages())
                <a href="{{ $purchases->nextPageUrl() }}">→</a>
                <a href="{{ $purchases->url($purchases->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">→</span>
                <span class="disabled">Last »</span>
            @endif
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
document.addEventListener("DOMContentLoaded", function () {
    const tableRows = document.querySelectorAll("#purchaseTable tbody tr");
    let activeMenu = null; 

    function showMenuAtCursor(menu, e) {
        // Close other menus
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }

        // Show at cursor position
        menu.style.display = "block";
        menu.style.position = "fixed";
        menu.style.left = e.clientX + "px";
        menu.style.top = e.clientY + "px";
        menu.style.right = "auto";

        activeMenu = menu;
    }

    function showMenuUnderButton(menu, button) {
        // Close other menus
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }

        // Show menu under button
        menu.style.display = "block";
        menu.style.position = "absolute";
        menu.style.right = "0";
        menu.style.top = "100%";
        menu.style.left = "auto";

        activeMenu = menu;
    }

    // Row click - show at cursor
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

    // ⋮ button click - show under icon
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

    // Close on outside click
    document.addEventListener("click", function () {
        if (activeMenu) {
            activeMenu.style.display = "none";
            activeMenu = null;
        }
    });
});

function openRestockModal(id, name,date) {
    document.getElementById('purchaseId').value = id;
    document.getElementById('restockProductName').innerText = 'Restock ' + name;
    document.getElementById('purchaseDate').value = date;   
    document.getElementById('restockModal').style.display = 'flex';
}

function closeRestockModal() {
    document.getElementById('restockModal').style.display = 'none';
}

document.addEventListener("DOMContentLoaded", function () {

    const svgIcon=document.querySelector('.icon1');
    const searchContainer=document.getElementById('search-container');
    const searchInput = document.getElementById("purchaseSearch");
    const searchType = document.getElementById("searchType");
    const quantityFrom = document.getElementById("quantityFrom");
    const quantityTo = document.getElementById("quantityTo");
    const quantityRangeInputs = document.getElementById("quantityRangeInputs");
    const table = document.getElementById("purchaseTable");
    const rows = table.getElementsByTagName("tr");

    // 🔹 Toggle between normal search & quantity range search
    searchType.addEventListener("change", function () {
        if (this.value === "quantity") {
            searchContainer.style.boxShadow="0 0px 0px";
            searchInput.style.display = "none";
            svgIcon.style.display="none";
            quantityRangeInputs.style.display = "flex";
        } else {
            searchContainer.style.boxShadow="0 2px 4px rgba(0, 0, 0, 0.1)";
            svgIcon.style.display="block";
            searchInput.style.display = "block";
            quantityRangeInputs.style.display = "none";
            // Reset filtering when switching types
            quantityFrom.value = "";
            quantityTo.value = "";
            filterTable();
        }
    });

    // 🔹 Helper: fuzzy match
    function fuzzyMatch(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) tIndex++;
        }
        return tIndex === token.length;
    }

    // 🔹 Main filtering function
    function filterTable() {
        const filter = searchInput.value.toLowerCase().trim();
        const type = searchType.value;

        for (let i = 1; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName("td");
            if (!cells.length) continue;

            let productName = cells[1].innerText.toLowerCase();
            let category = cells[2].innerText.toLowerCase();
            let unit = cells[3].innerText.toLowerCase();
            let quantity = parseInt(cells[4].innerText.trim()) || 0;
            let textToSearch = "";

            // 🔸 Normal searches
            if (type === "product") textToSearch = productName;
            else if (type === "category") textToSearch = category;
            else if (type === "unit") textToSearch = unit;
            else if (type === "quantity") {
                const fromVal = parseInt(quantityFrom.value) || 0;
                const toVal = parseInt(quantityTo.value) || Infinity;

                if (quantity >= fromVal && quantity <= toVal) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
                continue;
            }

            // ✅ fuzzy/exact match
            const match = filter === "" || textToSearch.includes(filter) || fuzzyMatch(textToSearch, filter);
            rows[i].style.display = match ? "" : "none";
        }
    }

    // 🔹 Event listeners
    searchInput.addEventListener("keyup", filterTable);
    quantityFrom.addEventListener("input", filterTable);
    quantityTo.addEventListener("input", filterTable);
});
</script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
<script src="{{asset('js/delete_modal.js')}}"></script>
@endsection
