<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Welcome to KynexEdu</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#059669,#10b981); padding:36px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:22px; font-weight:700; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .btn-wrap { text-align:center; margin:32px 0; }
  .btn { display:inline-block; background:#059669; color:#fff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:15px; font-weight:600; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
  .footer a { color:#3b82f6; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Welcome to KynexEdu!</h1>
  </div>
  <div class="body">
    <p>Hi <strong>{{ $tenant->admin_name }}</strong>,</p>
    <p>🎉 Your school <strong>{{ $tenant->school_name }}</strong> is now live on KynexEdu. You can log in to your school admin panel to start managing your institution.</p>
    <div class="btn-wrap">
      <a href="{{ $loginUrl }}" class="btn">Log In to School Panel</a>
    </div>
    <p>Need help getting started? Check our <a href="https://kynexsolutions.com/docs">documentation</a> or reach out to our support team.</p>
  </div>
  <div class="footer">
    <p>&copy; {{ date('Y') }} <a href="https://kynexsolutions.com">Kynex Solutions</a> · KynexEdu School ERP<br>
    Support: <a href="mailto:support@kynexsolutions.com">support@kynexsolutions.com</a></p>
  </div>
</div>
</body>
</html>
