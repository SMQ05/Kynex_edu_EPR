@extends('portal.layout')
@section('title', 'Reset Password')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card">
      <h1 class="form-title">Reset Password</h1>
      <p class="form-subtitle">Create a new secure password for <strong>{{ $email }}</strong>.</p>

      @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('school.reset-password.submit', ['token' => $token]) }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="password">New Password *</label>
          <input type="password" id="password" name="password" class="form-input @error('password') is-invalid @enderror"
            placeholder="Minimum 8 characters" required autofocus>
          @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirm New Password *</label>
          <input type="password" id="password_confirmation" name="password_confirmation" class="form-input"
            placeholder="Repeat your new password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Reset Password
        </button>
      </form>

      <p style="text-align:center; margin-top:24px; font-size:14px; color:#64748b;">
        <a href="{{ route('school.login') }}">&larr; Back to login</a>
      </p>
    </div>
  </div>
</div>
@endsection
