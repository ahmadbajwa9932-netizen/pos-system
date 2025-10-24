@extends('layouts.app')

@section('title', 'Credit Sales')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<style>
    .credit-dropdown {
    position: relative;
}

.credit-dropdown-menu {
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

/* --- Credit Sales Search Bar --- */
#creditSearchType {
    padding: 0px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}
#creditSearchType:focus {
    border-color: #007bff;
}
#creditSearchbar-dropdown {
    display: flex;
    justify-content: center;
    gap: 5px;
}

/* used to hide rows during search */
.search-hidden { display: none !important; }

/* marker for rows explicitly opened by toggle */
.open-by-toggle {}

/* ensure nested table keeps structure */
.sub-container table { width: 100%; }


/* button styling inside dropdown */
.credit-action-btn {
    display: block;
    text-align: center;
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    color: #000;
    background: transparent;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.credit-action-btn:hover {
    background: #f0f0f0;
    color: #000;
}

/* optional: styling for ⋮ toggle */
.credit-dropdown-toggle {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 20px;
    padding: 4px 8px;
}
.btn-dark {
    background-color:#929299;
    color: #fff;
    border-radius:3px;
    width:90px;
}
.btn-warning, .btn-danger{
    border-radius: 3px;
    /* width: 90px; */
    margin-left: 5px;
    border:1px;
    padding:7px 10px;
    cursor: pointer;
}
.btn-danger {
    background-color: #dc3545;
    color: #fff;
}
.btn-danger:hover{
    background-color:rgb(163, 47, 59);
}

.btn-warning {
    background-color: #ffc107;
    color: #000;
}
.btn-warning:hover{
    background-color:rgb(216, 164, 6);
}

#paymentModal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    z-index: 1001;
    width: 320px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

#modalBackdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(3px);
    z-index: 1000;
}

#paymentModal form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

#paymentModal input, #paymentModal select, #paymentModal button {
    padding: 10px;
    font-size: 16px;
}

#paymentModal button {
    background-color: #000;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

.close-btn {
    background: #d33;
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
        <h1>Credit Sales</h1>
    </div>
    <div class="container-child sub-text">
        <p>List of unpaid credit sales</p>
    </div>
    <div class="sub-container">
        <div class="report-search-container">
            <div class="report-format">
                <button id="openPrintPopup">Print</button>
                <button id="openPdfPopup">PDF</button>
                <button>Excel</button>
            </div>
            <div id="creditSearchbar-dropdown">
  <div style="display:flex;justify-content:center;gap:5px">
      <p style="position:relative;top:9px;">Search by:</p>
      <select id="creditSearchType" class="input" style="width: 140px;">
          <option value="voucher">Voucher No</option>
          <option value="customer">Customer</option>
          <option value="contact">Contact</option>
      </select>
  </div>
            <div class="search-container">
                <input type="text" class="input" id="creditSearchInput" placeholder="Search by customer or voucher...">
                <span class="icon1">
                    <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="1" d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round"></path>
                        <path opacity="1" d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round"></path>
                        <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5"></path>
                        <path opacity="1" d="M22 22L20 20" stroke="#000" stroke-width="3.5"></path>
                    </svg>
                </span>
            </div>
</div>
        </div>

        <table id="creditSalesTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Voucher No</th>
                    <th>Customer</th>
                    <th>Shop Name</th>
                    <th>Contact</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Paid Amount</th>
                    <th>Remaining Balance</th>
                    <th>Due Date</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($creditSales as $index => $sale)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <button class="toggle-details" data-id="{{ $sale->id }}" style="background:none;border:none;cursor:pointer;">
                                &#x25BC;
                            </button>
                            {{ $sale->voucher_no }}
                        </td>
                        <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                        <td>{{ $sale->customer->shop_name ?? 'N/A' }}</td>
                        <td>{{ $sale->customer->contact ?? 'N/A' }}</td>
                        <td>Rs {{ number_format($sale->grand_total, 2) }}</td>
                        <td>
                            <span class="badge badge-warning">{{ ucfirst($sale->status) }}</span>
                        </td>
                        <td>Rs {{ number_format($sale->grand_total-$sale->remaining_balance, 2) }}</td>
                        <td>Rs {{ number_format($sale->remaining_balance, 2) }}</td>
                        <td>{{$sale->due_date ?? '-'}}</td>
                        <td>
                <a href="{{route('credit-sales.details',$sale->voucher_no)}}">
                <i class="fa fa-eye" style="font-size:18px; color:#5c6670"></i>
                </a>
            </td>
            <td>
    <div class="credit-dropdown">
        <button class="credit-dropdown-toggle">⋮</button>
        <div class="credit-dropdown-menu">
            <form action="{{ route('sales.markPaid', $sale->id) }}" method="POST" class="mark-paid-form">
                @csrf
                @method('PUT')
                <button type="submit" class="credit-action-btn">Mark as Paid</button>
            </form>
            <button type="button" class="credit-action-btn" 
                data-id="{{ $sale->id }}" 
                data-balance="{{ $sale->remaining_balance }}" 
                onclick="openPaymentModal(this)">
                Add Payment
            </button>
            <button type="button" class="credit-action-btn" 
                onclick="returnCreditSale('{{ $sale->id }}', '{{ $sale->voucher_no }}')">
                Return Sale
            </button>
        </div>
    </div>
</td>

                    </tr>
                    <!-- Expandable details row -->
                    <tr class="details-row" id="details-{{ $sale->id }}" style="display:none;background:#f9f9f9;">
                        <td colspan="13">
                            <table class="table table-sm" style="width:100%;border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price (per unit)</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Discount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($saleItems[$sale->id] ?? [] as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ number_format($item->price, 2) }}</td>
                                        <td>
                                            {{ $item->remaining_quantity }}
                                            @if($item->returned_quantity > 0)
                                                <small style="color: #888;">({{ $item->returned_quantity }} returned)</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->total_after_discount, 2) }}</td>
                                        <td>
                                            @if($item->adjusted_discount_amount > 0)
                                                @if($item->discount_type === 'percentage')
                                                    {{ number_format($item->discount_value, 0) }}% (Rs {{ number_format($item->adjusted_discount_amount, 2) }})
                                                @else
                                                    Rs {{ number_format($item->adjusted_discount_amount, 2) }}
                                                @endif
                                                @if($item->returned_quantity > 0)
                                                    <small style="color: #666; display: block; font-style: italic;">
                                                        (Adjusted for {{ $item->returned_quantity }} returned items)
                                                    </small>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
    <button class="btn btn-warning btn-sm" onclick="reduceQuantity('{{ $sale->id }}', '{{ $item->id }}', '{{ $item->product_name }}', {{ $item->remaining_quantity }})">Reduce Qty</button>
    <button class="btn btn-danger btn-sm" onclick="deleteItem('{{ $sale->id }}', '{{ $item->id }}', '{{ $item->product_name }}')">Delete Item</button>
</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">No credit sales found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="custom-pagination">
            {{-- First & Previous --}}
            @if ($creditSales->onFirstPage())
                <span class="disabled">« First</span>
                <span class="disabled">←</span>
            @else
                <a href="{{ $creditSales->url(1) }}">« First</a>
                <a href="{{ $creditSales->previousPageUrl() }}">←</a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($creditSales->currentPage() - 2, 1);
                $end = min($creditSales->currentPage() + 2, $creditSales->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $creditSales->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $creditSales->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $creditSales->lastPage())
                <span class="dots">...</span>
            @endif

            {{-- Next & Last --}}
            @if ($creditSales->hasMorePages())
                <a href="{{ $creditSales->nextPageUrl() }}">→</a>
                <a href="{{ $creditSales->url($creditSales->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">→</span>
                <span class="disabled">Last »</span>
            @endif
        </div>
    </div>
</div>

<div id="modalBackdrop"></div>
<div id="paymentModal">
    <form id="paymentForm" method="POST" action="{{ route('sales.addPayment') }}">
        @csrf
        <input type="hidden" name="sale_id" id="sale_id">
        <label>Amount</label>
        <input type="number" name="amount" id="amount" required>
        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
        </select>
        <button type="submit">Submit</button>
        <button type="button" class="close-btn" onclick="closePaymentModal()">Close</button>
    </form>
</div>
<!-- PDF Popup Modal -->
<div class="pdfModal" id="pdfPopup">
    <div class="pdf-modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('sales.credit.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('sales.credit.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('sales.credit.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('sales.credit.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle expand/collapse for details
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const detailsRow = document.getElementById('details-' + id);
            if (!detailsRow) return;

            const isOpen = detailsRow.classList.contains('open-by-toggle');
            const nestedTable = detailsRow.querySelector('table');

            if (isOpen) {
                detailsRow.classList.remove('open-by-toggle');
                detailsRow.style.display = 'none';
                this.innerHTML = '\u25BC';
            } else {
                detailsRow.classList.remove('search-hidden');
                if (nestedTable) {
                    nestedTable.style.display = 'table';
                    nestedTable.querySelectorAll('tbody').forEach(tbody => {
                        tbody.style.display = 'table-row-group';
                        tbody.querySelectorAll('tr').forEach(tr => {
                            tr.style.display = 'table-row';
                            tr.classList.remove('search-hidden');
                        });
                    });
                    void nestedTable.offsetHeight;
                }
                detailsRow.style.display = 'table-row';
                detailsRow.classList.add('open-by-toggle');
                this.innerHTML = '\u25B2';
            }
        });
    });

    // Search Logic
    const searchInput = document.getElementById("creditSearchInput");
    const searchType = document.getElementById("creditSearchType");
    const mainTable = document.getElementById("creditSalesTable");
    if (!mainTable) return;
    const tbody = mainTable.querySelector('tbody');

    function fuzzyMatch(text, token) {
        if (!token) return true;
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) tIndex++;
        }
        return tIndex === token.length;
    }

    searchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase().trim();
        const tokens = filter === '' ? [] : filter.split(/\s+/);
        const type = (searchType && searchType.value) ? searchType.value : 'voucher';
        const mainRows = tbody.querySelectorAll('tr:not(.details-row)');

        mainRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (!cells || cells.length < 2) {
                row.classList.remove('search-hidden');
                row.style.display = '';
                return;
            }

            const voucher = (cells[1]?.innerText || '').toLowerCase();
            const customer = (cells[2]?.innerText || '').toLowerCase();
            const contact = (cells[3]?.innerText || '').toLowerCase();

            let textToSearch = '';
            if (type === 'voucher') textToSearch = voucher;
            else if (type === 'customer') textToSearch = customer;
            else if (type === 'contact') textToSearch = contact;

            let match;
            if (tokens.length === 0) match = true;
            else match = tokens.every(token => textToSearch.includes(token) || fuzzyMatch(textToSearch, token));

            const nextRow = row.nextElementSibling;
            if (match) {
                row.classList.remove('search-hidden');
                row.style.display = '';
                if (nextRow && nextRow.classList.contains('details-row')) {
                    nextRow.classList.remove('search-hidden');
                    if (!nextRow.classList.contains('open-by-toggle')) nextRow.style.display = 'none';
                    else nextRow.style.display = 'table-row';
                }
            } else {
                row.classList.add('search-hidden');
                row.style.display = 'none';
                if (nextRow && nextRow.classList.contains('details-row')) {
                    nextRow.classList.add('search-hidden');
                    nextRow.style.display = 'none';
                }
            }
        });
    });
});

// Mark as paid confirmation
document.querySelectorAll('.mark-paid-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "This will mark the sale as paid.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, mark as paid'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        })
    });
});

// Payment modal functions
function openPaymentModal(button) {
    document.getElementById('sale_id').value = button.dataset.id;
    document.getElementById('amount').max = button.dataset.balance;
    document.getElementById('modalBackdrop').style.display = 'block';
    document.getElementById('paymentModal').style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('modalBackdrop').style.display = 'none';
    document.getElementById('paymentModal').style.display = 'none';
}

// Delete entire credit sale
function returnCreditSale(saleId, voucherNo) {
    Swal.fire({
        title: 'Return Credit Sale?',
        html: `
            <p>Are you sure you want to return voucher <strong>${voucherNo}</strong>?</p>
            <p><strong style="color: red;">This will:</strong></p>
            <ul style="text-align: left; color: #666;">
                <li>Delete the entire sale</li>
                <li>Restore inventory quantities</li>
                <li>Remove all payment records</li>
                <li>This action cannot be undone</li>
            </ul>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Return it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/sales/credit/${saleId}/return`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Delete specific item from credit sale
function deleteItem(saleId, saleItemId, productName) {
    Swal.fire({
        title: 'Delete Item?',
        html: `
            <p>Are you sure you want to delete <strong>${productName}</strong> from this sale?</p>
            <p style="color: #666; font-size: 14px;">This will restore the inventory and recalculate the sale totals.</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete item'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/sales/credit/${saleId}/item/${saleItemId}/delete`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Reduce quantity of specific item
function reduceQuantity(saleId, saleItemId, productName, currentQty) {
    Swal.fire({
        title: 'Reduce Quantity',
        html: `
            <p>Current quantity for <strong>${productName}</strong>: ${currentQty}</p>
            <input type="number" id="newQuantity" class="swal2-input" placeholder="Enter new quantity" min="0" max="${currentQty - 1}" value="${Math.max(0, currentQty - 1)}">
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                <strong>Note:</strong> Enter 0 to completely remove this item from the sale.
            </p>
        `,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Update Quantity',
        preConfirm: () => {
            const newQty = document.getElementById('newQuantity').value;
            if (newQty === '' || newQty < 0 || newQty >= currentQty) {
                Swal.showValidationMessage('Please enter a valid quantity less than current quantity (0 to delete item)');
                return false;
            }
            return newQty;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/sales/credit/${saleId}/item/${saleItemId}/reduce`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PATCH';
            
            const quantityField = document.createElement('input');
            quantityField.type = 'hidden';
            quantityField.name = 'new_quantity';
            quantityField.value = result.value;
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            form.appendChild(quantityField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
document.addEventListener("DOMContentLoaded", function () {
    const creditTableRows = document.querySelectorAll("#creditSalesTable tbody tr");
    let activeCreditMenu = null;

    function showCreditMenuAtCursor(menu, e) {
        if (activeCreditMenu && activeCreditMenu !== menu) {
            activeCreditMenu.style.display = "none";
        }
        menu.style.display = "block";
        menu.style.position = "fixed";
        menu.style.left = e.clientX + "px";
        menu.style.top = e.clientY + "px";
        menu.style.right = "auto";
        activeCreditMenu = menu;
    }

    function showCreditMenuUnderButton(menu, button) {
        if (activeCreditMenu && activeCreditMenu !== menu) {
            activeCreditMenu.style.display = "none";
        }
        menu.style.display = "block";
        menu.style.position = "absolute";
        menu.style.right = "0";
        menu.style.top = "100%";
        menu.style.left = "auto";
        activeCreditMenu = menu;
    }

    // Row click opens dropdown at cursor
    creditTableRows.forEach(row => {
        row.addEventListener("click", function (e) {
            if (e.target.closest("a") || e.target.closest("button") || e.target.closest("input") || e.target.closest(".credit-dropdown")) return;

            let dropdown = this.querySelector(".credit-dropdown");
            let menu = dropdown ? dropdown.querySelector(".credit-dropdown-menu") : null;

            if (menu) {
                e.stopPropagation();
                if (menu.style.display === "block") {
                    menu.style.display = "none";
                    activeCreditMenu = null;
                } else {
                    showCreditMenuAtCursor(menu, e);
                }
            }
        });
    });

    // ⋮ icon click opens dropdown under icon
    document.querySelectorAll(".credit-dropdown-toggle").forEach(button => {
        button.addEventListener("click", function (e) {
            e.stopPropagation();
            let menu = this.nextElementSibling;
            if (menu.style.display === "block") {
                menu.style.display = "none";
                activeCreditMenu = null;
            } else {
                showCreditMenuUnderButton(menu, this);
            }
        });
    });

    // Close on outside click
    document.addEventListener("click", function () {
        if (activeCreditMenu) {
            activeCreditMenu.style.display = "none";
            activeCreditMenu = null;
        }
    });
});
</script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection