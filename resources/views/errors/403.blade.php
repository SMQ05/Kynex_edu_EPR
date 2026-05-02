<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Access Denied | KynexEdu</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { text-align: center; padding: 2rem; max-width: 500px; }
        .error-code { font-size: 8rem; font-weight: 900; line-height: 1; background: linear-gradient(135deg, #ef4444, #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .error-title { font-size: 1.5rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        .error-message { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #10b981, #059669); color: #fff; text-decoration: none; border-radius: 0.5rem; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(16,185,129,0.3); }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Access Denied</h1>
        <p class="error-message">
            You don't have permission to access this page. If you believe this is an error,
            please contact your school administrator.
        </p>
        <a href="{{ url('/') }}" class="btn">← Go Back Home</a>
    </div>
</body>
</html>
