<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify Your Email — KynexEdu</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#1d4ed8,#3b82f6); padding:36px 40px; text-align:center; }
  .header img { height:40px; margin-bottom:12px; }
  .header h1 { color:#fff; margin:0; font-size:22px; font-weight:700; letter-spacing:-0.3px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .btn-wrap { text-align:center; margin:32px 0; }
  .btn { display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:15px; font-weight:600; letter-spacing:0.2px; }
  .note { background:#f8fafc; border-left:4px solid #3b82f6; border-radius:4px; padding:14px 18px; margin:24px 0; font-size:13px; color:#64748b; }
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
    <p>Hi <strong>{{ $invitation->contact_name }}</strong>,</p>
    <p>Thank you for registering <strong>{{ $invitation->school_name }}</strong> on KynexEdu. Please verify your email address to continue setting up your school.</p>
    <div class="btn-wrap">
      <a href="{{ $verifyUrl }}" class="btn">Verify Email Address</a>
    </div>
    <div class="note">
      ⏰ This link expires on <strong>{{ $expiresAt }}</strong>.<br>
      After verifying, you will be asked to set your password.
    </div>
    <p>If you did not register on KynexEdu, you can safely ignore this email.</p>
    <p>If the button above doesn't work, copy and paste this link into your browser:</p>
    <p style="word-break:break-all; font-size:13px; color:#3b82f6;">{{ $verifyUrl }}</p>
  </div>
  <div class="footer">
    <p>&copy; {{ date('Y') }} <a href="https://kynexsolutions.com">Kynex Solutions</a> · KynexEdu School ERP<br>
    Questions? Contact us at <a href="mailto:support@kynexsolutions.com">support@kynexsolutions.com</a></p>
  </div>
</div>
</body>
</html>
