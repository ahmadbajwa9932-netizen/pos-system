@extends('layouts.app')

@section('title', 'Credit Sale Details')

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
        <h1>Credit Sale Details - {{ $creditSale->voucher_no }}</h1>
        <small style="color: #f39c12;">📋 Credit Sale Transaction</small>
    </div>
    <div class="container-child sub-text">
        <p>Complete summary and customer details for credit sale</p>
    </div>

    <div class="sub-container">
        <!-- Sale Items Table -->
        <div class="detail-section" style="margin-bottom:10px">
            <h3 style="margin-bottom:10px">Sale Items</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditSale->saleItems as $item)
                    <tr>
                        <td>{{ $item->purchase->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Rs {{ number_format($item->price, 2) }}</td>
                        <td>Rs {{ number_format($item->total_after_discount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="detail-box">
            <!-- Sale Summary -->
            <div class="detail-section">
                <h3>Sale Summary</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Subtotal:</span>
                    <span class="detail-value">Rs {{ number_format($creditSale->subtotal, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Discount:</span>
                    <span class="detail-value">
                        @if($creditSale->discount_type === 'percentage')
                            {{ number_format($creditSale->discount, 0) }}% (Rs {{ number_format($creditSale->discount_amount, 2) }})
                        @else
                            Rs {{ number_format($creditSale->discount_amount, 2) }}
                        @endif
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tax:</span>
                    <span class="detail-value">
                        @if($creditSale->tax_type === 'percentage')
     {{ number_format($creditSale->tax,0) }}% (Rs {{ number_format($creditSale->tax_amount, 2) }})
@else
  Rs {{ number_format($creditSale->tax_amount, 2) }}
@endif
</span>
                </div>

                <div class="detail-row" style="border-top: 1px solid #ccc; padding-top: 10px;">
                    <span class="detail-label"><strong>Grand Total:</strong></span>
                    <span class="detail-value"><strong>Rs {{ number_format($creditSale->grand_total, 2) }}</strong></span>
                </div>

                <div class="detail-row" style="background-color: #fff3cd; padding: 10px; margin-top: 15px; border-radius: 5px; border: 1px solid #ffeaa7;">
                    <span class="detail-label" style="color: #856404;"><strong>Credit Amount:</strong></span>
                    <span class="detail-value" style="color: #856404;"><strong>Rs {{ number_format($creditSale->grand_total, 2) }}</strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Payment Type:</span>
                    <span class="detail-value" style="color: #f39c12;"><strong>{{ ucfirst($creditSale->payment_type) }}</strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Sale Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($creditSale->created_at)->format('d M Y h:i A') }}</span>
                </div>

                @if($creditSale->notes)
                <div class="detail-row">
                    <span class="detail-label">Notes:</span>
                    <span class="detail-value">{{ $creditSale->notes }}</span>
                </div>
                @endif
            </div>

            <!-- Customer Information -->
            <div class="detail-section">
                <h3>Customer Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value">{{ $creditSale->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shop Name:</span>
                    <span class="detail-value">{{ $creditSale->customer->shop_name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value">{{ $creditSale->customer->contact ?? 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">City:</span>
                    <span class="detail-value">{{ $creditSale->customer->city ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer Type:</span>
                    <span class="detail-value">{{ $creditSale->customer->customer_type ?? 'Regular' }}</span>
                </div>
            </div>

            <!-- Credit Status Section -->
            <div class="detail-section" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #f39c12;">
                <h3 style="color: #f39c12; margin-bottom: 15px;">Credit Status</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Total Credit Amount:</span>
                    <span class="detail-value" style="color: #f39c12; font-weight: bold;">Rs {{ number_format($creditSale->grand_total, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value" style="color: #27ae60; font-weight: bold;">Rs {{ number_format($creditSale->total_paid, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Remaining Balance:</span>
                    <span class="detail-value" style="color: {{ $creditSale->remaining_balance > 0 ? '#e74c3c' : '#27ae60' }}; font-weight: bold;">
                        Rs {{ number_format($creditSale->remaining_balance, 2) }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        @if($creditSale->is_fully_paid)
                            <span style="color: #27ae60; font-weight: bold;">✅ Fully Paid</span>
                        @elseif($creditSale->total_paid > 0)
                            <span style="color: #f39c12; font-weight: bold;">💰 Partially Paid</span>
                        @else
                            <span style="color: #e74c3c; font-weight: bold;">⏳ Pending</span>
                        @endif
                    </span>
                </div>

                <!-- Payment History -->
                @if($creditSale->payments->count() > 0)
                <div style="margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 15px;">
                    <h4 style="color: #6c757d; margin-bottom: 10px; font-size: 14px;">Payment History</h4>
                    @foreach($creditSale->payments as $payment)
                    <div class="detail-row" style="font-size: 13px; padding: 5px 0;">
                        <span class="detail-label">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y h:i A') }}:</span>
                        <span class="detail-value" style="color: #27ae60;">Rs {{ number_format($payment->amount, 2) }} ({{ ucfirst($payment->method) }})</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="back-button-container">
        <a href="{{ url()->previous()}}" class="boton-elegante">← Back</a>
    </div>
</div>
@endsection