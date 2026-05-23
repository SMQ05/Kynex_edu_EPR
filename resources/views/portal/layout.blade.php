<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<title>@yield('title', 'KynexEdu') — KynexEdu School ERP</title>
<meta name="description" content="KynexEdu — Complete School Management ERP by Kynex Solutions">
@include('public.partials.theme')
<style>
  /* ── Auth-specific (class names consumed by portal child views) ── */
  body { background:var(--bg); display:flex; flex-direction:column; min-height:100dvh; }

  .auth-container {
    flex:1 1 auto; position:relative; isolation:isolate;
    display:flex; align-items:center; justify-content:center;
    padding:40px 16px; overflow:hidden;
  }
  .auth-container::before { content:''; position:absolute; inset:0; z-index:-1;
    background:
      radial-gradient(36rem 36rem at 88% -10%, rgba(37,99,235,.12), transparent 60%),
      radial-gradient(30rem 30rem at 0% 110%, rgba(124,58,237,.10), transparent 55%); }
  .auth-box { width:100%; max-width:460px; }
  .auth-brand { text-align:center; margin-bottom:22px; }
  .auth-brand .brand-logo { margin:0 auto 12px; width:52px; height:52px; font-size:23px; border-radius:15px; }
  .auth-brand h2 { margin:0; font-size:15px; font-weight:800; letter-spacing:-.01em; color:var(--ink); }
  .auth-brand span { font-size:12px; color:var(--muted); }

  .form-card {
    background:#fff; border:1px solid var(--line); border-radius:var(--r-lg);
    padding:32px 26px; box-shadow:var(--sh-lg); width:100%;
  }
  @media(min-width:480px){ .form-card { padding:40px 36px; } }
  .form-title { font-size:24px; font-weight:800; letter-spacing:-.02em; margin:0 0 6px; }
  .form-subtitle { color:var(--muted); font-size:14.5px; margin:0 0 26px; }
  .form-group { margin-bottom:18px; }
  .form-label { display:block; font-size:13px; font-weight:600; color:var(--ink-2); margin-bottom:7px; }
  .form-input {
    width:100%; padding:13px 15px; min-height:48px;
    border:1.5px solid var(--line); border-radius:11px; font-size:15px;
    color:var(--ink); background:#fff; transition:border-color .15s, box-shadow .15s; outline:none;
    font-family:inherit;
  }
  .form-input::placeholder { color:var(--muted-2); }
  .form-input:focus { border-color:var(--brand-600); box-shadow:0 0 0 4px rgba(37,99,235,.12); }
  .form-input.is-invalid { border-color:var(--error); }
  .form-input.is-invalid:focus { box-shadow:0 0 0 4px rgba(220,38,38,.12); }
  .error-text { color:var(--error); font-size:12.5px; margin-top:5px; }

  /* ── Alerts ── */
  .alert { padding:14px 16px; border-radius:12px; margin-bottom:20px; font-size:14px; line-height:1.55; }
  .alert-success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
  .alert-error { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }
  .alert-info { background:#eff6ff; border:1px solid #93c5fd; color:#1e40af; }

  /* ── Footer ── */
  .site-footer {
    background:#fff; border-top:1px solid var(--line);
    padding:20px; text-align:center; font-size:13px; color:var(--muted);
  }
  .site-footer a { color:var(--muted); font-weight:600; }
  .site-footer a:hover { color:var(--brand-600); }
</style>
@stack('styles')
</head>
<body>

<nav class="nav">
  <div class="inner nav-inner">
    <a href="{{ route('school.landing') }}" class="brand">
      <span class="brand-logo">K</span>
      <span>KynexEdu<span class="brand-sub">by Kynex Solutions</span></span>
    </a>
    <div class="navbar-links" style="display:flex;align-items:center;gap:8px">
      <a href="{{ route('school.register') }}" class="btn btn-outline btn-sm">Register School</a>
      <a href="{{ route('school.login') }}" class="btn btn-primary btn-sm">Login</a>
    </div>
  </div>
</nav>

@yield('content')

<footer class="site-footer">
  &copy; {{ date('Y') }} <a href="https://kynexsolutions.com" target="_blank" rel="noopener">Kynex Solutions</a> &middot; KynexEdu School ERP &middot;
  <a href="https://edu.kynexsolutions.com">edu.kynexsolutions.com</a>
</footer>

@stack('scripts')
</body>
</html>
