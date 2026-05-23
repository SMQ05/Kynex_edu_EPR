<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Role Assigned — {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#0d9488,#14b8a6); padding:36px 40px; text-align:center; }
  .header .icon { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#ccfbf1; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .detail-box { background:#f0fdfa; border:2px solid #5eead4; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .detail-box .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #ccfbf1; font-size:14px; }
  .detail-box .row:last-child { border-bottom:none; }
  .detail-box .lbl { color:#0f766e; font-weight:500; }
  .detail-box .val { color:#134e4a; font-weight:700; text-align:right; }
  .info-box { background:#eff6ff; border:1px solid #93c5fd; border-radius:8px; padding:16px 20px; margin:0 0 20px; font-size:14px; color:#1e40af; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; }
  .footer p { margin:0; font-size:12px; color:#94a3b8; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="icon">{{ $audience === 'new_holder' ? '🎓' : ($audience === 'existing_holder' ? '🔔' : '📢') }}</div>
    <h1>
      @if($audience === 'new_holder')
        You're Now {{ $roleName }}
      @elseif($audience === 'existing_holder')
        New {{ $roleName }} Assigned
      @else
        Role Assignment Notice
      @endif
    </h1>
    <p>{{ $schoolName }}</p>
  </div>
  <div class="body">
    @if($audience === 'new_holder')
      <p>Hello <strong>{{ $newHolderName }}</strong>,</p>
      <p>
        Congratulations! You have been officially assigned the role of
        <strong>{{ $roleName }}</strong> at <strong>{{ $schoolName }}</strong> by the
        KynexEdu platform team. The role is now active on your account.
      </p>
    @elseif($audience === 'existing_holder')
      <p>Hello <strong>{{ $recipientName }}</strong>,</p>
      <p>
        We're letting you know — as a current <strong>{{ $roleName }}</strong> at
        <strong>{{ $schoolName }}</strong> — that the platform team has just assigned
        the same role to <strong>{{ $newHolderName }}</strong>.
      </p>
      <p>
        You and {{ $newHolderName }} now hold the {{ $roleName }} role together. No action is
        required from you. If you believe this assignment is in error, contact the
        KynexEdu platform team immediately.
      </p>
    @else
      <p>Hello,</p>
      <p>
        A high-priority role has just been assigned at <strong>{{ $schoolName }}</strong>:
      </p>
    @endif

    <div class="detail-box">
      <div class="row"><span class="lbl">School</span><span class="val">{{ $schoolName }}</span></div>
      <div class="row"><span class="lbl">Role Assigned</span><span class="val">{{ $roleName }}</span></div>
      <div class="row"><span class="lbl">New Holder</span><span class="val">{{ $newHolderName }}</span></div>
      @if(!empty($newHolderEmail))
      <div class="row"><span class="lbl">Email</span><span class="val">{{ $newHolderEmail }}</span></div>
      @endif
      <div class="row"><span class="lbl">Assigned By</span><span class="val">{{ $assignedByName }}</span></div>
      <div class="row"><span class="lbl">When</span><span class="val">{{ now()->format('d M Y, H:i') }}</span></div>
    </div>

    @if($audience === 'new_holder' && !empty($activationNote))
    <div class="info-box">
      📨 <strong>Activation:</strong> {{ $activationNote }}
    </div>
    @endif
  </div>
  <div class="footer">
    <p>KynexEdu · Secure School Management Platform<br>This is an automated notification — do not reply to this email.</p>
  </div>
</div>
</body>
</html>
