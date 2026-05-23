<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Application Received — {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#2563eb,#3b82f6); padding:36px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#bfdbfe; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .ref-box { background:#eff6ff; border:2px solid #93c5fd; border-radius:10px; padding:20px 28px; margin:24px 0; text-align:center; }
  .ref-box .label { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#1d4ed8; margin:0 0 6px; }
  .ref-box .value { font-size:22px; font-weight:700; font-family:monospace; color:#1e40af; margin:0; word-break:break-all; }
  .steps { background:#f8fafc; border-radius:10px; padding:20px 24px; margin:20px 0; }
  .steps h3 { margin:0 0 12px; font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
  .step-row { display:flex; gap:12px; align-items:flex-start; padding:8px 0; border-bottom:1px solid #e2e8f0; }
  .step-row:last-child { border-bottom:none; }
  .step-num { width:24px; height:24px; border-radius:50%; background:#2563eb; color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
  .step-text { font-size:14px; color:#475569; }
  .btn-wrap { text-align:center; margin:28px 0; }
  .btn { display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:13px 36px; border-radius:8px; font-size:15px; font-weight:600; }
  .info-row { display:flex; gap:8px; align-items:flex-start; background:#f8fafc; border-radius:8px; padding:12px 16px; margin:0 0 10px; font-size:13px; color:#475569; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  .footer a { color:#2563eb; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $schoolName }}</h1>
    <p>Admission Application Received</p>
  </div>
  <div class="body">
    <p>Dear <strong>{{ $applicantName }}</strong>,</p>
    <p>Thank you for applying to <strong>{{ $schoolName }}</strong>. We have successfully received your admission application and it is currently under review.</p>

    <div class="ref-box">
      <p class="label">Your Application Reference</p>
      <p class="value">{{ $referenceToken }}</p>
    </div>

    <p>Save your reference number — you can use it to check your application status at any time.</p>

    @if($statusUrl)
    <div class="btn-wrap">
      <a href="{{ $statusUrl }}" class="btn">Track Application Status</a>
    </div>
    @endif

    <div class="steps">
      <h3>What happens next?</h3>
      <div class="step-row">
        <div class="step-num">1</div>
        <div class="step-text">Our admissions team will review your application. This usually takes 2–3 working days.</div>
      </div>
      <div class="step-row">
        <div class="step-num">2</div>
        <div class="step-text">If an entry test is required, you will receive a separate email with your exam login credentials and schedule.</div>
      </div>
      <div class="step-row">
        <div class="step-num">3</div>
        <div class="step-text">Once a decision is made, you will be notified by email. If admitted, you will receive your admission letter with further instructions.</div>
      </div>
    </div>

    <div class="info-row">📋 <span><strong>Applied for:</strong> {{ $className ?? 'Class not specified' }}</span></div>
    @if($campusName)
    <div class="info-row">🏫 <span><strong>Campus:</strong> {{ $campusName }}</span></div>
    @endif
    <div class="info-row">📅 <span><strong>Applied on:</strong> {{ $appliedDate }}</span></div>

    <p style="margin-top:20px;">If you have any questions, please contact the school admissions office directly.</p>
  </div>
  <div class="footer">
    <p>{{ $schoolName }} · Admissions Office</p>
    <p style="margin-top:6px;">This is an automated message — please do not reply to this email.</p>
  </div>
</div>
</body>
</html>
