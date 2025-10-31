@extends('layouts.app')

@section('title', 'Purchase Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/report_format.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchase/see_detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/back_button.css') }}">
<style>
    .product-revenue-section{
        background-color:rgb(247, 241, 241);
        padding:15px;
        border-radius:10px;
        border-left:5px solid #445622;
    }
    .product-revenue-section>h3{
        color:rgb(119, 76, 76) !important;
    }


</style>
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
        <h1>Products in the store</h1>
    </div>
    <div class="container-child sub-text">
        <p>Detail of: <strong>{{ $purchase->product_name }}</strong></p>
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
            <div class="detail-row"><span class="detail-label">Sold Quantity:</span><span class="detail-value">{{$purchase->sold_quantity }}</span></div>
            <div class="detail-row"><span class="detail-label">Remaining Quantity:</span><span class="detail-value">{{ $purchase->quantity - $purchase->sold_quantity }}</span></div>
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
         <!-- Financial Details -->
         <div class="detail-section product-revenue-section">
            <h3>Product Performance Overview</h3>
            <div class="detail-row"><span class="detail-label">💰 Total Revenue:</span><span class="detail-value">Rs {{ number_format($purchase->total_revenue, 2) }}</span></div>
            <div class="detail-row">
    <span class="detail-label">Sold inventory Cost (Paid):</span>
    <span class="detail-value">Rs {{ number_format($purchase->total_cost ?? 0, 2) }}</span>
</div>            <div class="detail-row"><span class="detail-label">Available inventory Cost:</span><span class="detail-value">Rs {{ number_format(($purchase->quantity - $purchase->sold_quantity)*$purchase->purchased_price,2) }}</span></div>
            @php
    $profitOrLoss = $purchase->total_revenue - $purchase->total_cost;
@endphp
<div class="detail-row">
    <span class="detail-label">{{ $profitOrLoss >= 0 ? '💰 Profit:' : '⚠️ Loss:' }}</span>
    <span class="detail-value" style="color: {{ $profitOrLoss >= 0 ? 'green' : 'red' }}; font-weight: bold;">
        Rs {{ number_format(abs($profitOrLoss), 2) }}
    </span>
</div>   </div>
        </div>
    </div>
    <div class="back-button-container">
    <a href="{{ route('purchase.index') }}" class="boton-elegante">← Back</a>
    </div>
</div>
@endsection
