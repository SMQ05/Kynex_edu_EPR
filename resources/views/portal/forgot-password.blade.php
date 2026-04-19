@extends('portal.layout')
@section('title', 'Forgot Password')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card">
      <h1 class="form-title">Forgot Password</h1>
      <p class="form-subtitle">Enter your email and we'll send a reset link (valid for 3 hours).</p>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form method="POST" action="{{ route('school.forgot-password.submit') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input @error('email') is-invalid @enderror"
            value="{{ old('email') }}" placeholder="you@school.com" required autofocus>
          @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Send Reset Link
        </button>
      </form>

      <p style="text-align:center; margin-top:24px; font-size:14px; color:#64748b;">
        Remember your password? <a href="{{ route('school.login') }}">Back to login</a>
      </p>
    </div>
  </div>
</div>
@endsection
