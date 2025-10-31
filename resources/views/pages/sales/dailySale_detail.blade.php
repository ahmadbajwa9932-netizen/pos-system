@extends('layouts.app')

@section('title', 'Sale Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/sales/see_detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/back_button.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
        <h1>Sale Details - {{ $sale->voucher_no }}</h1>
        @if($sale->has_returns)
            <small style="color: #e74c3c;">⚠️ This sale has returns - showing adjusted amounts</small>
        @endif
    </div>
    <div class="container-child sub-text">
        <p>Complete summary and customer details</p>
    </div>

    <div class="sub-container">
        <!-- Items Table (if has returns) -->
        @if($sale->has_returns)
        <div class="detail-section" style="margin-bottom:10px">
            <h3 style="margin-bottom:10px">Sale Items <span style="font-size:14px">(After Returns)</span></h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Original Qty</th>
                        <th>Returned Qty</th>
                        <th>Remaining Qty</th>
                        <th>Unit Price</th>
                        <th>Remaining Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->remaining_items as $item)
                    <tr>
                        <td>{{ $item->product_name}}</td>
                        <td>{{ $item->original_quantity }}</td>
                        <td style="color: #e74c3c;">{{ $item->returned_quantity }}</td>
                        <td><strong>{{ $item->remaining_quantity }}</strong></td>
                        <td>Rs {{ number_format($item->price, 2) }}</td>
                        <td>Rs {{ number_format($item->remaining_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="detail-box">
            <!-- Sale Summary -->
            <div class="detail-section">
                <h3>Sale Summary <span style="font-size:14px"> @if($sale->has_returns)(Adjusted After Returns)@endif</span></h3>
                
                @if($sale->has_returns)
                <!-- Show both original and adjusted values -->
                <div class="detail-row">
                    <span class="detail-label">Original Subtotal:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        Rs {{ number_format($sale->subtotal, 2) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Adjusted Subtotal:</span>
                    <span class="detail-value">Rs {{ number_format($sale->adjusted_subtotal, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Original Discount:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        @if($sale->discount_type === 'percentage')
                            {{ number_format($sale->discount,0) }}% (Rs {{ number_format($sale->discount_amount, 2) }})
                        @else
                            Rs {{ number_format($sale->discount_amount, 2) }}
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Adjusted Discount:</span>
                    <span class="detail-value">Rs {{ number_format($sale->adjusted_discount_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Original Tax:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        Rs {{ number_format($sale->tax ?? 0, 2) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Adjusted Tax:</span>
                    <span class="detail-value">Rs {{ number_format($sale->adjusted_tax, 2) }}</span>
                </div>

                <div class="detail-row" @if ($sale->total_returned_amount) style="border-top: 2px solid #e74c3c; padding-top: 10px;" @endif>
                    <span class="detail-label">Original Grand Total:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;" >
                        Rs {{ number_format($sale->grand_total, 2) }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Total Returned Amount:</span>
                    <span class="detail-value" style="color: #e74c3c;">
                        -Rs {{ number_format($sale->total_returned_amount, 2) }}
                    </span>
                </div>


                <div class="detail-row" style="border-top: 1px solid #ccc; padding-top: 10px;">
                    <span class="detail-label"><strong>Current Grand Total:</strong></span>
                    <span class="detail-value"><strong>Rs {{ number_format($sale->adjusted_grand_total, 2) }}</strong></span>
                </div>
                @else
                <!-- No returns - show original values -->
                <div class="detail-row">
                    <span class="detail-label">Subtotal:</span>
                    <span class="detail-value">Rs {{ number_format($sale->subtotal, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Discount:</span>
                    <span class="detail-value">
                        @if($sale->discount_type === 'percentage')
                            {{ number_format($sale->discount,0) }}% (Rs {{ number_format($sale->discount_amount, 2) }})
                        @else
                            Rs {{ number_format($sale->discount_amount, 2) }}
                        @endif
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tax:</span>
                    <span class="detail-value">
                    @if($sale->tax_type === 'percentage')
     {{ number_format($sale->tax,0) }}% (Rs {{ number_format($sale->tax_amount, 2) }})
@else
  Rs {{ number_format($sale->tax_amount, 2) }}
@endif
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Grand Total:</span>
                    <span class="detail-value">Rs {{ number_format($sale->grand_total, 2) }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">Received Amount:</span>
                    <span class="detail-value">Rs {{ number_format($sale->received_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Change:</span>
                    <span class="detail-value">Rs {{ number_format($sale->change_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Payment Type:</span>
                    <span class="detail-value">{{ ucfirst($sale->payment_type) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Sale Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y h:i A') }}</span>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-section">
                <h3>Customer Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $sale->customer->name ?? 'Walk-in Customer' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shop Name:</span>
                    <span class="detail-value">{{ $sale->customer->shop_name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value">{{ $sale->customer->contact ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">City:</span>
                    <span class="detail-value">{{ $sale->customer->city ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer Type:</span>
                    <span class="detail-value">{{ $sale->customer->customer_type ?? 'Walk-in' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="back-button-container">
        <a href="{{url()->previous() }}" class="boton-elegante">← Back</a>
    </div>
</div>
@endsection