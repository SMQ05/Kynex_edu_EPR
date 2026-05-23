<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Admission Test Login — {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#059669,#10b981); padding:36px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#d1fae5; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .creds-box { background:#f0fdf4; border:2px solid #86efac; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .creds-box .label { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#166534; margin:0 0 4px; }
  .creds-box .value { font-size:20px; font-weight:700; font-family:monospace; color:#14532d; margin:0 0 18px; }
  .creds-box .value:last-child { margin-bottom:0; }
  .btn-wrap { text-align:center; margin:32px 0; }
  .btn { display:inline-block; background:#059669; color:#fff; text-decoration:none; padding:14px 40px; border-radius:8px; font-size:15px; font-weight:600; }
  .info-row { display:flex; gap:8px; align-items:flex-start; background:#f8fafc; border-radius:8px; padding:14px 18px; margin:0 0 12px; font-size:13px; color:#475569; }
  .warn { background:#fefce8; border-left:4px solid #eab308; border-radius:4px; padding:14px 18px; margin:24px 0; font-size:13px; color:#713f12; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  .footer a { color:#059669; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $schoolName }}</h1>
    <p>Admission Test Login Credentials</p>
  </div>
  <div class="body">
    <p>Dear <strong>{{ $applicantName }}</strong>,</p>
    <p>Your admission test has been scheduled. Use the credentials below to log in on exam day.</p>

    <div class="creds-box">
      <p class="label">Username</p>
      <p class="value">{{ $username }}</p>
      <p class="label">Password</p>
      <p class="value">{{ $password }}</p>
    </div>

    @if($testName)
      <div class="info-row">📝 <span><strong>Exam:</strong> {{ $testName }}</span></div>
    @endif
    @if($scheduledDate)
      <div class="info-row">📅 <span><strong>Date:</strong> {{ $scheduledDate }}</span></div>
    @endif
    @if($windowOpens)
      <div class="info-row">🕐 <span><strong>Window:</strong> {{ $windowOpens }}@if($windowCloses) – {{ $windowCloses }}@endif</span></div>
    @endif
    @if($durationMinutes)
      <div class="info-row">⏱ <span><strong>Duration:</strong> {{ $durationMinutes }} minutes</span></div>
    @endif

    <div class="btn-wrap">
      <a href="{{ $loginUrl }}" class="btn">Go to Exam Login</a>
    </div>

    <div class="warn">
      ⚠️ <strong>Do not share these credentials.</strong> Keep this email safe — you will need it on exam day.
      The exam is proctored; switching tabs or leaving the window will be recorded.
    </div>

    <p>If the button above does not work, copy this link into your browser:</p>
    <p style="word-break:break-all;font-size:13px;color:#059669;">{{ $loginUrl }}</p>
  </div>
  <div class="footer">
    <p>&copy; {{ date('Y') }} {{ $schoolName }} · Powered by <a href="https://kynexsolutions.com">KynexEdu</a></p>
    <p>If you have any questions, please contact your school directly.</p>
  </div>
</div>
</body>
</html>
