@extends('layouts.app')

@section('title', 'Daily Sale')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components//container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<style>
    #salesSearchType {
    padding: 0px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}
#salesSearchType:focus {
    border-color: #007bff;
}
#searchbar-dropdown{
    display: flex;
    justify-content: center;
    gap:5px;
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
<div class="container">
    <div class="container-child main-text">
    <h1 >Sales Overview</h1>
</div>
<div class="container-child sub-text">
    <p>List of sales made during the day</p>
</div>
<div class="sub-container">
    {{-- <div class="add-button">
        <button>
          <span>Add</span>
        </button></div> --}}
    <div class="report-search-container">
    <div class="report-format">
        <button id="openPrintPopup">Print</button>
        <button id="openPdfPopup">Pdf</button>
        <button>Excel</button>
</div>
<div id="searchbar-dropdown">
<div style=" display:flex;justify-content:center;gap:5px">
    <p style="position:relative;top:9px;">Search by:</p>
    <select id="salesSearchType" class="input" style="width: 140px;">
        <option value="voucher">Voucher No</option>
        <option value="product">Product Name</option>
        <option value="customer">Customer</option>
        <option value="payment">Payment Type</option>
    </select>
    </div>
<div class="search-container">
    <input type="text" id="salesSearchInput" name="text" class="input" placeholder="Search...">
    <span class="icon1"> 
      <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="1" d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M22 22L20 20" stroke="#000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
    </span>
  </div>
</div>
    </div>
    <table>
    <thead>
        <tr>
            <th>#</th>
            <th>Voucher No</th>
            <th>Sale Date</th>
            <th>Products</th>
            <th>Total Items</th>
            <th>Grand Total</th>
            <th>Payment Type</th>
            <th>Seller</th>
            <th>Customer</th>
            <th>See Details</th>
        </tr>
    </thead>
    <tbody>
        @forelse($dailySales as $index => $sale)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <button class="toggle-details" data-id="{{ $sale->sale_id }}" style="background:none;border:none;cursor:pointer;">
                    &#x25BC;
                </button>
                {{ $sale->voucher_no }}
            </td>
            <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d-M-Y h:i A') }}</td>
            <td>
                @php
                    $products = explode(', ', $sale->products);
                    echo $products[0] . (count($products) > 1 ? ' + ' . (count($products) - 1) . ' more' : '');
                @endphp
            </td>
            <td>{{ $sale->total_items }}</td>
            <td>{{ number_format($sale->adjusted_grand_total, 2) }}</td>
            <td>{{ $sale->payment_type }}</td>
            <td>{{Auth::user()->name ?? 'admin' }}</td>
            <td>{{$sale->customer_name ?? 'Walk-in Customer'}}</td>
            <td>
                <a href="{{ route('sales.details', $sale->voucher_no) }}">
                <i class="fa fa-eye" style="font-size:18px; color:#5c6670"></i>
                <!-- NEW: Navigate to General Sales icon -->
    <a style="text-decoration:none;" href="{{ route('sales.general', ['highlight' => $sale->sale_id]) }}" 
       title="View in General Sales">
        <i class="fa fa-external-link" style="font-size:18px; color:#5c6670; margin-left:10px"></i>
    </a>
            </td>
        </tr>
        <!-- Expandable details row -->
        <tr class="details-row" id="details-{{ $sale->sale_id }}" style="display:none;background:#f9f9f9;">
            <td colspan="10">
                <table class="table table-sm" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price (per unit)</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($saleItems[$sale->sale_id] ?? [] as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ $item->remaining_quantity }}
            @if($item->returned_quantity > 0)
                <small style="color: #888;">({{ $item->returned_quantity }} returned)</small>
            @endif</td>
                            <td>{{ number_format($item->total_after_discount, 2) }}</td>
                            <td>
    @if($item->adjusted_discount_amount > 0)
        @if($item->discount_type === 'percentage')
            {{-- ✅ For percentage: show original % but adjusted amount --}}
            {{ number_format($item->discount_value, 0) }}% (Rs {{ number_format($item->adjusted_discount_amount, 2) }})
        @else
            {{-- ✅ For amount: show adjusted amount only --}}
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
</td>                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10">No sales found.</td>
        </tr>
        @endforelse
    </tbody>

    </table>
    <div class="custom-pagination">
            {{-- First & Previous --}}
            @if ($dailySales->onFirstPage())
                <span class="disabled">« First</span>
                <span class="disabled">←</span>
            @else
                <a href="{{ $dailySales->url(1) }}">« First</a>
                <a href="{{ $dailySales->previousPageUrl() }}">←</a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($dailySales->currentPage() - 2, 1);
                $end = min($dailySales->currentPage() + 2, $dailySales->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $dailySales->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $dailySales->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $dailySales->lastPage())
                <span class="dots">...</span>
            @endif

            {{-- Next & Last --}}
            @if ($dailySales->hasMorePages())
                <a href="{{ $dailySales->nextPageUrl() }}">→</a>
                <a href="{{ $dailySales->url($dailySales->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">→</span>
                <span class="disabled">Last »</span>
            @endif
        </div>
</div>
</div>
<!-- PDF Popup Modal -->
<div class="pdfModal" id="pdfPopup">
    <div class="pdf-modal-content" style="width:300px;">
        <h3>Select PDF Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('sales.daily.pdf.current') }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('sales.daily.pdf.all') }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('sales.daily.print.current') }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('sales.daily.print.all') }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle expand/collapse for details (robust: restores nested table rows and forces reflow)
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
                this.innerHTML = '\u25BC'; // down
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
                this.innerHTML = '\u25B2'; // up
            }
        });
    });

    // Search functionality with class-based hiding (search-hidden)
    const searchInput = document.getElementById("salesSearchInput");
    const searchType = document.getElementById("salesSearchType");

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

    searchInput.addEventListener('input', function () {
        const filter = this.value.toLowerCase().trim();
        const tokens = filter === '' ? [] : filter.split(/\s+/);
        const type = (searchType && searchType.value) ? searchType.value : 'voucher';

        // select only parent rows (skip details rows)
        const mainRows = tbody.querySelectorAll('tr:not(.details-row)');

        mainRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (!cells || cells.length < 2) {
                row.classList.remove('search-hidden');
                row.style.display = '';
                return;
            }

            const voucher = (cells[1]?.innerText || '').toLowerCase();
            const productSummary = (cells[3]?.innerText || '').toLowerCase();
            const payment = (cells[6]?.innerText || '').toLowerCase();
            const customer = (cells[8]?.innerText || '').toLowerCase();

            let textToSearch = '';
            const nextRow = row.nextElementSibling;

            if (type === 'voucher') textToSearch = voucher;
            else if (type === 'payment') textToSearch = payment;
            else if (type === 'customer') textToSearch = customer;
            else if (type === 'product') {
                textToSearch = productSummary;
                // include names from nested details table if present
                if (nextRow && nextRow.classList.contains('details-row')) {
                    const nestedTable = nextRow.querySelector('table');
                    if (nestedTable) {
                        // gather product names from first column of nested table rows
                        const detailNames = Array.from(
                            nestedTable.querySelectorAll('tbody tr td:first-child')
                        ).map(td => td.innerText.toLowerCase()).join(' ');
                        if (detailNames) textToSearch = (textToSearch + ' ' + detailNames).trim();
                        else textToSearch = (textToSearch + ' ' + nextRow.innerText.toLowerCase()).trim();
                    } else {
                        textToSearch = (textToSearch + ' ' + nextRow.innerText.toLowerCase()).trim();
                    }
                }
            }

            // decide match using substring + fuzzy fallback
            let match;
            if (tokens.length === 0) match = true;
            else match = tokens.every(token => textToSearch.includes(token) || fuzzyMatch(textToSearch, token));

            if (match) {
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
    });
});
</script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection