@extends('layouts.app')

@section('title', 'Update Product')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchase/purchase_add_form.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
        <h1>Products in the Store</h1>
    </div>

    <div class="container-child sub-text">
        <p>Update Product: <strong>{{ $purchase->product_name }}</strong></p>
    </div>

    <div class="sub-container">
        <div class="product-form-wrapper" style="padding-top: 20px;">
            <form class="purchase-from" action="{{ route('purchase.update.store', $purchase->id) }}" method="POST">
                @csrf
                <div class="form-container">
                    <div class="first-form">
                        <div class="input-group">
                            <span class="input-group-text">Product Name</span>
                            <input type="text" name="product_name" value="{{ $purchase->product_name }}" required>
                        </div>

                        <div class="price-category-container">
                            <div class="input-group">
                                <span class="input-group-text">P. Price</span>
                                <input type="text" name="purchased_price" value="{{ $purchase->purchased_price }}" required>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">S. Price</span>
                                <input type="text" name="sold_price" value="{{ $purchase->sold_price }}" required>
                            </div>
                        </div>
                        <div class="input-group-select">
                            <span class="input-group-text">Category</span>
                            <select name="category_id" required>
                                <option value="">--select--</option>
                                @foreach($activeCategories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ $purchase->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="price-category-container">
                        <div class="input-group">
                            <span class="input-group-text">Total Stock</span>
                            <input type="number" name="quantity" value="{{ number_format($purchase->quantity,0) }}" required>
                        </div>

                        <div class="input-group-select">
    <span class="input-group-text">Units</span>
    <select name="unit" required class="form-control">
        <option value="">--select--</option>
        <option value="pcs" {{ $purchase->unit == 'pcs' ? 'selected' : '' }}>Pieces</option>
        <option value="dozen" {{ $purchase->unit == 'dozen' ? 'selected' : '' }}>Dozen</option>
        <option value="meter" {{ $purchase->unit == 'meter' ? 'selected' : '' }}>Meter</option>
        <option value="feet" {{ $purchase->unit == 'feet' ? 'selected' : '' }}>Feet</option>
        <option value="liter" {{ $purchase->unit == 'liter' ? 'selected' : '' }}>Liter</option>
        <option value="ml" {{ $purchase->unit == 'ml' ? 'selected' : '' }}>Milliliter</option>
        <option value="gallon" {{ $purchase->unit == 'gallon' ? 'selected' : '' }}>Gallon</option>
        <option value="bag" {{ $purchase->unit == 'bag' ? 'selected' : '' }}>Bag</option>
        <option value="kg" {{ $purchase->unit == 'kg' ? 'selected' : '' }}>Kilogram</option>
        <option value="ton" {{ $purchase->unit == 'ton' ? 'selected' : '' }}>Ton</option>
        <option value="roll" {{ $purchase->unit == 'roll' ? 'selected' : '' }}>Roll</option>
        <option value="sq.ft" {{ $purchase->unit == 'sq.ft' ? 'selected' : '' }}>Square Feet</option>
        <option value="sq.m" {{ $purchase->unit == 'sq.m' ? 'selected' : '' }}>Square Meter</option>
        <option value="tube" {{ $purchase->unit == 'tube' ? 'selected' : '' }}>Tube</option>
        <option value="set" {{ $purchase->unit == 'set' ? 'selected' : '' }}>Set</option>
    </select>
</div>
</div>

                        <div class="input-group">
                            <span class="input-group-text">Purchase Date</span>
                            <input type="date" name="purchase_date" value="{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="second-form">
                        <div class="input-group">
                            <span class="input-group-text">Supplier Name</span>
                            <input type="text" name="supplier_name" value="{{ $purchase->supplier->name }}">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Company</span>
                            <input type="text" name="company" value="{{ $purchase->supplier->company }}">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Address</span>
                            <input type="text" name="address" value="{{ $purchase->supplier->address }}">
                        </div>

                        <div class="input-group">
                            <span class="input-group-text">Contact Info</span>
                            <input type="text" name="contact_info" value="{{ $purchase->supplier->contact_info }}">
                        </div>
                    </div>
                </div>
                <button type="submit" class="boton-elegante">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection
