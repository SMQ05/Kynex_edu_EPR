@extends('portal.layout')
@section('title', 'Register Your School')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card">
      <h1 class="form-title">Register Your School</h1>
      <p class="form-subtitle">Fill in the details below. We'll send a verification link to your email.</p>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
      @endif

      <form method="POST" action="{{ route('school.register.submit') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="school_name">School Name *</label>
          <input type="text" id="school_name" name="school_name" class="form-input @error('school_name') is-invalid @enderror"
            value="{{ old('school_name') }}" placeholder="e.g. City Grammar School" required autofocus>
          @error('school_name')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="contact_name">Your Full Name *</label>
          <input type="text" id="contact_name" name="contact_name" class="form-input @error('contact_name') is-invalid @enderror"
            value="{{ old('contact_name') }}" placeholder="e.g. Muhammad Ali" required>
          @error('contact_name')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address *</label>
          <input type="email" id="email" name="email" class="form-input @error('email') is-invalid @enderror"
            value="{{ old('email') }}" placeholder="admin@yourschool.com" required>
          @error('email')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number *</label>
          <input type="tel" id="phone" name="phone" class="form-input @error('phone') is-invalid @enderror"
            value="{{ old('phone') }}" placeholder="e.g. 0300-1234567" required>
          @error('phone')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="message">Message (optional)</label>
          <textarea id="message" name="message" class="form-input @error('message') is-invalid @enderror"
            rows="3" placeholder="Tell us about your school...">{{ old('message') }}</textarea>
          @error('message')<div class="error-text">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
          Send Verification Email
        </button>
      </form>

      <p style="text-align:center; margin-top:24px; font-size:14px; color:#64748b;">
        Already have an account? <a href="{{ route('school.login') }}">Login here</a>
      </p>
    </div>
  </div>
</div>
@endsection
