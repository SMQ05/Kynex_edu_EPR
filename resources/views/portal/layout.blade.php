<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'KynexEdu') — KynexEdu School ERP</title>
<meta name="description" content="KynexEdu — Complete School Management ERP by Kynex Solutions">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  :root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #eff6ff;
    --text: #1e293b;
    --muted: #64748b;
    --border: #e2e8f0;
    --bg: #f8fafc;
    --white: #ffffff;
    --success: #059669;
    --error: #dc2626;
    --warning: #d97706;
    --radius: 10px;
  }
  body {
    margin: 0;
    padding: 0;
    background: var(--bg);
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    color: var(--text);
    min-height: 100vh;
    font-size: 15px;
    line-height: 1.6;
  }
  a { color: var(--primary); text-decoration: none; }
  a:hover { text-decoration: underline; }

  /* ── Navbar ── */
  .navbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 18px;
    color: var(--primary);
    text-decoration: none;
  }
  .navbar-brand .logo-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--primary), #3b82f6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 800;
    font-size: 15px;
  }
  .navbar-sub {
    font-size: 11px;
    font-weight: 400;
    color: var(--muted);
    display: block;
    line-height: 1;
  }
  .navbar-links {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: var(--radius); font-size: 14px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-primary:hover { background: var(--primary-dark); text-decoration: none; }
  .btn-outline { background: transparent; color: var(--primary); border: 1.5px solid var(--primary); }
  .btn-outline:hover { background: var(--primary-light); text-decoration: none; }
  .btn-sm { padding: 7px 14px; font-size: 13px; }

  /* ── Form card ── */
  .form-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 40px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    width: 100%;
  }
  .form-title { font-size: 22px; font-weight: 700; margin: 0 0 6px; }
  .form-subtitle { color: var(--muted); font-size: 14px; margin: 0 0 28px; }
  .form-group { margin-bottom: 18px; }
  .form-label { display: block; font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 6px; }
  .form-input {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    color: var(--text);
    background: var(--white);
    transition: border-color 0.15s;
    outline: none;
  }
  .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
  .form-input.is-invalid { border-color: var(--error); }
  .error-text { color: var(--error); font-size: 12px; margin-top: 4px; }
  .btn-block { width: 100%; justify-content: center; padding: 13px; font-size: 15px; }

  /* ── Alerts ── */
  .alert { padding: 14px 18px; border-radius: var(--radius); margin-bottom: 20px; font-size: 14px; }
  .alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
  .alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #7f1d1d; }
  .alert-info { background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af; }

  /* ── Centered container ── */
  .auth-container {
    min-height: calc(100vh - 61px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
  }
  .auth-box { width: 100%; max-width: 460px; }

  /* ── Footer ── */
  .site-footer {
    background: var(--white);
    border-top: 1px solid var(--border);
    padding: 20px 32px;
    text-align: center;
    font-size: 13px;
    color: var(--muted);
  }
  .site-footer a { color: var(--muted); }
  .site-footer a:hover { color: var(--primary); }

  @media (max-width: 600px) {
    .navbar { padding: 0 16px; }
    .form-card { padding: 28px 20px; }
    .navbar-links .btn-outline { display: none; }
  }
</style>
@stack('styles')
</head>
<body>

<nav class="navbar">
  <a href="{{ route('school.landing') }}" class="navbar-brand">
    <div class="logo-icon">K</div>
    <div>
      KynexEdu
      <span class="navbar-sub">by Kynex Solutions</span>
    </div>
  </a>
  <div class="navbar-links">
    <a href="{{ route('school.register') }}" class="btn btn-outline btn-sm">Register School</a>
    <a href="{{ route('school.login') }}" class="btn btn-primary btn-sm">Login</a>
  </div>
</nav>

@yield('content')

<footer class="site-footer">
  &copy; {{ date('Y') }} <a href="https://kynexsolutions.com" target="_blank">Kynex Solutions</a> &middot; KynexEdu School ERP &middot;
  <a href="https://edu.kynexsolutions.com">edu.kynexsolutions.com</a>
</footer>

@stack('scripts')
</body>
</html>
