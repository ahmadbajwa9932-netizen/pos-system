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
@endpush

@section('content')
@if(session('success') || session('error'))
    <div id="popup-message" class="popup {{ session('success') ? 'success' : 'error' }}">
        {{ session('success') ?? session('error') }}
    </div>
@endif

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
    <tbody>
        @forelse ($expenses as $index => $expense)
            <tr>
            <td>{{ $expenses->firstItem() + $index }}</td>
                <td>{{ ucfirst($expense->category) }}</td>
                <td>{{ $expense->description ?? "N/A" }}</td>
                <td>Rs {{ number_format($expense->amount, 2) }}</td>
                <td>{{ $expense->date }}</td>
                <td><button type="button"
        id="delete-button"
        class="delete-button"
        data-action="{{ route('expenses.destroy', $expense->id) }}"
        onclick="openDeleteModal(this)">
    Delete
</button></td>
            </tr>
            @empty
            <tr><td colspan="8">No Expenses found</td></tr>
        @endforelse
    </tbody>
</table>

<div class="custom-pagination">
            {{-- First & Previous --}}
            @if ($expenses->onFirstPage())
                <span class="disabled">« First</span>
                <span class="disabled">←</span>
            @else
                <a href="{{ $expenses->url(1) }}">« First</a>
                <a href="{{ $expenses->previousPageUrl() }}">←</a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($expenses->currentPage() - 2, 1);
                $end = min($expenses->currentPage() + 2, $expenses->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $expenses->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $expenses->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $expenses->lastPage())
                <span class="dots">...</span>
            @endif

            {{-- Next & Last --}}
            @if ($expenses->hasMorePages())
                <a href="{{ $expenses->nextPageUrl() }}">→</a>
                <a href="{{ $expenses->url($expenses->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">→</span>
                <span class="disabled">Last »</span>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (single instance reused for all rows) -->
<div id="deleteModal" class="delete-modal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="delete-modal-content" role="document">
    <h3 style="margin-top:0">Delete Expense</h3>
    <p>This action uses <strong>soft delete</strong> and can be restored from Recycle Bin.</p>
    <p>This will remove the selected Expense record.</p>

    <form id="deleteForm" method="GET" action="">
      @csrf
      <input type="hidden" name="delete_option" id="deleteOption" value="">

      <div style="margin-top:12px;">
        <button type="button" class="btn btn-primary" onclick="submitDelete('only')">Delete Expense</button>      </div>
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
<script src="{{asset('js/delete_modal.js')}}"></script>
<script src="{{asset('js/pdf_popup.js')}}"></script>
<script src="{{asset('js/print_popup.js')}}"></script>
@endsection
