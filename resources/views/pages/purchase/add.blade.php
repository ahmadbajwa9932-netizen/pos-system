@extends('layouts.app')

@section('title', 'Add')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchase/purchase_add_form.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
@endpush

@section('content')
@if(session('success') || session('error'))
    <div id="popup-message" class="popup {{ session('success') ? 'success' : 'error' }}">
        {{ session('success') ?? session('error') }}
    </div>
@endif

<div class="container">
    <div class="container-child main-text">
        <h1>Products in the Store</h1>
    </div>
    <div class="container-child sub-text">
        <p>Add Product</p>
    </div>

    <div class="sub-container">
        <div class="optional">
            <span>* Supplier information is optional</span>
        </div>
        <div class="product-form-wrapper">
            <form class="purchase-from" action="{{ route('purchase.store') }}" method="POST">
                @csrf
                <div class="form-container">
                    <!-- Product Fields -->
                    <div class="first-form">
                        <div class="input-group">
                            <span class="input-group-text">Product Name</span>
                            <input type="text" name="product_name" placeholder="Product Name..." required>
                        </div>

                        <div class="price-category-container">
                        <div class="input-group">
                    <span class="input-group-text">P. Price</span>
                    <input type="number" step="0.01" id="purchased_price" name="purchased_price" placeholder="Purchased price..." required>
                </div>
                <div class="input-group">
                    <span class="input-group-text">S. Price</span>
                    <input type="number" step="0.01" id="sold_price" name="sold_price" placeholder="Sold price...">
                </div>
            </div>
            <small id="sold_price_error" class="error-message" style="color:red; display:none; margin-left:40%; margin-bottom:2px">
                Sold price cannot be less than purchased price.
            </small>
                        <div class="input-group-select">
    <span class="input-group-text">Category</span>
    <select name="category_id">
        <option value="">--select--</option>
        @php
    $misc = $activeCategories->where('name', 'Misc')->first();
    $others = $activeCategories->where('name', '!=', 'Misc');
@endphp

@if($misc)
    <option value="{{ $misc->id }}">{{ $misc->name }} (Default)</option>
@endif
@foreach($others as $category)
    <option value="{{ $category->id }}">{{ $category->name }}</option>
@endforeach

    </select>
</div>

                        <div class="price-category-container">
                        <div class="input-group">
                            <span class="input-group-text">Total Stock</span>
                            <input type="number" name="quantity" placeholder="Quantity..." required>
                        </div>
                        <div class="input-group-select">
                        <span class="input-group-text">Units</span>
                        <select name="unit" required>
        <option value="">--select--</option>
    <option value="pcs">Pieces</option>
    <option value="dozen">Dozen</option>
    <option value="meter">Meter</option>
    <option value="feet">Feet</option>
    <option value="liter">Liter</option>
    <option value="ml">Milliliter</option>
    <option value="gallon">Gallon</option>
    <option value="bag">Bag</option>
    <option value="kg">Kilogram</option>
    <option value="ton">Ton</option>
    <option value="roll">Roll</option>
    <option value="sq.ft">Square Feet</option>
    <option value="sq.m">Square Meter</option>
    <option value="tube">Tube</option>
    <option value="set">Set</option>
</select>
                        </div>
</div>

                        <div class="input-group">
                            <span class="input-group-text">Purchase Date</span>
                            <input type="date" name="purchase_date" required>
                        </div>
                    </div>

                    <!-- Supplier Fields -->
                    <div class="second-form">
                        <div class="input-group">
                            <span class="input-group-text">Supplier Name</span>
                            <input type="text" name="supplier_name" placeholder="Supplier Name...">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Company</span>
                            <input type="text" name="company" placeholder="Company Name...">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Address</span>
                            <input type="text" name="address" placeholder="Company Address...">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Contact Info</span>
                            <input type="text" name="contact_info" placeholder="Contact...">
                        </div>
                    </div>
                </div>

                <button type="submit" class="boton-elegante">Add</button>
            </form>
        </div>
    </div>
</div>
<script>
const purchasedInput = document.getElementById('purchased_price');
const soldInput = document.getElementById('sold_price');
const errorMsg = document.getElementById('sold_price_error');
const submitBtn = document.getElementById('submit-btn');

function validatePrices() {
    let purchased = parseFloat(purchasedInput.value);
    let sold = parseFloat(soldInput.value);

    // Only run check if both fields have values
    if (!isNaN(purchased) && !isNaN(sold)) {
        if (sold < purchased) {
            errorMsg.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            errorMsg.style.display = 'none';
            submitBtn.disabled = false;
        }
    } else {
        errorMsg.style.display = 'none';
        submitBtn.disabled = false;
    }
}

// Listen to live changes
purchasedInput.addEventListener('input', validatePrices);
soldInput.addEventListener('input', validatePrices);

// Double-check on submit as safety
document.getElementById('purchase-form').addEventListener('submit', function(e) {
    let purchased = parseFloat(purchasedInput.value);
    let sold = parseFloat(soldInput.value);

    if (sold < purchased) {
        e.preventDefault();
        soldInput.focus();
    }
});
</script>
@endsection
