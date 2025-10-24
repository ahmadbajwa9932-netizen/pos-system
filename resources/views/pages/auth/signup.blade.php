@extends('layouts.auth')

@section('title', 'Create Admin')

@section('content')
<div class="auth-card">
    <h2 class="card-title">Create Admin Account</h2>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert info">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('signup.post') }}" class="auth-form">
        @csrf

        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>

        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>

        <label class="form-label">Password</label>
        <div class="password-row">
            <input type="password" name="password" id="signup_password" class="form-input" required>
            <button type="button" class="btn-link toggle-pass" data-target="#signup_password">Show</button>
        </div>

        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-input" required>

        <div class="actions-row">
            <button type="submit" class="btn btn-primary">Create Admin</button>
            <p class="switch-link">Already have an account? <a href="{{ route('login') }}">Login</a></p>
        </div>
    </form>
</div>
@endsection
