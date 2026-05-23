{{--
  KynexEdu — Shared public design system (vanilla CSS, no build step).
  Included inside <head> of standalone public/portal pages so the
  marketing landing, auth pages and verification pages all share one
  bold, mobile-first visual language. Pair with partials/scripts.blade.php.
--}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; }

  :root {
    /* Brand */
    --brand-50:#eff6ff; --brand-100:#dbeafe; --brand-500:#3b82f6;
    --brand-600:#2563eb; --brand-700:#1d4ed8; --brand-800:#1e3a8a;
    --indigo:#4f46e5; --cyan:#06b6d4; --violet:#7c3aed;
    /* Ink + surfaces */
    --ink:#0f172a; --ink-2:#1e293b; --muted:#64748b; --muted-2:#94a3b8;
    --line:#e2e8f0; --bg:#f8fafc; --bg-2:#f1f5f9; --white:#ffffff;
    /* State */
    --success:#059669; --error:#dc2626; --warning:#d97706;
    /* Gradients */
    --grad-hero:linear-gradient(135deg,#1e1b4b 0%,#1e3a8a 42%,#2563eb 78%,#0ea5e9 100%);
    --grad-brand:linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%);
    --grad-soft:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);
    /* Radius */
    --r-sm:10px; --r:16px; --r-lg:22px; --r-xl:28px; --r-pill:999px;
    /* Shadow */
    --sh-sm:0 1px 3px rgba(15,23,42,.06),0 1px 2px rgba(15,23,42,.04);
    --sh:0 6px 24px rgba(15,23,42,.08);
    --sh-lg:0 20px 50px -12px rgba(15,23,42,.22);
    --sh-brand:0 16px 40px -12px rgba(37,99,235,.45);
  }

  html { scroll-behavior:smooth; -webkit-text-size-adjust:100%; }
  body {
    margin:0; font-family:'Inter','Segoe UI',system-ui,Arial,sans-serif;
    color:var(--ink); background:var(--white);
    font-size:16px; line-height:1.65; -webkit-font-smoothing:antialiased;
    overflow-x:hidden;
  }
  a { color:var(--brand-600); text-decoration:none; }
  a:hover { text-decoration:none; }
  img { max-width:100%; display:block; }
  ::selection { background:var(--brand-100); color:var(--brand-800); }

  /* ── Layout ── */
  .inner { width:100%; max-width:1180px; margin:0 auto; padding:0 20px; }
  .section { padding:64px 0; }
  @media(min-width:768px){ .section { padding:96px 0; } }
  .section-alt { background:var(--bg); }
  .section-dark { background:#0b1120; color:#cbd5e1; }
  .eyebrow {
    display:inline-flex; align-items:center; gap:7px;
    font-size:12.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
    color:var(--brand-700); background:var(--brand-50);
    border:1px solid var(--brand-100); border-radius:var(--r-pill); padding:6px 14px;
  }
  .section-title {
    font-size:clamp(26px,5vw,40px); font-weight:800; letter-spacing:-.02em;
    line-height:1.15; margin:16px 0 12px; color:var(--ink);
  }
  .section-sub {
    font-size:clamp(15px,2.4vw,18px); color:var(--muted); line-height:1.7;
    max-width:640px; margin:0;
  }
  .text-center { text-align:center; }
  .center-head { display:flex; flex-direction:column; align-items:center; text-align:center; margin-bottom:44px; }
  .center-head .section-sub { margin:0 auto; }
  .grad-text {
    background:var(--grad-brand); -webkit-background-clip:text; background-clip:text;
    -webkit-text-fill-color:transparent; color:transparent;
  }

  /* ── Buttons (44px+ tap targets) ── */
  .btn {
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:13px 24px; min-height:48px; border-radius:var(--r-sm);
    font-size:15px; font-weight:600; line-height:1; cursor:pointer;
    border:1.5px solid transparent; transition:transform .15s ease, box-shadow .2s ease, background .2s ease;
    text-decoration:none; white-space:nowrap;
  }
  .btn:active { transform:translateY(1px); }
  .btn-primary { background:var(--grad-brand); color:#fff; box-shadow:var(--sh-brand); }
  .btn-primary:hover { color:#fff; box-shadow:0 20px 48px -12px rgba(37,99,235,.6); transform:translateY(-2px); }
  .btn-outline { background:#fff; color:var(--brand-700); border-color:var(--line); }
  .btn-outline:hover { border-color:var(--brand-600); background:var(--brand-50); }
  .btn-white { background:#fff; color:var(--brand-700); }
  .btn-white:hover { background:var(--brand-50); color:var(--brand-700); transform:translateY(-2px); }
  .btn-ghost-white { background:rgba(255,255,255,.1); color:#fff; border-color:rgba(255,255,255,.35); backdrop-filter:blur(6px); }
  .btn-ghost-white:hover { background:rgba(255,255,255,.2); color:#fff; }
  .btn-lg { padding:16px 32px; min-height:54px; font-size:16px; border-radius:14px; }
  .btn-sm { padding:9px 16px; min-height:40px; font-size:13.5px; }
  .btn-block { width:100%; }

  /* ── Pills / badges ── */
  .chip {
    display:inline-flex; align-items:center; gap:8px;
    background:#fff; border:1px solid var(--line); border-radius:var(--r-pill);
    padding:10px 16px; font-size:13.5px; font-weight:600; color:var(--ink-2);
    transition:transform .15s ease, border-color .15s ease, box-shadow .2s ease;
  }
  .chip:hover { border-color:var(--brand-300,#bfdbfe); color:var(--brand-700); box-shadow:var(--sh-sm); transform:translateY(-2px); }
  .tag { display:inline-flex; align-items:center; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; }
  .tag-core { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
  .tag-secure { background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; }
  .tag-new { background:var(--grad-brand); color:#fff; }

  /* ── Glass / surface cards ── */
  .card {
    background:#fff; border:1px solid var(--line); border-radius:var(--r-lg);
    padding:26px 24px; transition:transform .22s ease, box-shadow .25s ease, border-color .2s ease;
  }
  .card:hover { transform:translateY(-4px); box-shadow:var(--sh-lg); border-color:rgba(37,99,235,.35); }
  .glass {
    background:rgba(255,255,255,.7); backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px); border:1px solid rgba(255,255,255,.6);
  }

  /* ── Navbar + mobile drawer ── */
  .nav {
    position:sticky; top:0; z-index:100;
    background:rgba(255,255,255,.82); backdrop-filter:saturate(160%) blur(14px);
    -webkit-backdrop-filter:saturate(160%) blur(14px);
    border-bottom:1px solid var(--line);
  }
  .nav-inner { height:64px; display:flex; align-items:center; justify-content:space-between; }
  .brand { display:flex; align-items:center; gap:11px; font-weight:800; font-size:19px; color:var(--ink); }
  .brand:hover { color:var(--ink); }
  .brand-logo {
    width:40px; height:40px; flex-shrink:0; border-radius:11px; color:#fff;
    background:var(--grad-brand); display:flex; align-items:center; justify-content:center;
    font-weight:900; font-size:18px; box-shadow:var(--sh-brand);
  }
  .brand-sub { display:block; font-size:10.5px; font-weight:500; color:var(--muted); line-height:1; margin-top:2px; }
  .nav-links { display:none; align-items:center; gap:6px; }
  .nav-link { padding:9px 14px; border-radius:10px; font-size:14.5px; font-weight:600; color:var(--ink-2); transition:background .15s,color .15s; }
  .nav-link:hover { background:var(--bg-2); color:var(--brand-700); }
  .nav-cta { display:none; align-items:center; gap:10px; }
  .nav-toggle {
    display:inline-flex; align-items:center; justify-content:center;
    width:46px; height:46px; border:1px solid var(--line); border-radius:12px;
    background:#fff; cursor:pointer; color:var(--ink);
  }
  .nav-toggle svg { width:22px; height:22px; }
  @media(min-width:900px){
    .nav-links { display:flex; }
    .nav-cta { display:flex; }
    .nav-toggle { display:none; }
  }

  .drawer-backdrop {
    position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(2px);
    opacity:0; visibility:hidden; transition:opacity .25s ease, visibility .25s ease; z-index:200;
  }
  .drawer {
    position:fixed; top:0; right:0; height:100dvh; width:min(86vw,360px);
    background:#fff; z-index:210; padding:22px;
    transform:translateX(100%); transition:transform .32s cubic-bezier(.16,1,.3,1);
    display:flex; flex-direction:column; gap:6px; box-shadow:var(--sh-lg); overflow-y:auto;
  }
  body.nav-open .drawer { transform:translateX(0); }
  body.nav-open .drawer-backdrop { opacity:1; visibility:visible; }
  body.nav-open { overflow:hidden; }
  .drawer-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
  .drawer-close { width:44px; height:44px; border:1px solid var(--line); border-radius:12px; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; }
  .drawer-link { padding:14px 12px; border-radius:12px; font-size:16px; font-weight:600; color:var(--ink-2); border-bottom:1px solid var(--bg-2); }
  .drawer-link:hover { background:var(--bg); color:var(--brand-700); }
  .drawer-actions { margin-top:auto; display:flex; flex-direction:column; gap:10px; padding-top:18px; }

  /* ── Footer ── */
  .footer { background:#0b1120; color:#94a3b8; padding:56px 0 28px; }
  .footer a { color:#94a3b8; }
  .footer a:hover { color:#fff; }
  .footer-grid { display:grid; grid-template-columns:1fr; gap:36px; margin-bottom:40px; }
  @media(min-width:640px){ .footer-grid { grid-template-columns:1.6fr 1fr 1fr; } }
  @media(min-width:900px){ .footer-grid { grid-template-columns:2.2fr 1fr 1fr 1fr; } }
  .footer h4 { color:#e2e8f0; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; margin:0 0 16px; }
  .footer-links a { display:block; font-size:14px; margin-bottom:11px; }
  .footer-bottom { border-top:1px solid #1e293b; padding-top:22px; text-align:center; font-size:13px; color:#64748b; }

  /* ── Scroll reveal ── */
  .reveal { opacity:0; transform:translateY(26px); transition:opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
  .reveal.in { opacity:1; transform:none; }
  @media (prefers-reduced-motion: reduce){
    html { scroll-behavior:auto; }
    .reveal { opacity:1; transform:none; transition:none; }
    .btn, .card, .chip { transition:none; }
    *{ animation:none !important; }
  }
</style>
