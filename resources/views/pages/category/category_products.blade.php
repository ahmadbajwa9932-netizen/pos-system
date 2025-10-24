@extends('layouts.app')

@section('title', 'Category Purchases')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/update_view_delete.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/eye_icon.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}"> 
<link rel="stylesheet" href="{{ asset('css/components/pdf_popup.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<!-- <link rel="stylesheet" href="{{ asset('css/components/popup.css') }}"> -->
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

@endpush

@section('content')
@if(session('success') || session('error'))
    <div id="popup-message" class="popup {{ session('success') ? 'success' : 'error' }}">
        {{ session('success') ?? session('error') }}
    </div>
@endif
<div class="container">
    <div class="container-child main-text">
    <h1 >Categories</h1>
</div>
<div class="container-child sub-text">
    <p>Products under {{$category->name}}</p>
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
            <th>Product Name</th>
            <th>Unit</th>
            <!-- <th>Category</th> -->
            <th>Purchased Price</th>
            <th>Total Stock</th>
            <th>Total</th>
            <th>Available Stock</th>
            <th>Available Stock (Rs)</th>
            <th>Date</th>
            <!-- <th>See Details</th>
            <th>Actions</th> -->
        </tr>
    </thead>
    <tbody>
    @forelse ($purchases as $index => $purchase)
    <tr>
        <td>{{ $purchases->firstItem() + $index }}</td>
        <td>{{ $purchase->product_name }}</td>
        <td>{{$purchase->unit}}</td>
        <!-- <td>{{ $purchase->category->name }}</td> -->
        <td>Rs {{ number_format($purchase->purchased_price, 2) }}</td>
        <td>{{ number_format($purchase->quantity,0) }}</td>
        <td>Rs {{ number_format($purchase->purchased_price * $purchase->quantity, 2) }}</td>
        <td>{{$purchase->quantity-$purchase->sold_quantity}}</td>
        <td>Rs {{number_format(($purchase->quantity-$purchase->sold_quantity)*$purchase->purchased_price,2)}}</td>
        <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('j-M-Y') }}</td>
    </tr>
        @empty
            <tr>
                <td colspan="8">No Record found.</td>
            </tr>
        @endforelse
    </tbody>
</table>


<div class="custom-pagination">
        @if ($purchases->onFirstPage())
            <span class="disabled">« First</span>
        @else
            <a class="ajax-link" href="{{ $purchases->url(1) }}">« First</a>
        @endif

        {{-- Previous Page Link --}}
        @if ($purchases->onFirstPage())
            <span class="disabled">←</span>
        @else
            <a href="{{ $purchases->previousPageUrl() }}" rel="prev">←</a>
        @endif

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

        {{-- Next Page Link --}}
        @if ($purchases->hasMorePages())
            <a href="{{ $purchases->nextPageUrl() }}" rel="next">→</a>
        @else
            <span class="disabled">→</span>
        @endif

        @if ($purchases->hasMorePages())
            <a href="{{ $purchases->url($purchases->lastPage()) }}">Last »</a>
        @else
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
            <a href="{{ route('category.purchases.pdf.current',$category->id) }}" class="pdf-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('category.purchases.pdf.all',$category->id) }}" class="pdf-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="pdf-close-btn" id="closePdfPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<!-- Print Popup Modal -->
<div class="printModal" id="printPopup">
    <div class="print-modal-content" style="width:300px;">
        <h3>Select Print Option</h3>
        <div style="margin-top:15px; display:flex; flex-direction:center; gap:10px; width:100%; align-items:center;">
            <a href="{{ route('category.purchases.print.current',$category->id) }}" class="print-btn" style="text-decoration:none;width:100%;">Current Page</a>
            <a href="{{ route('category.purchases.print.all',$category->id) }}" class="print-btn" style="text-decoration:none;width:100%;">All Records</a>
        </div>
        <button class="print-close-btn" id="closePrintPopup" style="width:50%;">Cancel</button>
    </div>
</div>
<script src="{{asset('js/search.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection
