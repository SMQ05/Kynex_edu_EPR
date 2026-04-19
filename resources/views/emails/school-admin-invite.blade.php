<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your KynexEdu School is Ready</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1d4ed8,#3b82f6); padding:36px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:22px; font-weight:700; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .btn-wrap { text-align:center; margin:32px 0; }
  .btn { display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:15px; font-weight:600; }
  .note { background:#fefce8; border-left:4px solid #eab308; border-radius:4px; padding:14px 18px; margin:24px 0; font-size:13px; color:#713f12; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  .footer a { color:#3b82f6; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>KynexEdu</h1>
  </div>
  <div class="body">
    <p>Hi <strong>{{ $tenant->admin_name }}</strong>,</p>
    <p>Your school <strong>{{ $tenant->school_name }}</strong> has been added to KynexEdu. Your admin account has been created and is waiting for you to set your password.</p>
    <div class="btn-wrap">
      <a href="{{ $setPasswordUrl }}" class="btn">Set Your Password</a>
    </div>
    <div class="note">
      ⏰ This link expires on <strong>{{ $expiresAt }}</strong>.<br>
      If the link expires, please contact your platform administrator.
    </div>
    <p>After setting your password you will be redirected to the login page at <a href="{{ route('school.login') }}">edu.kynexsolutions.com/login</a>.</p>
    <p>If the button above doesn't work, copy this link:</p>
    <p style="word-break:break-all; font-size:13px; color:#3b82f6;">{{ $setPasswordUrl }}</p>
  </div>
  <div class="footer">
    <p>&copy; {{ date('Y') }} <a href="https://kynexsolutions.com">Kynex Solutions</a> · KynexEdu School ERP<br>
    Support: <a href="mailto:support@kynexsolutions.com">support@kynexsolutions.com</a></p>
  </div>
</div>
</body>
</html>
