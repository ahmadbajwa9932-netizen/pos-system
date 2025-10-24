@extends('layouts.app')

@section('title', 'Purchase Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchase/see_detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/back_button.css') }}">

@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
        <h1>Products in the store</h1>
    </div>
    <div class="container-child sub-text">
        <p>Detail of: <strong>{{ $purchase->product_name }}</strong></p>
    </div>
    
    <div class="report-format" style="position:relative !important; bottom:15px !important;">
                <button>Print</button> 
                <button onclick="exportToPDF()">PDF</button>
                <button onclick="exportToExcel()">Excel</button>
            </div>
    <div class="sub-container">
        <!-- Product Details -->
         <div class="detail-box">
        <div class="detail-section">
            <h3>Product Details</h3>
            <div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value">{{ $purchase->product_name }}</span></div>
            <div class="detail-row"><span class="detail-label">Category:</span><span class="detail-value">{{ $purchase->category->name }}</span></div>
            <div class="detail-row"><span class="detail-label">Purchased Price:</span><span class="detail-value">Rs {{ number_format($purchase->purchased_price, 2) }}</span></div>
            @if($purchase->previous_sold_price)
            <div class="detail-row"><span class="detail-label">Previous Sold Price:</span><span class="detail-value">Rs {{ number_format($purchase->previous_sold_price, 2) ?? 'N/A' }}</span></div>
            @endif
            <div class="detail-row"><span class="detail-label">Sold Price:</span><span class="detail-value">Rs {{ number_format($purchase->sold_price, 2) }}</span></div>
            <div class="detail-row"><span class="detail-label">Total Stock:</span><span class="detail-value">{{ number_format($purchase->quantity,0) }}</span></div>
            <div class="detail-row"><span class="detail-label">Unit:</span><span class="detail-value">{{ $purchase->unit }}</span></div>
            <div class="detail-row"><span class="detail-label">Total Price:</span><span class="detail-value">Rs {{number_format($purchase->purchased_price * $purchase->quantity, 2)}}</span></div>
            <div class="detail-row"><span class="detail-label">Sold Quantity:</span><span class="detail-value">{{$purchase->sold_quantity }} (Rs {{number_format($purchase->sold_quantity*$purchase->purchased_price,2)}})</span></div>
            <div class="detail-row"><span class="detail-label">Remaining Quantity:</span><span class="detail-value">{{ $purchase->quantity - $purchase->sold_quantity }} (Rs {{ number_format(($purchase->quantity - $purchase->sold_quantity)*$purchase->purchased_price,2) }})</span></div>
            <div class="detail-row"><span class="detail-label">Purchase Date:</span><span class="detail-value">{{ $purchase->created_at->format('j-M-Y h:i A') }}</span></div>
        </div>

        <!-- Supplier Details -->
        <div class="detail-section">
            <h3>Supplier Details</h3>
            <div class="detail-row"><span class="detail-label">Supplier Name:</span><span class="detail-value">{{ $purchase->supplier->name ?? "N/A" }}</span></div>
            <div class="detail-row"><span class="detail-label">Company:</span><span class="detail-value">{{ $purchase->supplier->company ?? "N/A" }}</span></div>
            <div class="detail-row"><span class="detail-label">Address:</span><span class="detail-value">{{ $purchase->supplier->address ?? "N/A"}}</span></div>
            <div class="detail-row"><span class="detail-label">Contact Info:</span><span class="detail-value">{{ $purchase->supplier->contact_info ?? "N/A"}}</span></div>
        </div>
        </div>
    </div>
    <div class="back-button-container">
    <a href="{{ route('purchase.index') }}" class="boton-elegante">← Back</a>
    </div>
</div>
@endsection
