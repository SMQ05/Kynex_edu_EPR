@extends('portal.layout')
@section('title', 'Link Invalid or Expired')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card" style="text-align:center;">
      <div style="font-size:56px; margin-bottom:16px;">⏰</div>
      <h1 class="form-title" style="color:#dc2626;">Link Invalid or Expired</h1>
      <p style="color:#64748b; font-size:15px; margin-top:8px;">{{ $reason }}</p>
      <div style="margin-top:28px; display:flex; flex-direction:column; gap:12px;">
        <a href="{{ route('school.register') }}" class="btn btn-primary" style="justify-content:center;">
          Register Again
        </a>
        <a href="{{ route('school.login') }}" class="btn btn-outline" style="justify-content:center;">
          Back to Login
        </a>
      </div>
      <p style="margin-top:24px; font-size:13px; color:#94a3b8;">
        Need help? Contact us at <a href="mailto:support@kynexsolutions.com">support@kynexsolutions.com</a>
      </p>
    </div>
  </div>
</div>
@endsection
