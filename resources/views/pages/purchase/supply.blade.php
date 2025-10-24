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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    <tbody id="supplierTable">
        @forelse ($suppliers as $index => $supplier)
            <tr>
                <td>{{ $suppliers->firstItem() + $index }}</td>
                <td>{{ $supplier->name ?? "N/A" }}</td>
                <td>{{ $supplier->company ?? "N/A"  }}</td>
                <td>{{$supplier->address ?? "N/A" }}</td>
                <td>{{$supplier->contact_info ?? "N/A"  }}</td>
                <td>{{$supplier->purchases_count}}</td>
                <td>Rs {{ number_format($supplier->total_purchase_amount, 2) }}</td>
                <td><a href="{{route('supplier.show',$supplier->id)}}"><i style="font-size:18px; margin-left:13px; color:#5c6670;" class="fa fa-eye"></i></a></td>
                <td>
    <div class="dropdown">
        <button class="dropdown-toggle">⋮</button>
        <div class="dropdown-menu">
            <a href="{{ route('supplier.edit', $supplier->id) }}">Edit</a>
            <button type="button"
        id="delete-button"
        data-action="{{ route('supplier.delete', $supplier->id) }}"
        onclick="openDeleteModal(this)">
    Delete
</button>
    </div>
</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No Record found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

    <div class="custom-pagination">
        @if ($suppliers->onFirstPage())
            <span class="disabled">« First</span>
        @else
            <a class="ajax-link" href="{{ $suppliers->url(1) }}">« First</a>
        @endif

        {{-- Previous Page Link --}}
        @if ($suppliers->onFirstPage())
            <span class="disabled">←</span>
        @else
            <a href="{{ $suppliers->previousPageUrl() }}" rel="prev">←</a>
        @endif

        @php
            $start = max($suppliers->currentPage() - 2, 1);
            $end = min($suppliers->currentPage() + 2, $suppliers->lastPage());
        @endphp

        @if ($start > 1)
            <span class="dots">...</span>
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $suppliers->currentPage())
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $suppliers->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $suppliers->lastPage())
            <span class="dots">...</span>
        @endif

        {{-- Next Page Link --}}
        @if ($suppliers->hasMorePages())
            <a href="{{ $suppliers->nextPageUrl() }}" rel="next">→</a>
        @else
            <span class="disabled">→</span>
        @endif

        @if ($suppliers->hasMorePages())
            <a href="{{ $suppliers->url($suppliers->lastPage()) }}">Last »</a>
        @else
            <span class="disabled">Last »</span>
        @endif
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
document.addEventListener("DOMContentLoaded", function () {
    let activeMenu = null; // ✅ define it globally within this scope

    // Function to position and show the menu
    function showMenuUnderButton(menu, button) {
        // Hide any previously open menu
        if (activeMenu && activeMenu !== menu) {
            activeMenu.style.display = "none";
        }

        // Toggle display for current one
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

    // Attach toggle logic to all dropdown buttons
    document.querySelectorAll(".dropdown-toggle").forEach(button => {
        button.addEventListener("click", function (e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            showMenuUnderButton(menu, this);
        });
    });

    // Close when clicking outside
    document.addEventListener("click", function () {
        if (activeMenu) {
            activeMenu.style.display = "none";
            activeMenu = null;
        }
    });
});
</script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
<script src="{{asset('js/delete_modal.js')}}"></script>
@endsection
