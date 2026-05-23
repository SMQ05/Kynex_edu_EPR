<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Approval Required: {{ $roleName }} Assignment</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#7c3aed,#a855f7); padding:36px 40px; text-align:center; }
  .header .icon { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#e9d5ff; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .detail-box { background:#faf5ff; border:2px solid #d8b4fe; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .detail-box .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #ede9fe; font-size:14px; }
  .detail-box .row:last-child { border-bottom:none; }
  .detail-box .lbl { color:#6d28d9; font-weight:500; }
  .detail-box .val { color:#4c1d95; font-weight:700; text-align:right; }
  .alert-box { background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:16px 20px; margin:0 0 20px; font-size:14px; color:#92400e; }
  .btn-wrap { text-align:center; margin:28px 0; }
  .btn { display:inline-block; background:#7c3aed; color:#fff; text-decoration:none; padding:14px 40px; border-radius:8px; font-size:16px; font-weight:700; letter-spacing:0.02em; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; }
  .footer p { margin:0; font-size:12px; color:#94a3b8; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="icon">🔐</div>
    <h1>Approval Required</h1>
    <p>High-priority role assignment pending your review — {{ $schoolName }}</p>
  </div>
  <div class="body">
    <p>Hello,</p>
    <p>
      A new role assignment request has been submitted at <strong>{{ $schoolName }}</strong> and
      requires your approval before it can take effect.
    </p>

    <div class="detail-box">
      <div class="row"><span class="lbl">School</span><span class="val">{{ $schoolName }}</span></div>
      <div class="row"><span class="lbl">Role Being Assigned</span><span class="val">{{ $roleName }}</span></div>
      <div class="row"><span class="lbl">Assigning To</span><span class="val">{{ $targetName }}</span></div>
      <div class="row"><span class="lbl">Requested By</span><span class="val">{{ $requesterName }}</span></div>
      <div class="row"><span class="lbl">Submitted</span><span class="val">{{ now()->format('d M Y, H:i') }}</span></div>
    </div>

    <div class="alert-box">
      ⚠️ <strong>Action required.</strong> This is a high-authority role. The assignment will only take
      effect after at least one authorised approver reviews and approves this request.
    </div>

    @if(!empty($approvalQueueUrl))
    <p>
      Log in to the admin panel to review the request and either approve or reject it with a note.
    </p>
    <div class="btn-wrap">
      <a href="{{ $approvalQueueUrl }}" class="btn">Review Request</a>
    </div>
    @endif

    <p style="font-size:13px;color:#64748b;">
      If you did not expect this request or believe it is fraudulent, please contact the KynexEdu
      platform team immediately.
    </p>
  </div>
  <div class="footer">
    <p>KynexEdu · Secure School Management Platform<br>This is an automated notification — do not reply to this email.</p>
  </div>
</div>
</body>
</html>
