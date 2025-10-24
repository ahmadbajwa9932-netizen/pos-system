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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    <tbody>
    @forelse($customers as $index => $customer)
            <tr>
            <td>{{ $index + 1 }}</td>
                        <td>{{ $customer->name ?? 'Walk-in Customer' }}</td>
                        <td>{{ $customer->shop_name ?? 'N/A' }}</td>
                        <td>{{ $customer->contact ?? 'N/A' }}</td>
                        <td>{{ $customer->city ?? 'N/A' }}</td>
                        <td>{{ $customer->credit_sales_count }}</td>
                        <td>{{ $customer->current_balance }}</td>
                        <td>
    @php
        $latestCreditSale = $customer->sales
            ->where('payment_type', 'credit')
            ->sortByDesc('created_at')
            ->first();
    @endphp

    @if($latestCreditSale)
        {{ $latestCreditSale->created_at->timezone('Asia/Karachi')->format('Y-m-d g:i:A') }}
    @else
        N/A
    @endif
</td>
                <td><a href="{{ route('customers.credit.purchases', $customer->id) }}"><i style="font-size:18px; margin-left:13px; color:#5c6670" class="fa fa-eye"></i></a></td>
                <td><button type="button"
        id="delete-button"
        class="delete-button"
        data-action="{{ route('customers.credit.destroy', $customer->id) }}"
        onclick="openDeleteModal(this)">
    Delete
</button></td>
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
    document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("customerSearchInput");
    const searchType = document.getElementById("customerSearchType");
    const table = document.getElementById("customerSalesTable");
    const rows = table.getElementsByTagName("tr");
    // 🔹 Helper: fuzzy match (characters appear in sequence)
    function fuzzyMatch(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) {
                tIndex++;
            }
        }
        return tIndex === token.length;
    }

    searchInput.addEventListener("keyup", function () {
        const filter = this.value.toLowerCase().trim();
        const tokens = filter.split(/\s+/);
        const type = searchType.value;

        for (let i = 1; i < rows.length; i++) { // skip header row
            let cells = rows[i].getElementsByTagName("td");
            if (!cells.length) continue;

            let customerName = cells[1].innerText.toLowerCase();
            let shopName = cells[2].innerText.toLowerCase();
            let contact = cells[3].innerText.toLowerCase();
            let textToSearch = "";

            // 🔸 Choose column to search
            if (type === "customer") textToSearch = customerName;
            else if (type === "shop_name") textToSearch = shopName;
            else if (type === "contact") textToSearch = contact;

            // ✅ fuzzy or exact match
            const match = tokens.every(token =>
                textToSearch.includes(token) || fuzzyMatch(textToSearch, token)
            );

            rows[i].style.display = match ? "" : "none";
        }
    });
});

</script>
<script src="{{asset('js/delete_modal.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>

@endsection
