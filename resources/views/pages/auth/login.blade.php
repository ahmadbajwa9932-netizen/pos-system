@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="auth-card" id="loginCard">
    <h2 class="card-title">Sign in to POS</h2>

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

    <form method="POST" action="{{ route('login.post') }}" class="auth-form">
        @csrf

        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>

        <label class="form-label">Password</label>
        <div class="password-row">
            <input type="password" name="password" id="login_password" class="form-input" required>
            <button type="button" class="btn-link toggle-pass" data-target="#login_password">Show</button>
        </div>
        <div class="remember-section">
          <label><input type="checkbox" name="remember"> Remember Me</label>
          <a href="{{ route('password.request') }}">Forgot Password?</a>
        </div>
        <div class="actions-row">
            <button type="submit" class="btn btn-primary">Login</button>
            <a href="{{ route('signup') }}" class="btn btn-ghost">Create Admin</a>
            <p class="switch-link">Don’t have an account? <a href="{{ route('signup') }}">Sign up</a></p>
        </div>
    </form>
</div>
<script>
    // Shake animation on failed login
    @if(session('error'))
      const card = document.getElementById('loginCard');
      card.classList.add('shake');
      setTimeout(() => card.classList.remove('shake'), 500);
    @endif
</script>
@endsection
