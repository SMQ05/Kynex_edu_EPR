<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KynexEdu — Complete School ERP by Kynex Solutions</title>
<meta name="description" content="KynexEdu is Pakistan's most complete cloud-based School Management ERP. Manage students, fees, attendance, exams, HR, library, transport, hostel, and more.">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet">
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
    --radius: 10px;
  }
  body { margin:0; padding:0; font-family:'Inter','Segoe UI',Arial,sans-serif; color:var(--text); background:var(--white); font-size:15px; line-height:1.6; }
  a { color:var(--primary); text-decoration:none; }
  a:hover { text-decoration:underline; }

  /* ── NAVBAR ── */
  .navbar {
    background:rgba(255,255,255,0.97);
    backdrop-filter:blur(8px);
    border-bottom:1px solid var(--border);
    padding:0 48px;
    height:64px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:100;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
  }
  .navbar-brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:20px; color:var(--primary); text-decoration:none; }
  .logo-icon { width:38px; height:38px; background:linear-gradient(135deg,#2563eb,#3b82f6); border-radius:9px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:16px; flex-shrink:0; }
  .logo-sub { font-size:10px; font-weight:400; color:var(--muted); display:block; line-height:1; }
  .navbar-links { display:flex; align-items:center; gap:10px; }
  .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 22px; border-radius:var(--radius); font-size:14px; font-weight:500; cursor:pointer; border:none; transition:all 0.15s; text-decoration:none; line-height:1; }
  .btn-primary { background:var(--primary); color:#fff; }
  .btn-primary:hover { background:var(--primary-dark); text-decoration:none; color:#fff; }
  .btn-outline { background:transparent; color:var(--primary); border:1.5px solid var(--primary); }
  .btn-outline:hover { background:var(--primary-light); text-decoration:none; }
  .btn-lg { padding:14px 32px; font-size:16px; font-weight:600; border-radius:12px; }
  .btn-white { background:#fff; color:var(--primary); font-weight:600; }
  .btn-white:hover { background:#eff6ff; text-decoration:none; color:var(--primary); }
  .btn-ghost-white { background:rgba(255,255,255,0.12); color:#fff; border:1.5px solid rgba(255,255,255,0.4); font-weight:600; }
  .btn-ghost-white:hover { background:rgba(255,255,255,0.22); text-decoration:none; color:#fff; }

  /* ── HERO ── */
  .hero {
    background:linear-gradient(145deg,#1e3a8a 0%,#2563eb 55%,#3b82f6 100%);
    padding:96px 48px 80px;
    text-align:center;
    color:#fff;
    position:relative;
    overflow:hidden;
  }
  .hero::before {
    content:'';
    position:absolute; inset:0;
    background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events:none;
  }
  .hero-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3); border-radius:100px; padding:6px 18px; font-size:13px; font-weight:500; margin-bottom:28px; }
  .hero h1 { font-size:clamp(30px,5vw,58px); font-weight:800; margin:0 0 20px; line-height:1.12; letter-spacing:-1px; }
  .hero h1 span { color:#93c5fd; }
  .hero p { font-size:18px; color:rgba(255,255,255,0.88); max-width:640px; margin:0 auto 40px; line-height:1.75; }
  .hero-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
  .hero-stats { display:flex; justify-content:center; gap:40px; margin-top:56px; flex-wrap:wrap; }
  .hero-stat { text-align:center; }
  .hero-stat-num { font-size:28px; font-weight:800; color:#fff; line-height:1; }
  .hero-stat-label { font-size:13px; color:rgba(255,255,255,0.7); margin-top:4px; }

  /* ── SECTIONS ── */
  .section { padding:80px 48px; }
  .section-alt { background:#f8fafc; }
  .section-title { text-align:center; font-size:clamp(24px,3vw,34px); font-weight:700; margin:0 0 12px; }
  .section-sub { text-align:center; color:var(--muted); font-size:16px; margin:0 0 52px; line-height:1.7; }
  .inner { max-width:1160px; margin:0 auto; }

  /* ── FEATURES GRID — Full 16 modules ── */
  .features-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; }
  .feature-card {
    background:var(--white);
    border:1.5px solid var(--border);
    border-radius:14px;
    padding:24px 22px;
    transition:all 0.2s;
    display:flex;
    flex-direction:column;
    gap:10px;
  }
  .feature-card:hover { border-color:var(--primary); box-shadow:0 8px 24px rgba(37,99,235,0.1); transform:translateY(-2px); }
  .feature-icon { font-size:28px; line-height:1; }
  .feature-card h3 { font-size:15px; font-weight:600; margin:0; color:var(--text); }
  .feature-card p { font-size:13px; color:var(--muted); margin:0; line-height:1.6; }
  .feature-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; align-self:flex-start; margin-top:auto; }
  .feature-badge.enterprise { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

  /* ── ADVANCED FEATURES PILLS ── */
  .pills-section { padding:60px 48px; }
  .pills-grid { display:flex; flex-wrap:wrap; gap:12px; justify-content:center; max-width:1000px; margin:0 auto; }
  .pill {
    display:flex; align-items:center; gap:8px;
    background:var(--white); border:1.5px solid var(--border);
    border-radius:100px; padding:10px 18px; font-size:13px; font-weight:500;
    transition:all 0.15s; color:var(--text);
  }
  .pill:hover { border-color:var(--primary); color:var(--primary); box-shadow:0 2px 8px rgba(37,99,235,0.1); }
  .pill-icon { font-size:16px; }

  /* ── HOW IT WORKS ── */
  .steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:32px; max-width:900px; margin:0 auto; }
  .step { text-align:center; }
  .step-num { width:52px; height:52px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700; margin:0 auto 16px; }
  .step h3 { font-size:15px; font-weight:600; margin:0 0 8px; }
  .step p { font-size:13px; color:var(--muted); margin:0; }

  /* ── LOGIN SELECTOR ── */
  .login-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:18px; max-width:900px; margin:32px auto 0; }
  .login-card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:14px; padding:28px 20px; text-align:center;
    transition:all 0.2s; display:block;
  }
  .login-card:hover { border-color:var(--primary); box-shadow:0 6px 20px rgba(37,99,235,0.12); transform:translateY(-2px); text-decoration:none; }
  .login-card-icon { font-size:34px; margin-bottom:10px; }
  .login-card h3 { font-size:15px; font-weight:600; color:var(--text); margin:0 0 6px; }
  .login-card p { font-size:13px; color:var(--muted); margin:0; }

  /* ── CTA BANNER ── */
  .cta-banner { background:linear-gradient(135deg,#1e3a8a,#2563eb); padding:72px 48px; text-align:center; color:#fff; }
  .cta-banner h2 { font-size:clamp(22px,3vw,34px); font-weight:700; margin:0 0 16px; }
  .cta-banner p { font-size:16px; opacity:0.85; margin:0 0 36px; }
  .cta-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }

  /* ── TRUST STRIP ── */
  .trust-strip { background:var(--white); border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:28px 48px; text-align:center; }
  .trust-strip p { color:var(--muted); font-size:13px; margin:0 0 16px; font-weight:500; letter-spacing:.5px; text-transform:uppercase; }
  .trust-items { display:flex; justify-content:center; align-items:center; gap:32px; flex-wrap:wrap; }
  .trust-item { font-size:13px; color:var(--muted); display:flex; align-items:center; gap:6px; font-weight:500; }

  /* ── FOOTER ── */
  .footer { background:#0f172a; color:#94a3b8; padding:48px; }
  .footer-inner { max-width:1160px; margin:0 auto; display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:48px; margin-bottom:40px; }
  .footer-brand h3 { color:#fff; font-size:18px; font-weight:700; margin:0 0 10px; display:flex; align-items:center; gap:8px; }
  .footer-brand p { font-size:13px; line-height:1.8; max-width:280px; }
  .footer-col h4 { color:#e2e8f0; font-size:13px; font-weight:600; margin:0 0 16px; text-transform:uppercase; letter-spacing:.5px; }
  .footer-col a { display:block; color:#94a3b8; font-size:13px; margin-bottom:10px; }
  .footer-col a:hover { color:#fff; text-decoration:none; }
  .footer-bottom { border-top:1px solid #1e293b; padding-top:24px; text-align:center; font-size:12px; max-width:1160px; margin:0 auto; }

  @media(max-width:900px) {
    .navbar { padding:0 20px; }
    .navbar-brand span:not(.logo-icon) { display:none; }
    .hero { padding:64px 20px 52px; }
    .section, .pills-section { padding:52px 20px; }
    .cta-banner { padding:52px 20px; }
    .trust-strip { padding:24px 20px; }
    .footer { padding:40px 20px 24px; }
    .footer-inner { grid-template-columns:1fr 1fr; gap:32px; }
    .hero-stats { gap:24px; }
  }
  @media(max-width:600px) {
    .footer-inner { grid-template-columns:1fr; }
    .hero-actions, .cta-actions { flex-direction:column; align-items:center; }
    .trust-items { gap:16px; }
  }
</style>
</head>
<body>

{{-- ─── NAVBAR ─── --}}
<nav class="navbar">
  <a href="{{ route('school.landing') }}" class="navbar-brand">
    <div class="logo-icon">K</div>
    <div>
      KynexEdu
      <span class="logo-sub">by Kynex Solutions</span>
    </div>
  </a>
  <div class="navbar-links">
    <a href="{{ route('school.register') }}" class="btn btn-outline">Register School</a>
    <a href="{{ route('school.login') }}" class="btn btn-primary">Login</a>
  </div>
</nav>

{{-- ─── HERO ─── --}}
<section class="hero">
  <div class="hero-badge">🇵🇰 &nbsp;Pakistan's Most Complete School ERP</div>
  <h1>
    Everything Your School Needs,<br>
    <span>All in One Platform</span>
  </h1>
  <p>KynexEdu is a cloud-based, multi-tenant School Management ERP covering 16 core modules — from admissions to payroll, library to transport, WhatsApp notifications to biometric attendance.</p>
  <div class="hero-actions">
    <a href="{{ route('school.register') }}" class="btn btn-white btn-lg">Register Your School Free</a>
    <a href="{{ route('school.login') }}" class="btn btn-ghost-white btn-lg">Login to Dashboard</a>
  </div>
  <div class="hero-stats">
    <div class="hero-stat">
      <div class="hero-stat-num">16+</div>
      <div class="hero-stat-label">Core Modules</div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-num">19</div>
      <div class="hero-stat-label">Role Types</div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-num">100+</div>
      <div class="hero-stat-label">Permissions</div>
    </div>
    <div class="hero-stat">
      <div class="hero-stat-num">3</div>
      <div class="hero-stat-label">Apps (Web, iOS, Android)</div>
    </div>
  </div>
</section>

{{-- ─── TRUST STRIP ─── --}}
<div class="trust-strip">
  <p>Built for</p>
  <div class="trust-items">
    <div class="trust-item">🏫 Single Schools</div>
    <div class="trust-item">🏛️ Multi-Campus Institutes</div>
    <div class="trust-item">🏢 Multi-Institute Owners</div>
    <div class="trust-item">🎓 Private &amp; Government Schools</div>
    <div class="trust-item">🌐 Urdu &amp; English Support</div>
  </div>
</div>

{{-- ─── 16 CORE MODULES ─── --}}
<section class="section inner">
  <h2 class="section-title">16 Complete Core Modules</h2>
  <p class="section-sub">Every module your school needs, built in — no add-ons, no surprises.</p>
  <div class="features-grid">

    <div class="feature-card">
      <div class="feature-icon">🎓</div>
      <h3>Student Lifecycle</h3>
      <p>Admissions, profiles, guardians, documents, status tracking, promotions, and dismissals — with an approval workflow.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📚</div>
      <h3>Academic Setup</h3>
      <p>Academic years, classes, sections, subjects, class routines, and timetable management.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📋</div>
      <h3>Attendance</h3>
      <p>Daily student &amp; staff attendance, biometric (ZKTeco ADMS), late arrival detection, daily scoring, and detailed reports.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📝</div>
      <h3>Examinations &amp; Results</h3>
      <p>Exam scheduling, marks entry, grade rules, weighted results, report cards, and public result search.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">💰</div>
      <h3>Fees &amp; Finance</h3>
      <p>Flexible fee structures, installment plans, online payments (JazzCash, EasyPaisa), refunds, and fee reports.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">👨‍💼</div>
      <h3>HR &amp; Payroll</h3>
      <p>Staff profiles, salary components, payroll generation, payslip PDF downloads, leave types, and leave requests.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">💸</div>
      <h3>Expense &amp; Budget</h3>
      <p>Expense categories, budget planning, expense tracking with mandatory approval for amounts over PKR 50,000.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📖</div>
      <h3>Library</h3>
      <p>Book catalog, categories, member management, issue &amp; return tracking, overdue fines, and library reports.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🚌</div>
      <h3>Transport</h3>
      <p>Vehicle management, route planning, stops, student transport assignments, and tracking.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🏠</div>
      <h3>Hostel Management</h3>
      <p>Buildings, room types, rooms, student allocations, and gate pass management for boarding schools.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🚪</div>
      <h3>Visitor Management</h3>
      <p>Visitor check-in/out, purpose tracking, badge generation, and photo capture at the front desk.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📦</div>
      <h3>Inventory &amp; Assets</h3>
      <p>Asset categories, items, stores, supplier management, and full stock transaction history.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">💻</div>
      <h3>Online Classes</h3>
      <p>Class scheduling across multiple platforms, student attendance tracking, and quiz assignment.</p>
      <span class="feature-badge">Core</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🏥</div>
      <h3>Health Records</h3>
      <p>Confidential student health &amp; medical records with triple-layer security — RLS, Eloquent scope, and role gating.</p>
      <span class="feature-badge enterprise">Confidential</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🧠</div>
      <h3>Behavior &amp; Counseling</h3>
      <p>Incident logging, counseling records, and behavior tracking — strictly isolated with PostgreSQL Row Level Security.</p>
      <span class="feature-badge enterprise">Confidential</span>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🍽️</div>
      <h3>Cafeteria</h3>
      <p>Menu management, daily transactions, and student cafeteria balance tracking.</p>
      <span class="feature-badge">Core</span>
    </div>

  </div>
</section>

{{-- ─── ADVANCED CAPABILITIES ─── --}}
<section class="section section-alt pills-section">
  <h2 class="section-title">Advanced Capabilities</h2>
  <p class="section-sub">Beyond the core — enterprise-grade features built in from day one.</p>
  <div class="pills-grid">

    <div class="pill"><span class="pill-icon">📢</span> WhatsApp Notifications</div>
    <div class="pill"><span class="pill-icon">📱</span> SMS Notifications</div>
    <div class="pill"><span class="pill-icon">🔔</span> In-App Push Notifications</div>
    <div class="pill"><span class="pill-icon">🤖</span> WhatsApp Bot (FEE, RESULT, ATTENDANCE)</div>
    <div class="pill"><span class="pill-icon">✅</span> Approval Workflow System</div>
    <div class="pill"><span class="pill-icon">🔐</span> 19 Role Types (RBAC)</div>
    <div class="pill"><span class="pill-icon">🔄</span> Role Switcher</div>
    <div class="pill"><span class="pill-icon">🏫</span> Multi-Campus Support</div>
    <div class="pill"><span class="pill-icon">🏢</span> Multi-Institute Ownership</div>
    <div class="pill"><span class="pill-icon">🌐</span> Custom Domains + SSL</div>
    <div class="pill"><span class="pill-icon">📊</span> Custom Report Builder</div>
    <div class="pill"><span class="pill-icon">📈</span> Analytics Dashboard</div>
    <div class="pill"><span class="pill-icon">🆔</span> ID Card &amp; Certificate Generation</div>
    <div class="pill"><span class="pill-icon">📝</span> Homework &amp; Assignment</div>
    <div class="pill"><span class="pill-icon">🗒️</span> Notices &amp; Events</div>
    <div class="pill"><span class="pill-icon">🌍</span> Public School Website (CMS)</div>
    <div class="pill"><span class="pill-icon">🔵</span> Biometric (ZKTeco) Integration</div>
    <div class="pill"><span class="pill-icon">🤳</span> Android SMS Gateway</div>
    <div class="pill"><span class="pill-icon">📲</span> React Native Mobile Apps</div>
    <div class="pill"><span class="pill-icon">🧾</span> Monthly Billing &amp; Invoicing</div>
    <div class="pill"><span class="pill-icon">🛡️</span> PostgreSQL Row Level Security</div>
    <div class="pill"><span class="pill-icon">🔒</span> PII Audit Trail (FERPA 7-year)</div>
    <div class="pill"><span class="pill-icon">🌙</span> Urdu / Bilingual Support</div>
    <div class="pill"><span class="pill-icon">🤖</span> AI Assistant (OpenRouter)</div>
    <div class="pill"><span class="pill-icon">🗂️</span> Infix ERP Data Import</div>
    <div class="pill"><span class="pill-icon">💳</span> JazzCash &amp; EasyPaisa Payments</div>
    <div class="pill"><span class="pill-icon">⚡</span> Evolution API WhatsApp</div>
    <div class="pill"><span class="pill-icon">🏷️</span> Subscription Plan Management</div>

  </div>
</section>

{{-- ─── HOW IT WORKS ─── --}}
<section class="section inner">
  <h2 class="section-title">Get Started in Minutes</h2>
  <p class="section-sub">No IT team required. Simple onboarding, instant access.</p>
  <div class="steps">
    <div class="step">
      <div class="step-num">1</div>
      <h3>Register Your School</h3>
      <p>Fill in your school details and submit the registration form on this page.</p>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <h3>Verify Your Email</h3>
      <p>Click the verification link we'll send to your inbox — expires in 3 hours.</p>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <h3>Set Your Password</h3>
      <p>Create a secure password to activate your school admin account.</p>
    </div>
    <div class="step">
      <div class="step-num">4</div>
      <h3>Start Managing</h3>
      <p>Log in and start setting up your school — classes, students, staff, and more.</p>
    </div>
  </div>
</section>

{{-- ─── LOGIN SELECTOR ─── --}}
<section class="section section-alt">
  <div class="inner">
    <h2 class="section-title">Choose Your Login</h2>
    <p class="section-sub">All roles — admins, teachers, parents — use the same login page.<br>Your dashboard is determined by your assigned role.</p>
    <div class="login-cards">
      <a href="{{ route('school.login') }}" class="login-card">
        <div class="login-card-icon">🏫</div>
        <h3>School Admin</h3>
        <p>Principals, administrators, registrars</p>
      </a>
      <a href="{{ route('school.login') }}" class="login-card">
        <div class="login-card-icon">👨‍🏫</div>
        <h3>Teacher / Staff</h3>
        <p>Teachers, HR, accountants, nurses</p>
      </a>
      <a href="{{ route('school.login') }}" class="login-card">
        <div class="login-card-icon">👨‍👩‍👧</div>
        <h3>Parent / Guardian</h3>
        <p>Track your child's progress</p>
      </a>
      <a href="{{ route('school.login') }}" class="login-card">
        <div class="login-card-icon">🏢</div>
        <h3>Institute Owner</h3>
        <p>Multi-campus &amp; multi-school owners</p>
      </a>
    </div>
    <p style="text-align:center; margin-top:24px; font-size:14px; color:var(--muted);">
      Forgot your password? <a href="{{ route('school.forgot-password') }}">Reset it here</a>
    </p>
  </div>
</section>

{{-- ─── CTA ─── --}}
<section class="cta-banner">
  <h2>Ready to transform your school?</h2>
  <p>Join schools across Pakistan using KynexEdu to run smarter, faster, and with full control.</p>
  <div class="cta-actions">
    <a href="{{ route('school.register') }}" class="btn btn-white btn-lg">Register Free — No Credit Card</a>
    <a href="{{ route('school.login') }}" class="btn btn-ghost-white btn-lg">Login to Dashboard</a>
  </div>
</section>

{{-- ─── FOOTER ─── --}}
<footer class="footer">
  <div class="footer-inner">

    <div class="footer-brand">
      <h3>
        <div class="logo-icon" style="width:28px;height:28px;font-size:13px;">K</div>
        KynexEdu
      </h3>
      <p>Pakistan's most complete cloud-based School Management ERP. Developed and maintained by Kynex Solutions.</p>
      <p style="margin-top:12px;"><a href="https://kynexsolutions.com" target="_blank" style="color:#60a5fa;">kynexsolutions.com</a></p>
    </div>

    <div class="footer-col">
      <h4>Platform</h4>
      <a href="{{ route('school.register') }}">Register School</a>
      <a href="{{ route('school.login') }}">Login</a>
      <a href="{{ route('school.forgot-password') }}">Reset Password</a>
      <a href="https://kynexsolutions.com/docs" target="_blank">Documentation</a>
    </div>

    <div class="footer-col">
      <h4>Modules</h4>
      <a href="#">Students &amp; Admissions</a>
      <a href="#">Fees &amp; Finance</a>
      <a href="#">Exams &amp; Results</a>
      <a href="#">HR &amp; Payroll</a>
      <a href="#">Library &amp; Transport</a>
    </div>

    <div class="footer-col">
      <h4>Company</h4>
      <a href="https://kynexsolutions.com" target="_blank">Kynex Solutions</a>
      <a href="https://kynexsolutions.com/contact" target="_blank">Contact Us</a>
      <a href="mailto:support@kynexsolutions.com">Support</a>
    </div>

  </div>
  <div class="footer-bottom">
    &copy; {{ date('Y') }} Kynex Solutions &mdash; All Rights Reserved
    &middot; <a href="https://kynexsolutions.com" style="color:#64748b;" target="_blank">kynexsolutions.com</a>
    &middot; <a href="https://edu.kynexsolutions.com" style="color:#64748b;">edu.kynexsolutions.com</a>
  </div>
</footer>

</body>
</html>
