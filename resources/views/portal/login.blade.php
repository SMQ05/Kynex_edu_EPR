@extends('portal.layout')
@section('title', 'Login')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card">
      <h1 class="form-title">School Login</h1>
      <p class="form-subtitle">Log in to your KynexEdu school dashboard.</p>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('school.login.submit') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input @error('email') is-invalid @enderror"
            value="{{ old('email') }}" placeholder="you@school.com" required autofocus>
          @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-input"
            placeholder="Your password" required>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
          <label style="display:flex; align-items:center; gap:8px; font-size:14px; cursor:pointer;">
            <input type="checkbox" name="remember" style="width:16px; height:16px; accent-color:#2563eb;">
            Remember me
          </label>
          <a href="{{ route('school.forgot-password') }}" style="font-size:14px;">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Log In
        </button>
      </form>

      <div style="margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0; text-align:center; font-size:14px; color:#64748b;">
        <p style="margin:0 0 8px;">Don't have an account? <a href="{{ route('school.register') }}">Register your school</a></p>
      </div>
    </div>
  </div>
</div>
@endsection
