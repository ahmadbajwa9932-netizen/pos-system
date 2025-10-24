@extends('layouts.app')

@section('title', 'add')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/category/category_add_form.css') }}">
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
    <h1 >Manage Categories</h1>
</div>
<div class="container-child sub-text">
    <p>Register a category</p>
</div>
<div class="sub-container">
<form action="{{ route('category.store') }}" method="POST">
    @csrf
    <div class="form-container">
        <div class="first-form">
            <div class="input-group">
                <span class="input-group-text">Category Name</span>
                <input type="text" name="name" placeholder="Category Name..." required>
            </div>
            <div class="input-group">
                <textarea name="description" placeholder="Category Description (Optional)..." rows="8"></textarea>
            </div>

            <button type="submit" class="boton-elegante">Create</button>
        </div>
    </div>
</form>
</div>
</div>
@endsection
