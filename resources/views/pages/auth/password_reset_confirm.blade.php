@extends('layouts.auth')

@section('title', 'Set New Password - POS System')

@section('content')
<div class="auth-container">
    <div class="auth-card" id="resetConfirmCard">
      <h2>Set New Password</h2>

      @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
      @endif

      <form id="resetConfirmForm" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter new password" required minlength="6">
        <input type="password" name="password_confirmation" placeholder="Confirm new password" required minlength="6">
        <button type="submit">Reset Password</button>

        <p class="switch-link">
          <a href="{{ route('login') }}">Back to Login</a>
        </p>
      </form>
    </div>
  </div>

  <script>
    // Shake animation if error occurs
    @if ($errors->any())
      const card = document.getElementById('resetConfirmCard');
      card.classList.add('shake');
      setTimeout(() => card.classList.remove('shake'), 500);
    @endif
  </script>
@endsection
