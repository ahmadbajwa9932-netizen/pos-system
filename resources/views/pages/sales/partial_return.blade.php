@extends('layouts.app')

@section('title', 'Partial Return')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/sales/partial_return.css') }}">
@endpush

@section('content')
<div class="partial-return-container">
    <h2>Partial Return</h2>
    <div class="sale-info">
        <p><strong>Voucher No:</strong> {{ $sale->voucher_no }}</p>
        <p><strong>Sale Date:</strong> {{ \Carbon\Carbon::parse($sale->created_at)->format('d-M-Y h:i A') }}</p>
        <p><strong>Customer:</strong> {{ $sale->customer->name ?? 'Walk-in Customer' }}</p>
    </div>

    <form id="partialReturnForm" method="POST" action="{{ route('sales.returns.partial', $sale->id) }}">
        @csrf
        <table class="return-items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Available Qty</th>
                    <th>Unit Price</th>
                    <th>Return Qty</th>
                    <th>Item Subtotal</th>
                    <th>Item Discount</th>
                    <th>After Item Discount</th>
                    <th>Final Refund</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleItems as $item)
                @php
                    $unitPrice = $item->quantity > 0 ? ($item->total_after_discount / $item->quantity) : 0;
                    $unitItemDiscount = $item->quantity > 0 ? ($item->discount_amount / $item->quantity) : 0;
                @endphp
                <tr>
                    <td>
                        {{ $item->purchase->product_name }}
                        @if($item->returned_quantity > 0)
                            <br><small style="color: #888;">({{ $item->returned_quantity }} already returned)</small>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>Rs {{ number_format($item->price, 2) }}</td>
                    <td>
                        <input type="number" 
                               name="items[{{ $item->id }}]" 
                               min="0" 
                               max="{{ $item->quantity }}"
                               value="0" 
                               data-unit-price="{{ $item->price }}"
                               data-unit-after-item-discount="{{ $unitPrice }}"
                               data-unit-item-discount="{{ $unitItemDiscount }}"
                               data-item-id="{{ $item->id }}"
                               data-max-qty="{{ $item->quantity }}"
                               oninput="validateAndCalculate(this)"
                               onchange="validateAndCalculate(this)">
                    </td>
                    <td class="item-subtotal">Rs 0.00</td>
                    <td class="item-discount">Rs 0.00</td>
                    <td class="after-item-discount">Rs 0.00</td>
                    <td class="final-refund">Rs 0.00</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <label for="return-notes">Notes (Optional):</label>
            <textarea id="return-notes" name="notes" rows="3" placeholder="Enter any notes..."></textarea>
        </div>
        
        <div class="refund-summary">
            <p><strong>Items Subtotal:</strong> Rs <span id="items-subtotal">0.00</span></p>
            <p><strong>Items Discount:</strong> Rs <span id="items-discount">0.00</span></p>
            <p><strong>After Items Discount:</strong> Rs <span id="after-items-discount">0.00</span></p>
            <p><strong>Sale-Level Discount:</strong> Rs <span id="sale-discount">0.00</span></p>
            <p><strong>Tax Adjustment:</strong> 
    @if($sale->tax_type === 'percentage')
        {{ number_format(($sale->subtotal > 0 ? ($sale->tax_amount / $sale->subtotal) * 100 : 0), 1) }}%
        (Rs <span id="tax-adjustment">0.00</span>)
    @else
        Rs <span id="tax-adjustment">0.00</span>
    @endif
</p>
            <hr>
            <div class="refund-total">
                <h3>Total Refund Amount: Rs <span id="total-refund">0.00</span></h3>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="{{ route('sales.general') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-success" onclick="return confirm('Are you sure you want to process this return?')">Process Return</button>
        </div>
    </form>
</div>

<script>
// ✅ Enhanced validation function with quantity limits and visual feedback
function validateAndCalculate(input) {
    const maxQty = parseInt(input.dataset.maxQty);
    const currentValue = parseInt(input.value) || 0;
    
    // Reset styles first
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    
    // Remove any existing error message
    const existingError = input.parentNode.querySelector('.qty-error');
    if (existingError) {
        existingError.remove();
    }
    
    if (currentValue > maxQty) {
        // Show error styling
        input.style.borderColor = '#dc3545';
        input.style.backgroundColor = '#ffe6e6';
        
        // Create error message
        const errorMsg = document.createElement('div');
        errorMsg.className = 'qty-error';
        errorMsg.style.color = '#dc3545';
        errorMsg.style.fontSize = '11px';
        errorMsg.style.fontWeight = 'bold';
        errorMsg.style.marginTop = '2px';
        errorMsg.textContent = `Max: ${maxQty}`;
        input.parentNode.appendChild(errorMsg);
        
        // Reset to maximum allowed value
        input.value = maxQty;
        
        // Show alert popup
        alert(`Maximum returnable quantity is ${maxQty}. Value has been adjusted.`);
    } else if (currentValue < 0) {
        // Handle negative values
        input.style.borderColor = '#dc3545';
        input.style.backgroundColor = '#ffe6e6';
        input.value = 0;
        alert('Return quantity cannot be negative.');
    }
    
    // Always recalculate after validation
    calculateRefund();
}

// ✅ Use the same calculation logic as your working SaleReturnController
function calculateRefund() {
    let totalItemsSubtotal = 0;
    let totalItemsDiscount = 0;
    let totalAfterItemsDiscount = 0;
    let totalFinalRefund = 0;

    // Sale totals for proportional calculations
    const saleSubtotal = {{ $sale->subtotal ?? 0 }};
    const saleGrandTotal = {{ $sale->grand_total ?? 0 }};
    const saleTaxType = "{{ $sale->tax_type ?? 'amount' }}";
const saleTax = {{ $sale->tax ?? 0 }};
const saleTaxAmount = {{ $sale->tax_amount ?? 0 }};
    
    // Calculate payment ratio (same as SaleReturnController)
    const actualPaymentRatio = (saleSubtotal > 0 && saleGrandTotal > 0) 
        ? (saleGrandTotal / saleSubtotal) 
        : 1;

    document.querySelectorAll('input[type="number"]').forEach(input => {
        const qty = parseInt(input.value) || 0;
        const unitPrice = parseFloat(input.dataset.unitPrice);
        const unitAfterItemDiscount = parseFloat(input.dataset.unitAfterItemDiscount);
        const unitItemDiscount = parseFloat(input.dataset.unitItemDiscount);

        if (qty > 0) {
            // Step 1: Calculate item-level totals
            const itemSubtotal = qty * unitPrice;
            const itemDiscount = qty * unitItemDiscount;
            const afterItemDiscount = qty * unitAfterItemDiscount;
            
            // Step 2: Apply sale-level discount + tax proportionally (same as SaleReturnController)
            const finalRefundForItem = afterItemDiscount * actualPaymentRatio;

            // Update row displays
            const row = input.closest('tr');
            row.querySelector('.item-subtotal').textContent = `Rs ${itemSubtotal.toFixed(2)}`;
            row.querySelector('.item-discount').textContent = `Rs ${itemDiscount.toFixed(2)}`;
            row.querySelector('.after-item-discount').textContent = `Rs ${afterItemDiscount.toFixed(2)}`;
            row.querySelector('.final-refund').textContent = `Rs ${finalRefundForItem.toFixed(2)}`;

            // Add to totals
            totalItemsSubtotal += itemSubtotal;
            totalItemsDiscount += itemDiscount;
            totalAfterItemsDiscount += afterItemDiscount;
            totalFinalRefund += finalRefundForItem;
        } else {
            // Clear row if no quantity
            const row = input.closest('tr');
            row.querySelector('.item-subtotal').textContent = 'Rs 0.00';
            row.querySelector('.item-discount').textContent = 'Rs 0.00';
            row.querySelector('.after-item-discount').textContent = 'Rs 0.00';
            row.querySelector('.final-refund').textContent = 'Rs 0.00';
        }
    });

    // Calculate sale-level discount and tax adjustments separately
    const saleLevelDiscountAdjustment = totalAfterItemsDiscount * (actualPaymentRatio - 1);
    
    // ✅ FIXED: Calculate tax adjustment separately based on actual tax
    const taxAdjustment = (saleTaxAmount > 0 && saleSubtotal > 0) 
        ? (totalAfterItemsDiscount * (saleTaxAmount / saleSubtotal))
        : 0;

    // Update summary
    document.getElementById('items-subtotal').textContent = totalItemsSubtotal.toFixed(2);
    document.getElementById('items-discount').textContent = totalItemsDiscount.toFixed(2);
    document.getElementById('after-items-discount').textContent = totalAfterItemsDiscount.toFixed(2);
    document.getElementById('sale-discount').textContent = Math.abs(saleLevelDiscountAdjustment).toFixed(2);
    document.getElementById('tax-adjustment').textContent = Math.abs(taxAdjustment).toFixed(2);
    document.getElementById('total-refund').textContent = totalFinalRefund.toFixed(2);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateRefund();
});

// ✅ Add form validation before submission
document.getElementById('partialReturnForm').addEventListener('submit', function(e) {
    let hasValidReturns = false;
    
    document.querySelectorAll('input[type="number"]').forEach(input => {
        const qty = parseInt(input.value) || 0;
        if (qty > 0) {
            hasValidReturns = true;
        }
    });
    
    if (!hasValidReturns) {
        e.preventDefault();
        alert('Please select at least one item to return.');
        return false;
    }
});
</script>
@endsection