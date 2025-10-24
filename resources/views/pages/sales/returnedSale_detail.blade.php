@extends('layouts.app')

@section('title', 'Return Details')

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
        <h1>Return Details - {{ $sale->voucher_no }}</h1>
        <small style="color: #e74c3c;">⚠️ {{ $sale->total_return_transactions }} return transaction(s) - {{ $sale->total_items_returned }} items returned</small>
    </div>
    <div class="container-child sub-text">
        <p>Complete return history and financial impact</p>
    </div>

    <div class="sub-container">
        <!-- Original Sale Items with Return Status -->
        <div class="detail-section" style="margin-bottom:10px;">
            <h3 style="margin-bottom: 10px;
    color: #2a2a2a;">Original Sale Items (Return Status)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Original Qty</th>
                        <th>Returned Qty</th>
                        <th>Remaining Qty</th>
                        <th>Unit Price</th>
                        <th>Original Total</th>
                        <th>Remaining Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->original_sale_items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->original_quantity }}</td>
                        <td style="color: #e74c3c;">
                            @if($item->returned_quantity > 0)
                                {{ $item->returned_quantity }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <strong style="color: {{ $item->remaining_quantity > 0 ? '#28a745' : '#6c757d' }};">
                                {{ $item->remaining_quantity }}
                            </strong>
                        </td>
                        <td>Rs {{ number_format($item->price, 2) }}</td>
                        <td style="text-decoration: {{ $item->returned_quantity > 0 ? 'line-through' : 'none' }}; color: {{ $item->returned_quantity > 0 ? '#888' : 'inherit' }};">
                            Rs {{ number_format($item->original_total, 2) }}
                        </td>
                        <td>
                            <strong>Rs {{ number_format($item->remaining_total, 2) }}</strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="detail-box">
            <!-- Financial Summary -->
            <div class="detail-section">
                <h3>Financial Summary <span style="font-size:14px">(After Returns)</span> </h3>
                
                <div class="detail-row">
                    <span class="detail-label">Original Subtotal:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        Rs {{ number_format($sale->subtotal, 2) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Current Subtotal:</span>
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
                    <span class="detail-label">Current Discount:</span>
                    <span class="detail-value">Rs {{ number_format($sale->adjusted_discount_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Original Tax:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        Rs {{ number_format($sale->tax ?? 0, 2) }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Current Tax:</span>
                    <span class="detail-value">Rs {{ number_format($sale->adjusted_tax, 2) }}</span>
                </div>

                <div class="detail-row" style="border-top: 2px solid #e74c3c; padding-top: 10px;">
                    <span class="detail-label">Total Refunded Amount:</span>
                    <span class="detail-value" style="color: #e74c3c;">
                        -Rs {{ number_format($sale->total_returned_amount, 2) }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Original Grand Total:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #888;">
                        Rs {{ number_format($sale->grand_total, 2) }}
                    </span>
                </div>
                <div class="detail-row" style="border-top: 1px solid #ccc; padding-top: 10px;">
                    <span class="detail-label"><strong>Current Net Sale:</strong></span>
                    <span class="detail-value"><strong>Rs {{ number_format($sale->adjusted_grand_total, 2) }}</strong></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Received Amount:</span>
                    <span class="detail-value">Rs {{ number_format($sale->received_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Change Given:</span>
                    <span class="detail-value">Rs {{ number_format($sale->change_amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Payment Type:</span>
                    <span class="detail-value">{{ ucfirst($sale->payment_type) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Original Sale Date:</span>
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

            <!-- Return Summary -->
            <div class="detail-section">
                <h3>Return Summary</h3>
                <div class="detail-row">
                    <span class="detail-label">Total Returns:</span>
                    <span class="detail-value">{{ $sale->total_return_transactions }} transaction(s)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Items Returned:</span>
                    <span class="detail-value">{{ $sale->total_items_returned }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Refunded:</span>
                    <span class="detail-value" style="color: #e74c3c;">Rs {{ number_format($sale->total_returned_amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Net Sale Value:</span>
                    <span class="detail-value"><strong>Rs {{ number_format($sale->adjusted_grand_total, 2) }}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Return Rate:</span>
                    <span class="detail-value">{{ number_format(($sale->total_returned_amount / $sale->grand_total) * 100, 1) }}%</span>
                </div>
            </div>
        </div>

        <!-- Return Transaction History -->
        <div class="detail-section" style="margin-top: 30px;">
            <h3 style="margin-bottom: 10px;
            color: #2a2a2a;" >Return Transaction History</h3>
            @foreach($returns as $index => $return)
            <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong>Return #{{ $index + 1 }} - RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</strong>
                    <span style="color: #666;">{{ \Carbon\Carbon::parse($return->created_at)->format('d M Y h:i A') }}</span>
                </div>
                
                <div style="display: flex; gap: 20px; margin-bottom: 10px;">
                    <div>
                        <strong>Status:</strong> {{ ucfirst($return->status) }}
                    </div>
                    <div>
                        <strong>Refund Amount:</strong> <span style="color: #e74c3c;">Rs {{ number_format($return->total_return_amount, 2) }}</span>
                    </div>
                </div>
                
                @if($return->notes)
                <div style="margin-bottom: 10px;">
                    <strong>Notes:</strong> <em>{{ $return->notes }}</em>
                </div>
                @endif
                
                <!-- Items in this return -->
                <div>
                    <strong>Items Returned:</strong>
                    <table style="width: 100%; margin-top: 5px; font-size: 14px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #e9ecef;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Product</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Qty</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Unit Price</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Refund</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->items as $item)
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">{{ $item->purchase->product_name }}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">{{ $item->quantity_returned }}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">Rs {{ number_format($item->unit_net_after_discount, 2) }}</td>
                                <td style="padding: 8px; border: 1px solid #ddd;">Rs {{ number_format($item->amount_refunded, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="back-button-container">
        <a href="{{ route('sales.returns.index') }}" class="boton-elegante">← Back</a>
    </div>
</div>
@endsection