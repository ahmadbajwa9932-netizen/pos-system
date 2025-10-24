@extends('layouts.app')

@section('title', 'update')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/category/category_add_form.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
    <h1 >Manage Categories</h1>
</div>
<div class="container-child sub-text">
    <p>Update a category</p>
</div>
<div class="sub-container">
<form action="{{ route('category.update.post', $category->id) }}" method="POST">
    @csrf
    <div class="form-container">
        <div class="first-form">
            <div class="input-group">
                <span class="input-group-text">Category Name</span>
                <input type="text" name="name" value="{{ $category->name }}" required>
            </div>
            <div class="input-group">
                <textarea name="description" rows="8" placeholder="Category Description (Optional)...">{{ $category->description }}</textarea>
            </div>
            <button type="submit" class="boton-elegante">Update</button>
        </div>
    </div>
</form>
</div>
</div>
@endsection
