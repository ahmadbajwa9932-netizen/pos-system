@extends('layouts.auth')

@section('title', 'Reset Password - POS System')

@section('content')
<div class="auth-card" id="resetCard">
    <h2 class="card-title">Reset Password</h2>

      @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert error">
          {{ $errors->first() }}
        </div>
      @endif

      <form id="resetForm" method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="email" name="email" placeholder="Enter your email address" required>
        <button class="btn-primary" type="submit">Send Reset Link</button>

        <p class="switch-link">Remembered your password? 
          <a href="{{ route('login') }}">Login here</a>
        </p>
      </form>
</div>

<script>
    // Shake animation if error exists
    @if ($errors->any())
      const card = document.getElementById('resetCard');
      card.classList.add('shake');
      setTimeout(() => card.classList.remove('shake'), 500);
    @endif
  </script>
@endsection
