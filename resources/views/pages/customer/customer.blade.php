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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    <tbody>
    @forelse($customers as $index => $customer)
            <tr>
            <td>{{ $index + 1 }}</td>
                        <td style="text-align:left;">
                            <button class="toggle-details" data-id="{{ $customer->id }}" style="background:none;border:none;cursor:pointer;">
                                &#x25BC;
                            </button>
                            {{ $customer->name ?? 'Walk-in Customer' }}
                        </td>
                        <td>{{ $customer->shop_name ?? 'N/A' }}</td>
                        <td>{{ $customer->contact ?? 'N/A' }}</td>
                        <td>{{ $customer->city ?? 'N/A' }}</td>
                        <td>{{ $customer->sales_count }}</td>
                        <td>
                            @php
                                $hasCreditSales = isset($salesBreakdown[$customer->id]['credit']) && $salesBreakdown[$customer->id]['credit']->count > 0;
                            @endphp
                            {{ $hasCreditSales ? 'Yes' : 'No' }}
                        </td>
                        <td>
                            @if($customer->sales->isNotEmpty())
                                {{ $customer->sales->first()->created_at->timezone('Asia/Karachi')->format('Y-m-d g:i:A') }}
                            @else
                                N/A
                            @endif
                        </td>
                <td><a href="{{ route('customers.purchases', $customer->id) }}"><i style="font-size:18px; margin-left:13px; color:#5c6670" class="fa fa-eye"></i></a></td>
                <td><button type="button"
        id="delete-button"
        class="delete-button"
        data-action="{{ route('customers.destroy', $customer->id) }}"
        onclick="openDeleteModal(this)">
    Delete
</button></td>
            </tr>
            <!-- Expandable details row -->
            <tr class="details-row" id="details-{{ $customer->id }}" style="display:none;background:#f9f9f9;">
                <td colspan="10">
                    <table class="table table-sm" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th>Number of Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $breakdown = $salesBreakdown[$customer->id] ?? [];
                                $types = ['credit', 'cash', 'card'];
                            @endphp
                            @foreach($types as $type)
                                @if(isset($breakdown[$type]))
                                <tr>
                                    <td>{{ ucfirst($type) }}</td>
                                    <td>{{ $breakdown[$type]->count }}</td>
                                </tr>
                                @endif
                            @endforeach
                            @if(count($breakdown) == 0)
                            <tr>
                                <td colspan="2">No sales found.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No Record found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

    <div class="custom-pagination">
        @if ($customers->onFirstPage())
            <span class="disabled">« First</span>
        @else
            <a class="ajax-link" href="{{ $customers->url(1) }}">« First</a>
        @endif

        {{-- Previous Page Link --}}
        @if ($customers->onFirstPage())
            <span class="disabled">←</span>
        @else
            <a href="{{ $customers->previousPageUrl() }}" rel="prev">←</a>
        @endif

        @php
            $start = max($customers->currentPage() - 2, 1);
            $end = min($customers->currentPage() + 2, $customers->lastPage());
        @endphp

        @if ($start > 1)
            <span class="dots">...</span>
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $customers->currentPage())
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $customers->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $customers->lastPage())
            <span class="dots">...</span>
        @endif

        {{-- Next Page Link --}}
        @if ($customers->hasMorePages())
            <a href="{{ $customers->nextPageUrl() }}" rel="next">→</a>
        @else
            <span class="disabled">→</span>
        @endif

        @if ($customers->hasMorePages())
            <a href="{{ $customers->url($customers->lastPage()) }}">Last »</a>
        @else
            <span class="disabled">Last »</span>
        @endif
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
document.addEventListener('DOMContentLoaded', function() {
    // ========== TOGGLE EXPANDABLE ROWS ==========
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const detailsRow = document.getElementById('details-' + id);
            if (!detailsRow) return;

            const isOpen = detailsRow.classList.contains('open-by-toggle');
            const nestedTable = detailsRow.querySelector('table');

            if (isOpen) {
                // close
                detailsRow.classList.remove('open-by-toggle');
                detailsRow.style.display = 'none';
                this.innerHTML = '\u25BC'; // down arrow
            } else {
                // before opening, make sure it wasn't hidden by search
                detailsRow.classList.remove('search-hidden');

                // restore nested table display (tbody/tr) to make sure rows are visible
                if (nestedTable) {
                    nestedTable.style.display = 'table';
                    // tbody
                    nestedTable.querySelectorAll('tbody').forEach(tbody => {
                        tbody.style.display = 'table-row-group';
                        // each row inside details table
                        tbody.querySelectorAll('tr').forEach(tr => {
                            tr.style.display = 'table-row';
                            tr.classList.remove('search-hidden'); // remove if search hid them
                        });
                    });

                    // force reflow/repaint so browser renders tbody rows correctly
                    void nestedTable.offsetHeight;
                }

                // show details row
                detailsRow.style.display = 'table-row';
                detailsRow.classList.add('open-by-toggle');
                this.innerHTML = '\u25B2'; // up arrow
            }
        });
    });

    // ========== FUZZY SEARCH WITH EXPANDABLE ROWS SUPPORT ==========
    const searchInput = document.getElementById("customerSearchInput");
    const searchType = document.getElementById("customerSearchType");
    const customerTypeFilter = document.getElementById("customerTypeFilter");
    
    const mainTable = document.querySelector('.sub-container > table') || document.querySelector('table');
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

    function filterTable() {
        const filter = searchInput.value.toLowerCase().trim();
        const tokens = filter === '' ? [] : filter.split(/\s+/);
        const type = (searchType && searchType.value) ? searchType.value : 'customer';
        const selectedCustomerType = customerTypeFilter.value;

        // select only parent rows (skip details rows)
        const mainRows = tbody.querySelectorAll('tr:not(.details-row)');

        mainRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (!cells || cells.length < 2) {
                row.classList.remove('search-hidden');
                row.style.display = '';
                return;
            }

            const customerName = (cells[1]?.innerText || '').toLowerCase();
            const shopName = (cells[2]?.innerText || '').toLowerCase();
            const contact = (cells[3]?.innerText || '').toLowerCase();
            const nextRow = row.nextElementSibling;

            let textToSearch = '';
            if (type === 'customer') textToSearch = customerName;
            else if (type === 'shop_name') textToSearch = shopName;
            else if (type === 'contact') textToSearch = contact;

            // decide match using substring + fuzzy fallback
            let matchesSearch;
            if (tokens.length === 0) matchesSearch = true;
            else matchesSearch = tokens.every(token => textToSearch.includes(token) || fuzzyMatch(textToSearch, token));

            // ✅ Customer type filter logic (check nested table for payment types)
            let matchesType = true;
            if (selectedCustomerType !== 'all' && nextRow && nextRow.classList.contains('details-row')) {
                const nestedTable = nextRow.querySelector('table');
                if (nestedTable) {
                    const paymentTypes = Array.from(
                        nestedTable.querySelectorAll('tbody tr td:first-child')
                    ).map(td => td.innerText.toLowerCase());
                    
                    matchesType = paymentTypes.includes(selectedCustomerType);
                }
            }

            if (matchesSearch && matchesType) {
                // show parent row
                row.classList.remove('search-hidden');
                row.style.display = '';

                // show detail row but keep it closed unless previously opened by user
                if (nextRow && nextRow.classList.contains('details-row')) {
                    nextRow.classList.remove('search-hidden');
                    // if user had it open before, keep visible; otherwise keep closed (clean view)
                    if (!nextRow.classList.contains('open-by-toggle')) {
                        nextRow.style.display = 'none';
                    } else {
                        // if it was open-by-toggle, ensure nested table rows are visible
                        const nestedTable = nextRow.querySelector('table');
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
                        nextRow.style.display = 'table-row';
                    }
                }
            } else {
                // hide both parent and detail safely using class
                row.classList.add('search-hidden');
                row.style.display = 'none';
                if (nextRow && nextRow.classList.contains('details-row')) {
                    nextRow.classList.add('search-hidden');
                    nextRow.style.display = 'none';
                }
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    customerTypeFilter.addEventListener('change', filterTable);
});
</script>
<script src="{{asset('js/delete_modal.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection