@extends('portal.layout')
@section('title', 'Set Your Password')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card">
      <h1 class="form-title">Set Your Password</h1>
      <p class="form-subtitle">
        Welcome, <strong>{{ $invitation->contact_name }}</strong>!
        Create a password to activate your account for <strong>{{ $invitation->school_name }}</strong>.
      </p>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('school.set-password.submit', ['token' => $token]) }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="password">New Password *</label>
          <input type="password" id="password" name="password" class="form-input @error('password') is-invalid @enderror"
            placeholder="Minimum 8 characters" required autofocus>
          @error('password')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirm Password *</label>
          <input type="password" id="password_confirmation" name="password_confirmation" class="form-input"
            placeholder="Repeat your password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
          Activate Account &amp; Set Password
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
