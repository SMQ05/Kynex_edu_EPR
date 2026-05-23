<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">

{{-- ─── SEO: primary meta ─── --}}
<title>KynexEdu — School Management System &amp; ERP Software</title>
<meta name="description" content="KynexEdu is a cloud-based school management system &amp; ERP — online admissions &amp; entry tests, fees, attendance, exams, HR, WhatsApp &amp; biometric. Register your school free.">
{{-- Generic "school management system in Pakistan" SEO is owned by the parent page
     (kynexsolutions.com/school-management-system). This product home leads with the
     BRAND to avoid keyword cannibalization. See docs/seo/SEO-STRATEGY.md §0.1. --}}
<link rel="canonical" href="https://edu.kynexsolutions.com/">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="author" content="Kynex Solutions">
<meta name="keywords" content="school management system, school management software, school ERP, school software Pakistan, online admission system, school fee management software, student attendance system, cloud school management system, KynexEdu">
<link rel="alternate" hreflang="en-pk" href="https://edu.kynexsolutions.com/">
<link rel="alternate" hreflang="x-default" href="https://edu.kynexsolutions.com/">

{{-- ─── SEO: Open Graph ─── --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="KynexEdu">
<meta property="og:title" content="KynexEdu — School Management System &amp; ERP for Pakistan">
<meta property="og:description" content="Cloud school management system &amp; ERP — online admissions &amp; entry tests, fees, attendance, exams, HR, WhatsApp &amp; biometric. All in one platform.">
<meta property="og:url" content="https://edu.kynexsolutions.com/">
<meta property="og:image" content="https://edu.kynexsolutions.com/images/og-kynexedu.png">
<meta property="og:image:secure_url" content="https://edu.kynexsolutions.com/images/og-kynexedu.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="KynexEdu — Complete School Management ERP">
<meta property="og:locale" content="en_PK">
<meta property="og:locale:alternate" content="ur_PK">

{{-- ─── SEO: Twitter / X ─── --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="KynexEdu — School Management System &amp; ERP for Pakistan">
<meta name="twitter:description" content="Cloud school ERP — online admissions &amp; entry tests, fees, attendance, exams, HR, WhatsApp &amp; biometric. Register your school free.">
<meta name="twitter:image" content="https://edu.kynexsolutions.com/images/og-kynexedu.png">
<meta name="twitter:image:alt" content="KynexEdu — Complete School Management ERP">

@include('public.partials.theme')
<style>
  /* ── HERO ── */
  .hero { position:relative; background:var(--grad-hero); color:#fff; overflow:hidden; isolation:isolate; }
  .hero::before { content:''; position:absolute; inset:0; z-index:-1;
    background:
      radial-gradient(40rem 40rem at 80% -10%, rgba(6,182,212,.45), transparent 60%),
      radial-gradient(30rem 30rem at 0% 110%, rgba(124,58,237,.4), transparent 55%); }
  .hero::after { content:''; position:absolute; inset:0; z-index:-1; opacity:.5;
    background-image:url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='20' cy='20' r='1.5'/%3E%3C/g%3E%3C/svg%3E"); }
  .hero-wrap { display:grid; grid-template-columns:1fr; gap:48px; align-items:center; padding:64px 0 72px; }
  @media(min-width:980px){ .hero-wrap { grid-template-columns:1.05fr .95fr; padding:96px 0 104px; } }
  .hero-badge { display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28); border-radius:var(--r-pill); padding:7px 16px; font-size:13px; font-weight:600; }
  .hero h1 { font-size:clamp(33px,6.4vw,60px); font-weight:900; letter-spacing:-.025em; line-height:1.08; margin:22px 0 18px; }
  .hero h1 b { background:linear-gradient(120deg,#a5b4fc,#67e8f9); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; font-weight:900; }
  .hero p.lead { font-size:clamp(15.5px,2.4vw,19px); color:rgba(255,255,255,.86); max-width:560px; margin:0 0 32px; }
  .hero-actions { display:flex; flex-direction:column; gap:12px; }
  @media(min-width:480px){ .hero-actions { flex-direction:row; flex-wrap:wrap; } }
  .hero-stats { display:grid; grid-template-columns:repeat(2,1fr); gap:18px 12px; margin-top:44px; max-width:520px; }
  @media(min-width:560px){ .hero-stats { grid-template-columns:repeat(4,1fr); } }
  .hstat-num { font-size:clamp(24px,4vw,30px); font-weight:900; line-height:1; background:linear-gradient(120deg,#fff,#bae6fd); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
  .hstat-label { font-size:12px; color:rgba(255,255,255,.7); margin-top:6px; }

  /* App-window mockup */
  .mock { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.18); border-radius:var(--r-xl); padding:14px; box-shadow:var(--sh-lg); backdrop-filter:blur(8px); }
  .mock-bar { display:flex; gap:7px; padding:4px 6px 12px; }
  .mock-dot { width:11px; height:11px; border-radius:50%; background:rgba(255,255,255,.3); }
  .mock-body { background:#fff; border-radius:16px; padding:16px; display:grid; grid-template-columns:1fr; gap:12px; }
  .mock-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
  .mock-stat { background:var(--bg); border:1px solid var(--line); border-radius:12px; padding:12px; }
  .mock-stat .k { font-size:10px; color:var(--muted); font-weight:600; }
  .mock-stat .v { font-size:18px; font-weight:800; color:var(--ink); margin-top:3px; }
  .mock-stat .v.g { color:var(--success); }
  .mock-chart { display:flex; align-items:flex-end; gap:8px; height:96px; background:var(--bg); border:1px solid var(--line); border-radius:12px; padding:12px; }
  .mock-chart i { flex:1; border-radius:5px 5px 0 0; background:var(--grad-brand); opacity:.85; animation:rise 1.2s cubic-bezier(.16,1,.3,1) backwards; }
  @keyframes rise { from { height:6%; opacity:.2; } }
  .float-card { position:absolute; background:#fff; border-radius:14px; box-shadow:var(--sh-lg); padding:11px 14px; display:flex; align-items:center; gap:10px; font-size:12.5px; font-weight:700; color:var(--ink); }
  .float-card .ic { width:30px; height:30px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; }
  .mock-stage { position:relative; }
  @media(max-width:560px){ .float-card { display:none; } }

  /* Trust strip */
  .trust { background:#fff; border-top:1px solid var(--line); border-bottom:1px solid var(--line); padding:22px 0; }
  .trust-label { text-align:center; font-size:11.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted-2); margin:0 0 14px; }
  .trust-row { display:flex; flex-wrap:wrap; justify-content:center; gap:12px 26px; }
  .trust-item { font-size:13.5px; font-weight:600; color:var(--muted); display:flex; align-items:center; gap:7px; }

  /* Spotlight (admissions) */
  .spotlight { display:grid; grid-template-columns:1fr; gap:18px; }
  @media(min-width:860px){ .spotlight { grid-template-columns:1.15fr 1fr 1fr; grid-auto-rows:1fr; } }
  .spot-card { position:relative; border-radius:var(--r-lg); padding:28px 24px; overflow:hidden; border:1px solid var(--line); background:#fff; }
  .spot-hero { grid-row:span 1; color:#fff; background:var(--grad-hero); border:none; }
  @media(min-width:860px){ .spot-hero { grid-row:span 2; } }
  .spot-hero::after { content:''; position:absolute; inset:0; background:radial-gradient(20rem 20rem at 90% 0%, rgba(6,182,212,.5), transparent 60%); }
  .spot-hero > * { position:relative; z-index:1; }
  .spot-ic { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:26px; margin-bottom:16px; }
  .spot-hero .spot-ic { background:rgba(255,255,255,.16); }
  .spot-card:not(.spot-hero) .spot-ic { background:var(--brand-50); }
  .spot-card h3 { font-size:19px; font-weight:800; margin:0 0 8px; letter-spacing:-.01em; }
  .spot-hero h3 { font-size:clamp(22px,3vw,28px); }
  .spot-card p { font-size:14.5px; margin:0; color:var(--muted); }
  .spot-hero p { color:rgba(255,255,255,.85); }
  .spot-list { list-style:none; padding:0; margin:18px 0 0; display:flex; flex-direction:column; gap:10px; }
  .spot-list li { display:flex; gap:10px; font-size:14px; color:rgba(255,255,255,.92); }
  .spot-list li::before { content:'✓'; color:#67e8f9; font-weight:900; }

  /* Modules grid */
  .modules { display:grid; grid-template-columns:1fr; gap:16px; }
  @media(min-width:560px){ .modules { grid-template-columns:repeat(2,1fr); } }
  @media(min-width:900px){ .modules { grid-template-columns:repeat(3,1fr); } }
  @media(min-width:1100px){ .modules { grid-template-columns:repeat(4,1fr); } }
  .mod { display:flex; flex-direction:column; gap:10px; }
  .mod-ic { width:48px; height:48px; border-radius:13px; background:var(--brand-50); display:flex; align-items:center; justify-content:center; font-size:24px; }
  .mod h3 { font-size:16px; font-weight:700; margin:0; }
  .mod p { font-size:13.5px; color:var(--muted); margin:0; line-height:1.6; }
  .mod .tag { align-self:flex-start; margin-top:auto; }

  /* Pills */
  .pills { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; max-width:1000px; margin:0 auto; }

  /* Portals / roles */
  .portals { display:grid; grid-template-columns:1fr; gap:16px; }
  @media(min-width:560px){ .portals { grid-template-columns:repeat(2,1fr); } }
  @media(min-width:920px){ .portals { grid-template-columns:repeat(4,1fr); } }
  .portal-card { text-align:center; display:flex; flex-direction:column; align-items:center; gap:8px; }
  .portal-card .pic { width:60px; height:60px; border-radius:16px; background:var(--grad-brand); color:#fff; display:flex; align-items:center; justify-content:center; font-size:28px; box-shadow:var(--sh-brand); }
  .portal-card h3 { font-size:16px; font-weight:700; margin:6px 0 0; }
  .portal-card p { font-size:13px; color:var(--muted); margin:0; }

  /* Steps */
  .steps { display:grid; grid-template-columns:1fr; gap:14px; counter-reset:s; }
  @media(min-width:560px){ .steps { grid-template-columns:repeat(2,1fr); } }
  @media(min-width:980px){ .steps { grid-template-columns:repeat(4,1fr); } }
  .step { position:relative; background:#fff; border:1px solid var(--line); border-radius:var(--r); padding:24px 20px; }
  .step-num { width:44px; height:44px; border-radius:12px; background:var(--grad-brand); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:17px; margin-bottom:14px; box-shadow:var(--sh-brand); }
  .step h3 { font-size:15.5px; font-weight:700; margin:0 0 6px; }
  .step p { font-size:13.5px; color:var(--muted); margin:0; }

  /* CTA banner */
  .cta { position:relative; background:var(--grad-hero); color:#fff; text-align:center; overflow:hidden; isolation:isolate; }
  .cta::before { content:''; position:absolute; inset:0; z-index:-1; background:radial-gradient(28rem 28rem at 50% -20%, rgba(6,182,212,.4), transparent 60%); }
  .cta h2 { font-size:clamp(24px,4vw,38px); font-weight:900; letter-spacing:-.02em; margin:0 0 14px; }
  .cta p { font-size:clamp(15px,2.2vw,18px); color:rgba(255,255,255,.85); margin:0 0 30px; }
  .cta-actions { display:flex; flex-direction:column; gap:12px; justify-content:center; align-items:center; }
  @media(min-width:480px){ .cta-actions { flex-direction:row; } }

  /* Sticky mobile CTA */
  .sticky-cta {
    position:fixed; left:0; right:0; bottom:0; z-index:120;
    display:flex; gap:10px; padding:12px 16px calc(12px + env(safe-area-inset-bottom));
    background:rgba(255,255,255,.92); backdrop-filter:blur(12px); border-top:1px solid var(--line);
    transform:translateY(120%); transition:transform .3s ease;
  }
  .sticky-cta.show { transform:translateY(0); }
  .sticky-cta .btn { flex:1; }
  @media(min-width:900px){ .sticky-cta { display:none; } }
</style>

{{-- ─── SEO: structured data (Schema.org JSON-LD) ─── --}}
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://kynexsolutions.com/#organization",
      "name": "Kynex Solutions",
      "url": "https://kynexsolutions.com",
      "logo": "https://edu.kynexsolutions.com/images/og-kynexedu.png",
      "description": "Kynex Solutions builds KynexEdu, a complete cloud-based school management system and ERP for schools in Pakistan.",
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "support@kynexsolutions.com",
        "contactType": "customer support",
        "areaServed": "PK",
        "availableLanguage": ["en", "ur"]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://edu.kynexsolutions.com/#website",
      "url": "https://edu.kynexsolutions.com/",
      "name": "KynexEdu",
      "inLanguage": "en-PK",
      "publisher": { "@id": "https://kynexsolutions.com/#organization" }
    },
    {
      "@type": "SoftwareApplication",
      "@id": "https://edu.kynexsolutions.com/#software",
      "name": "KynexEdu",
      "alternateName": "KynexEdu School Management System",
      "applicationCategory": "BusinessApplication",
      "applicationSubCategory": "School Management System",
      "operatingSystem": "Web, iOS, Android",
      "url": "https://edu.kynexsolutions.com/",
      "image": "https://edu.kynexsolutions.com/images/og-kynexedu.png",
      "description": "KynexEdu is a cloud-based, multi-tenant school management system and ERP covering online admissions and proctored entry tests, fees and online payments, attendance and biometric, exams and results, HR and payroll, library, transport, hostel, inventory, a parent portal, WhatsApp and SMS notifications, and a public school website.",
      "publisher": { "@id": "https://kynexsolutions.com/#organization" },
      "inLanguage": ["en", "ur"],
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "PKR",
        "description": "Free school registration. Trial and paid subscription plans available.",
        "availability": "https://schema.org/InStock"
      },
      "featureList": [
        "Online admissions and proctored entry tests",
        "Fee management and online payments (JazzCash, EasyPaisa)",
        "Student and staff attendance with biometric (ZKTeco)",
        "Examinations, grading and result cards",
        "HR and payroll",
        "Library, transport, hostel and inventory",
        "Parent portal and mobile apps",
        "WhatsApp, SMS and in-app notifications",
        "Public school website (CMS)"
      ]
    }
  ]
}
</script>
</head>
<body>

{{-- ─── NAV ─── --}}
<nav class="nav">
  <div class="inner nav-inner">
    <a href="{{ route('school.landing') }}" class="brand">
      <span class="brand-logo">K</span>
      <span>KynexEdu<span class="brand-sub">by Kynex Solutions</span></span>
    </a>
    <div class="nav-links">
      <a href="#modules" class="nav-link">Modules</a>
      <a href="#admissions" class="nav-link">Admissions</a>
      <a href="#capabilities" class="nav-link">Capabilities</a>
      <a href="#portals" class="nav-link">For everyone</a>
    </div>
    <div class="nav-cta">
      <a href="{{ route('school.login') }}" class="btn btn-outline btn-sm">Login</a>
      <a href="{{ route('school.register') }}" class="btn btn-primary btn-sm">Register School</a>
    </div>
    <button class="nav-toggle" data-nav-open aria-label="Open menu" aria-expanded="false">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </div>
</nav>

{{-- Mobile drawer --}}
<div class="drawer-backdrop"></div>
<aside class="drawer" aria-label="Mobile navigation">
  <div class="drawer-head">
    <a href="{{ route('school.landing') }}" class="brand"><span class="brand-logo">K</span><span>KynexEdu</span></a>
    <button class="drawer-close" data-nav-close aria-label="Close menu">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </div>
  <a href="#modules" class="drawer-link">Modules</a>
  <a href="#admissions" class="drawer-link">Admissions Suite</a>
  <a href="#capabilities" class="drawer-link">Capabilities</a>
  <a href="#portals" class="drawer-link">For everyone</a>
  <a href="#how" class="drawer-link">How it works</a>
  <div class="drawer-actions">
    <a href="{{ route('school.login') }}" class="btn btn-outline btn-block">Login to Dashboard</a>
    <a href="{{ route('school.register') }}" class="btn btn-primary btn-block">Register Your School</a>
  </div>
</aside>

{{-- ─── HERO ─── --}}
<header class="hero">
  <div class="inner hero-wrap">
    <div>
      <span class="hero-badge">🇵🇰 Pakistan's Most Complete School ERP</span>
      <h1>Everything your school needs,<br><b>all in one platform</b></h1>
      <p class="lead">Cloud-based, multi-tenant School Management ERP — from <strong style="color:#fff">online admissions &amp; proctored entry tests</strong> to fees, attendance, exams, HR, library, transport, WhatsApp &amp; biometric.</p>
      <div class="hero-actions">
        <a href="{{ route('school.register') }}" class="btn btn-white btn-lg">Register Your School Free →</a>
        <a href="{{ route('school.login') }}" class="btn btn-ghost-white btn-lg">Login to Dashboard</a>
      </div>
      <div class="hero-stats">
        <div><div class="hstat-num">20+</div><div class="hstat-label">Core Modules</div></div>
        <div><div class="hstat-num">19</div><div class="hstat-label">Role Types</div></div>
        <div><div class="hstat-num">100+</div><div class="hstat-label">Permissions</div></div>
        <div><div class="hstat-num">3</div><div class="hstat-label">Apps · Web · iOS · Android</div></div>
      </div>
    </div>

    <div class="mock-stage reveal">
      <div class="mock">
        <div class="mock-bar"><span class="mock-dot"></span><span class="mock-dot"></span><span class="mock-dot"></span></div>
        <div class="mock-body">
          <div class="mock-row">
            <div class="mock-stat"><div class="k">Students</div><div class="v">1,284</div></div>
            <div class="mock-stat"><div class="k">Attendance</div><div class="v g">96%</div></div>
            <div class="mock-stat"><div class="k">Fees Today</div><div class="v">₨ 412k</div></div>
          </div>
          <div class="mock-chart">
            <i style="height:55%;animation-delay:.05s"></i><i style="height:72%;animation-delay:.1s"></i>
            <i style="height:48%;animation-delay:.15s"></i><i style="height:88%;animation-delay:.2s"></i>
            <i style="height:64%;animation-delay:.25s"></i><i style="height:95%;animation-delay:.3s"></i>
            <i style="height:70%;animation-delay:.35s"></i>
          </div>
          <div class="mock-row">
            <div class="mock-stat"><div class="k">Applications</div><div class="v">68</div></div>
            <div class="mock-stat"><div class="k">Entry Tests</div><div class="v g">Live</div></div>
            <div class="mock-stat"><div class="k">Defaulters</div><div class="v">23</div></div>
          </div>
        </div>
      </div>
      <div class="float-card" style="top:-14px;left:-12px"><span class="ic" style="background:#ecfdf5">📲</span> WhatsApp sent</div>
      <div class="float-card" style="bottom:-16px;right:-10px"><span class="ic" style="background:#eef2ff">🔒</span> Biometric check-in</div>
    </div>
  </div>
</header>

{{-- ─── TRUST ─── --}}
<div class="trust">
  <div class="inner">
    <p class="trust-label">Built for</p>
    <div class="trust-row">
      <span class="trust-item">🏫 Single Schools</span>
      <span class="trust-item">🏛️ Multi-Campus Institutes</span>
      <span class="trust-item">🏢 Multi-Institute Owners</span>
      <span class="trust-item">🎓 Private &amp; Government</span>
      <span class="trust-item">🌐 Urdu &amp; English</span>
    </div>
  </div>
</div>

{{-- ─── ADMISSIONS SPOTLIGHT (flagship) ─── --}}
<section class="section section-alt" id="admissions">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">✦ Flagship</span>
      <h2 class="section-title">A complete online admissions suite</h2>
      <p class="section-sub">Applicants apply online, sit a <strong>proctored entry test</strong>, and you decide — with AI scoring assistance. End to end, no paperwork.</p>
    </div>
    <div class="spotlight">
      <div class="spot-card spot-hero reveal">
        <div class="spot-ic">🎯</div>
        <h3>Proctored Online Entry Test</h3>
        <p>Token-based exams with a live waiting room and automatic anti-cheat — focus-loss &amp; tab-switch violations auto-cancel the attempt.</p>
        <ul class="spot-list">
          <li>Exam-day temporary login</li>
          <li>Real-time waiting room &amp; start control</li>
          <li>Tab-switch / focus violation detection</li>
          <li>Marks entry &amp; exam attendance</li>
        </ul>
      </div>
      <div class="spot-card reveal">
        <div class="spot-ic">📝</div>
        <h3>Public Apply Portal</h3>
        <p>Branded online application form with document upload, status tracking by token, parent self-registration, and post-admit profile completion.</p>
        <span class="tag tag-new" style="margin-top:14px">Self-service</span>
      </div>
      <div class="spot-card reveal">
        <div class="spot-ic">🤖</div>
        <h3>AI Admission Assistant</h3>
        <p>AI-assisted scoring and review to speed up shortlisting, with a human approval workflow for every final decision.</p>
        <span class="tag tag-secure" style="margin-top:14px">AI · OpenRouter</span>
      </div>
    </div>
  </div>
</section>

{{-- ─── CORE MODULES ─── --}}
<section class="section" id="modules">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">🧩 Core platform</span>
      <h2 class="section-title">Every module your school needs</h2>
      <p class="section-sub">Built in — no add-ons, no surprises. One login, one bill, one platform.</p>
    </div>
    <div class="modules">
      @php
        $modules = [
          ['🎓','Student Lifecycle','Admissions, profiles, guardians, documents, promotions and dismissals — with approval workflow.','core'],
          ['📚','Academic Setup','Academic years, classes, sections, subjects, syllabus, class routines and timetables.','core'],
          ['📋','Attendance','Daily student &amp; staff attendance, biometric (ZKTeco ADMS), late detection and reports.','core'],
          ['📝','Exams &amp; Results','Scheduling, marks entry, grade rules, weighted results, report cards and public result search.','core'],
          ['💰','Fees &amp; Finance','Flexible fee structures, installments, online payments (JazzCash, EasyPaisa), refunds and reports.','core'],
          ['👨‍💼','HR &amp; Payroll','Staff profiles, salary components, payroll, payslip PDFs, leave types and leave requests.','core'],
          ['💸','Expense &amp; Budget','Expense categories, budget planning and tracking with approval over PKR 50,000.','core'],
          ['📖','Library','Catalog, categories, members, issue &amp; return, overdue fines and reports.','core'],
          ['🚌','Transport','Vehicles, routes, stops, student transport assignments and tracking.','core'],
          ['🏠','Hostel','Buildings, room types, rooms, allocations and gate-pass management.','core'],
          ['🚪','Visitor Management','Check-in/out, purpose tracking, photo capture and badge generation.','core'],
          ['📦','Inventory &amp; Assets','Categories, items, stores, suppliers and full stock transaction history.','core'],
          ['💻','Online Classes','Class scheduling across platforms, attendance tracking and quizzes.','core'],
          ['🏥','Health Records','Confidential medical records — RLS, Eloquent scope and role gating.','secure'],
          ['🧠','Behavior &amp; Counseling','Incident logging and counseling records — isolated by PostgreSQL RLS.','secure'],
          ['🍽️','Cafeteria','Menu management, daily transactions and student balance tracking.','core'],
          ['🆔','ID Cards &amp; Certificates','Designable templates, batch generation and public QR verification.','core'],
          ['📝','Homework','Assignments, submissions and pending-review tracking.','core'],
          ['🗒️','Notices &amp; Events','School-wide notices, events and targeted announcements.','core'],
          ['🌍','Public Website (CMS)','Per-school website — pages, news, gallery, sliders and contact forms.','core'],
        ];
      @endphp
      @foreach($modules as [$ic,$title,$desc,$kind])
        <div class="card mod reveal">
          <div class="mod-ic">{!! $ic !!}</div>
          <h3>{!! $title !!}</h3>
          <p>{!! $desc !!}</p>
          <span class="tag {{ $kind === 'secure' ? 'tag-secure' : 'tag-core' }}">{{ $kind === 'secure' ? 'Confidential' : 'Core' }}</span>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ─── CAPABILITIES ─── --}}
<section class="section section-alt" id="capabilities">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">⚡ Beyond the core</span>
      <h2 class="section-title">Enterprise capabilities, built in</h2>
      <p class="section-sub">From day one — communication, security, integrations and scale.</p>
    </div>
    <div class="pills reveal">
      @php
        $pills = [
          '📢 WhatsApp Notifications','📱 SMS Notifications','🔔 In-App Push','🤖 WhatsApp Bot (FEE · RESULT · ATTENDANCE)',
          '✅ Approval Workflow','🔐 19 Role Types (RBAC)','🔄 Role Switcher','🏫 Multi-Campus','🏢 Multi-Institute',
          '🌐 Custom Domains + SSL','📊 Custom Report Builder','📈 Analytics Dashboard','🆔 ID Card &amp; Certificate Generation',
          '🔎 Public Certificate &amp; ID Verification','🆘 In-App Help Center','📝 Homework &amp; Assignment','🗒️ Notices &amp; Events',
          '🌍 Public School Website (CMS)','🔵 Biometric (ZKTeco)','🤳 Android SMS Gateway','📲 React Native Mobile Apps',
          '🧾 Monthly Billing &amp; Invoicing','🛡️ PostgreSQL Row Level Security','🔒 PII Audit Trail (FERPA 7-yr)','🌙 Urdu / Bilingual',
          '🤖 AI Assistant (OpenRouter)','🗂️ Infix ERP Import','💳 JazzCash &amp; EasyPaisa','⚡ Evolution API WhatsApp','🏷️ Subscription Plans',
        ];
      @endphp
      @foreach($pills as $pill)
        <span class="chip">{!! $pill !!}</span>
      @endforeach
    </div>
  </div>
</section>

{{-- ─── PORTALS / FOR EVERYONE ─── --}}
<section class="section" id="portals">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">👥 One platform, every role</span>
      <h2 class="section-title">A dedicated experience for everyone</h2>
      <p class="section-sub">Admins, teachers, parents and owners — each gets the right dashboard, plus public self-service tools.</p>
    </div>
    <div class="portals">
      <div class="portal-card reveal"><div class="pic">🏫</div><h3>Admin &amp; Staff</h3><p>Principals, registrars, accountants, HR, librarians, nurses — role-based dashboards &amp; approvals.</p></div>
      <div class="portal-card reveal"><div class="pic">👨‍👩‍👧</div><h3>Parent Portal</h3><p>Track attendance, results, fees and homework; pay online and get WhatsApp updates.</p></div>
      <div class="portal-card reveal"><div class="pic">🔎</div><h3>Public Verification</h3><p>Anyone can verify a student ID or certificate authenticity via secure QR pages.</p></div>
      <div class="portal-card reveal"><div class="pic">🆘</div><h3>Help Center</h3><p>Built-in guidance and support inside every dashboard — no separate ticketing tool needed.</p></div>
    </div>
  </div>
</section>

{{-- ─── HOW IT WORKS ─── --}}
<section class="section section-alt" id="how">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">🚀 Get started</span>
      <h2 class="section-title">Up and running in minutes</h2>
      <p class="section-sub">No IT team required. Simple onboarding, instant access.</p>
    </div>
    <div class="steps">
      <div class="step reveal"><div class="step-num">1</div><h3>Register Your School</h3><p>Fill in your school details and submit the registration form.</p></div>
      <div class="step reveal"><div class="step-num">2</div><h3>Verify Your Email</h3><p>Click the verification link we send — it expires in 3 hours.</p></div>
      <div class="step reveal"><div class="step-num">3</div><h3>Set Your Password</h3><p>Create a secure password to activate your admin account.</p></div>
      <div class="step reveal"><div class="step-num">4</div><h3>Start Managing</h3><p>Log in and set up classes, students, staff and more.</p></div>
    </div>
  </div>
</section>

{{-- ─── LOGIN SELECTOR ─── --}}
<section class="section">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">🔑 Login</span>
      <h2 class="section-title">One login for every role</h2>
      <p class="section-sub">Admins, teachers and parents all use the same page — your dashboard is set by your role.</p>
    </div>
    <div class="portals">
      <a href="{{ route('school.login') }}" class="card portal-card reveal"><div class="pic">🏫</div><h3>School Admin</h3><p>Principals, administrators, registrars</p></a>
      <a href="{{ route('school.login') }}" class="card portal-card reveal"><div class="pic">👨‍🏫</div><h3>Teacher / Staff</h3><p>Teachers, HR, accountants, nurses</p></a>
      <a href="{{ route('school.login') }}" class="card portal-card reveal"><div class="pic">👨‍👩‍👧</div><h3>Parent / Guardian</h3><p>Track your child's progress</p></a>
      <a href="{{ route('school.login') }}" class="card portal-card reveal"><div class="pic">🏢</div><h3>Institute Owner</h3><p>Multi-campus &amp; multi-school owners</p></a>
    </div>
    <p class="text-center" style="margin-top:24px; font-size:14px; color:var(--muted);">
      Forgot your password? <a href="{{ route('school.forgot-password') }}" style="font-weight:600">Reset it here</a>
    </p>
  </div>
</section>

{{-- ─── FAQ ─── --}}
<style>
  .faq { max-width:820px; margin:0 auto; display:flex; flex-direction:column; gap:12px; }
  .faq-item { background:#fff; border:1px solid var(--line); border-radius:var(--r); overflow:hidden; transition:border-color .2s, box-shadow .2s; }
  .faq-item[open] { border-color:rgba(37,99,235,.35); box-shadow:var(--sh); }
  .faq-item summary { list-style:none; cursor:pointer; padding:18px 20px; font-weight:700; font-size:15.5px; color:var(--ink); display:flex; justify-content:space-between; align-items:center; gap:14px; min-height:56px; }
  .faq-item summary::-webkit-details-marker { display:none; }
  .faq-item summary::after { content:'+'; font-size:24px; line-height:1; color:var(--brand-600); font-weight:400; flex-shrink:0; }
  .faq-item[open] summary::after { content:'\2212'; }
  .faq-a { padding:0 20px 20px; color:var(--muted); font-size:14.5px; line-height:1.75; }
  .faq-a a { font-weight:600; }
</style>
<section class="section" id="faq">
  <div class="inner">
    <div class="center-head reveal">
      <span class="eyebrow">❓ FAQ</span>
      <h2 class="section-title">Frequently asked questions</h2>
      <p class="section-sub">Everything you need to know about the KynexEdu school management system.</p>
    </div>
    <div class="faq">
      <details class="faq-item reveal">
        <summary>What is a school management system?</summary>
        <div class="faq-a"><p>A school management system (also called a school ERP) is software that runs a school's day-to-day operations in one place — student admissions and records, attendance, fees and finance, exams and results, timetables, HR and payroll, library, transport and parent communication. KynexEdu is a cloud-based school management system that brings all of these together with role-based access for admins, teachers and parents.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>Is KynexEdu suitable for schools in Pakistan?</summary>
        <div class="faq-a"><p>Yes. KynexEdu is built for schools in Pakistan — it supports PKR fees with JazzCash and EasyPaisa online payments, WhatsApp and SMS notifications, Urdu/English bilingual content, biometric (ZKTeco) attendance, and works for single schools, multi-campus institutes, private and government schools, and madaris.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>Does KynexEdu support online admissions and entry tests?</summary>
        <div class="faq-a"><p>Yes. Applicants apply online through a branded admission portal, track their status, and sit a <strong>proctored online entry test</strong> with a live waiting room and anti-cheat (tab-switch and focus-loss) detection. An AI admission assistant helps with shortlisting, and every final decision goes through an approval workflow.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>Can parents and teachers use KynexEdu on mobile?</summary>
        <div class="faq-a"><p>Yes. KynexEdu works on any device through the web app and has dedicated React Native apps for iOS and Android. Parents track attendance, results, fees and homework and pay online; teachers mark attendance, enter marks and assign homework from their phones.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>Does KynexEdu send WhatsApp and SMS notifications?</summary>
        <div class="faq-a"><p>Yes. KynexEdu sends WhatsApp, SMS and in-app push notifications for fees, results, attendance and notices, and includes a WhatsApp bot where parents can text FEE, RESULT or ATTENDANCE to get instant updates.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>How much does KynexEdu cost?</summary>
        <div class="faq-a"><p>Registering your school is free and there's no credit card required to start. KynexEdu then offers trial and paid subscription plans based on your school's size and needs. <a href="{{ route('school.register') }}">Register free</a> or contact <a href="mailto:support@kynexsolutions.com">support@kynexsolutions.com</a> for current pricing.</p></div>
      </details>
      <details class="faq-item reveal">
        <summary>Can KynexEdu manage multiple campuses and biometric attendance?</summary>
        <div class="faq-a"><p>Yes. KynexEdu supports multi-campus and multi-institute ownership from one account, biometric attendance via ZKTeco devices, custom domains with SSL for each school, and enterprise security including PostgreSQL row-level security and a FERPA-style PII audit trail.</p></div>
      </details>
    </div>
  </div>
</section>

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is a school management system?",
      "acceptedAnswer": { "@type": "Answer", "text": "A school management system (also called a school ERP) is software that runs a school's day-to-day operations in one place — student admissions and records, attendance, fees and finance, exams and results, timetables, HR and payroll, library, transport and parent communication. KynexEdu is a cloud-based school management system that brings all of these together with role-based access for admins, teachers and parents." }
    },
    {
      "@type": "Question",
      "name": "Is KynexEdu suitable for schools in Pakistan?",
      "acceptedAnswer": { "@type": "Answer", "text": "Yes. KynexEdu is built for schools in Pakistan — it supports PKR fees with JazzCash and EasyPaisa online payments, WhatsApp and SMS notifications, Urdu/English bilingual content, biometric (ZKTeco) attendance, and works for single schools, multi-campus institutes, private and government schools, and madaris." }
    },
    {
      "@type": "Question",
      "name": "Does KynexEdu support online admissions and entry tests?",
      "acceptedAnswer": { "@type": "Answer", "text": "Yes. Applicants apply online through a branded admission portal, track their status, and sit a proctored online entry test with a live waiting room and anti-cheat (tab-switch and focus-loss) detection. An AI admission assistant helps with shortlisting, and every final decision goes through an approval workflow." }
    },
    {
      "@type": "Question",
      "name": "Can parents and teachers use KynexEdu on mobile?",
      "acceptedAnswer": { "@type": "Answer", "text": "Yes. KynexEdu works on any device through the web app and has dedicated React Native apps for iOS and Android. Parents track attendance, results, fees and homework and pay online; teachers mark attendance, enter marks and assign homework from their phones." }
    },
    {
      "@type": "Question",
      "name": "Does KynexEdu send WhatsApp and SMS notifications?",
      "acceptedAnswer": { "@type": "Answer", "text": "Yes. KynexEdu sends WhatsApp, SMS and in-app push notifications for fees, results, attendance and notices, and includes a WhatsApp bot where parents can text FEE, RESULT or ATTENDANCE to get instant updates." }
    },
    {
      "@type": "Question",
      "name": "How much does KynexEdu cost?",
      "acceptedAnswer": { "@type": "Answer", "text": "Registering your school is free and there's no credit card required to start. KynexEdu then offers trial and paid subscription plans based on your school's size and needs. Register free or contact support@kynexsolutions.com for current pricing." }
    },
    {
      "@type": "Question",
      "name": "Can KynexEdu manage multiple campuses and biometric attendance?",
      "acceptedAnswer": { "@type": "Answer", "text": "Yes. KynexEdu supports multi-campus and multi-institute ownership from one account, biometric attendance via ZKTeco devices, custom domains with SSL for each school, and enterprise security including PostgreSQL row-level security and a FERPA-style PII audit trail." }
    }
  ]
}
</script>

{{-- ─── CTA ─── --}}
<section class="cta section">
  <div class="inner">
    <h2>Ready to transform your school?</h2>
    <p>Join schools across Pakistan running smarter, faster, and with full control.</p>
    <div class="cta-actions">
      <a href="{{ route('school.register') }}" class="btn btn-white btn-lg">Register Free — No Credit Card</a>
      <a href="{{ route('school.login') }}" class="btn btn-ghost-white btn-lg">Login to Dashboard</a>
    </div>
  </div>
</section>

{{-- ─── FOOTER ─── --}}
<footer class="footer">
  <div class="inner">
    <div class="footer-grid">
      <div>
        <a href="{{ route('school.landing') }}" class="brand" style="color:#fff;margin-bottom:14px"><span class="brand-logo">K</span><span style="color:#fff">KynexEdu</span></a>
        <p style="font-size:14px;line-height:1.8;max-width:300px;color:#94a3b8">Pakistan's most complete cloud-based School Management ERP. Developed and maintained by Kynex Solutions.</p>
        <p style="margin-top:12px"><a href="https://kynexsolutions.com" target="_blank" rel="noopener" style="color:#60a5fa;font-weight:600">kynexsolutions.com</a></p>
      </div>
      <div>
        <h4>Platform</h4>
        <div class="footer-links">
          <a href="{{ route('school.register') }}">Register School</a>
          <a href="{{ route('school.login') }}">Login</a>
          <a href="{{ route('school.forgot-password') }}">Reset Password</a>
          <a href="https://kynexsolutions.com/docs" target="_blank" rel="noopener">Documentation</a>
        </div>
      </div>
      <div>
        <h4>Modules</h4>
        <div class="footer-links">
          <a href="#admissions">Admissions &amp; Entry Test</a>
          <a href="#modules">Fees &amp; Finance</a>
          <a href="#modules">Exams &amp; Results</a>
          <a href="#modules">HR &amp; Payroll</a>
          <a href="#capabilities">All Capabilities</a>
        </div>
      </div>
      <div>
        <h4>Company</h4>
        <div class="footer-links">
          <a href="https://kynexsolutions.com" target="_blank" rel="noopener">Kynex Solutions</a>
          <a href="https://kynexsolutions.com/contact" target="_blank" rel="noopener">Contact Us</a>
          <a href="mailto:support@kynexsolutions.com">Support</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; {{ date('Y') }} Kynex Solutions — All Rights Reserved
      &middot; <a href="https://kynexsolutions.com" target="_blank" rel="noopener">kynexsolutions.com</a>
      &middot; <a href="https://edu.kynexsolutions.com">edu.kynexsolutions.com</a>
    </div>
  </div>
</footer>

{{-- Sticky mobile CTA --}}
<div class="sticky-cta" id="stickyCta">
  <a href="{{ route('school.login') }}" class="btn btn-outline">Login</a>
  <a href="{{ route('school.register') }}" class="btn btn-primary">Register Free</a>
</div>

@include('public.partials.scripts')
<script>
  // Reveal the sticky mobile CTA after the hero scrolls away.
  (function(){
    var bar = document.getElementById('stickyCta');
    if (!bar) return;
    var onScroll = function(){ bar.classList.toggle('show', window.scrollY > 560); };
    window.addEventListener('scroll', onScroll, { passive:true }); onScroll();
  })();
</script>
</body>
</html>
