@extends('layouts.app')

@section('title', 'edit')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/supplier/edit_form.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
    <h1 >Supplier Directory</h1>
</div>
<div class="container-child sub-text">
    <p>Update supplier {{$supplier->name}}</p>
</div>
<div class="sub-container">
    {{-- <div class="optional">
        <span>
            optional
        </span>
    </div> --}}
    <div class="product-form-wrapper" style="padding-top:20px;">
    <form class="purchase-from" action="{{ route('supplier.update.post', $supplier->id) }}" method="POST">
    @csrf
        <div class="second-form">
            <div class="input-group">
                <span class="input-group-text">Supplier Name</span>
                <input type="text" name="supplier_name" placeholder="Supplier Name..." value="{{ $supplier->name ?? '' }}">
            </div>
            <div class="input-group">
                <span class="input-group-text">Company Name</span>
                <input type="text" name="company" placeholder="Company Name..." value="{{ $supplier->company ?? '' }}">
            </div>
            <div class="input-group">
                <span class="input-group-text">Address</span>
                <input type="text" name="address" placeholder="Company Address..." value="{{ $supplier->address ?? '' }}">
            </div>
            <div class="input-group">
                <span class="input-group-text">Contact Info</span>
                <input type="text" name="contact_info" placeholder="Contact..." value="{{ $supplier->contact_info ?? '' }}">
            </div>
        </div>
    <button type="submit" class="boton-elegante">Update</button>
</form>
    </div>
</div>
</div>
@endsection
