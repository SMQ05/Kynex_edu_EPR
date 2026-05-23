@extends('portal.layout')
@section('title', 'Signup Pending')

@section('content')
<div class="auth-container">
  <div class="auth-box">
    <div class="form-card" style="text-align:center">
      <div style="width:64px;height:64px;border-radius:18px;background:#fffbeb;border:1px solid #fde68a;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 18px">⏳</div>
      <h1 class="form-title">Awaiting Kynex Solutions approval</h1>
      <p class="form-subtitle" style="margin-bottom:18px">
        Thank you — your school registration has been received and is queued for review.
        Once an account manager approves it and selects a plan (trial or paid), you'll get a
        confirmation email at <strong style="color:var(--ink)">{{ $email }}</strong> with login instructions.
      </p>
      <div class="alert alert-info" style="text-align:left">
        Approvals are typically processed within one business day. For urgent requests, contact
        <a href="mailto:hello@kynexsolutions.com" style="font-weight:600">hello@kynexsolutions.com</a>.
      </div>
      <a href="{{ route('school.landing') }}" class="btn btn-outline btn-block" style="margin-top:6px">← Back to Home</a>
    </div>
  </div>
</div>
@endsection
