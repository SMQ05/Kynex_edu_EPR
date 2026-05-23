<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Request Submitted: {{ $roleName }} Assignment</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#0369a1,#0ea5e9); padding:36px 40px; text-align:center; }
  .header .icon { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#bae6fd; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .detail-box { background:#f0f9ff; border:2px solid #7dd3fc; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .detail-box .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e0f2fe; font-size:14px; }
  .detail-box .row:last-child { border-bottom:none; }
  .detail-box .lbl { color:#0369a1; font-weight:500; }
  .detail-box .val { color:#075985; font-weight:700; text-align:right; }
  .info-box { background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:16px 20px; margin:0 0 20px; font-size:14px; color:#166534; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; }
  .footer p { margin:0; font-size:12px; color:#94a3b8; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="icon">📋</div>
    <h1>Request Submitted</h1>
    <p>Your role assignment request is now pending approval — {{ $schoolName }}</p>
  </div>
  <div class="body">
    <p>Hello <strong>{{ $requesterName }}</strong>,</p>
    <p>
      Your request to assign the <strong>{{ $roleName }}</strong> role to
      <strong>{{ $targetName }}</strong> at <strong>{{ $schoolName }}</strong> has been submitted
      successfully and is now awaiting approval.
    </p>

    <div class="detail-box">
      <div class="row"><span class="lbl">School</span><span class="val">{{ $schoolName }}</span></div>
      <div class="row"><span class="lbl">Role Being Assigned</span><span class="val">{{ $roleName }}</span></div>
      <div class="row"><span class="lbl">Assigning To</span><span class="val">{{ $targetName }}</span></div>
      <div class="row"><span class="lbl">Submitted By</span><span class="val">{{ $requesterName }}</span></div>
      <div class="row"><span class="lbl">Status</span><span class="val">⏳ Pending Approval</span></div>
    </div>

    <div class="info-box">
      ✅ <strong>What happens next?</strong> The relevant approver(s) — either the platform SaaS admin
      or the current {{ $roleName }} holder — will review your request. You will receive another
      email once a decision is made.
    </div>

    <p style="font-size:13px;color:#64748b;">
      This is a high-authority role. Approval may take some time. If you need to follow up,
      contact your platform support team.
    </p>
  </div>
  <div class="footer">
    <p>KynexEdu · Secure School Management Platform<br>This is an automated notification — do not reply to this email.</p>
  </div>
</div>
</body>
</html>
