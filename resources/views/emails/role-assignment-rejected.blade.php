<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Role Assignment Request Rejected — {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#dc2626,#ef4444); padding:36px 40px; text-align:center; }
  .header .icon { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#fecaca; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .detail-box { background:#fef2f2; border:2px solid #fca5a5; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .detail-box .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #fee2e2; font-size:14px; }
  .detail-box .row:last-child { border-bottom:none; }
  .detail-box .lbl { color:#991b1b; font-weight:500; }
  .detail-box .val { color:#7f1d1d; font-weight:700; text-align:right; }
  .reason-box { background:#fef3c7; border:1px solid #fcd34d; border-radius:8px; padding:16px 20px; margin:0 0 20px; font-size:14px; color:#92400e; }
  .info-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px 20px; margin:0 0 20px; font-size:14px; color:#475569; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; }
  .footer p { margin:0; font-size:12px; color:#94a3b8; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="icon">❌</div>
    <h1>Request Not Approved</h1>
    <p>Role assignment request rejected — {{ $schoolName }}</p>
  </div>
  <div class="body">
    <p>Hello <strong>{{ $requesterName }}</strong>,</p>
    <p>
      Your request to assign the <strong>{{ $roleName }}</strong> role to
      <strong>{{ $targetName }}</strong> at <strong>{{ $schoolName }}</strong>
      has been <strong>rejected</strong> by the approver.
    </p>

    <div class="detail-box">
      <div class="row"><span class="lbl">School</span><span class="val">{{ $schoolName }}</span></div>
      <div class="row"><span class="lbl">Role Requested</span><span class="val">{{ $roleName }}</span></div>
      <div class="row"><span class="lbl">For User</span><span class="val">{{ $targetName }}</span></div>
      <div class="row"><span class="lbl">Status</span><span class="val">❌ Rejected</span></div>
    </div>

    @if(!empty($adminNote))
    <div class="reason-box">
      💬 <strong>Reason given by approver:</strong><br><br>{{ $adminNote }}
    </div>
    @endif

    <div class="info-box">
      ℹ️ If you believe this decision was made in error, or if you have additional information
      to provide, please contact the approver or the KynexEdu platform support team.
      You may submit a new request after addressing the concerns raised.
    </div>
  </div>
  <div class="footer">
    <p>KynexEdu · Secure School Management Platform<br>This is an automated notification — do not reply to this email.</p>
  </div>
</div>
</body>
</html>
