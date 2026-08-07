<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Activation Link Sent — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-900 relative overflow-hidden"
      style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.45), transparent 60%), radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%);"></div>

    <div class="relative max-w-md w-full bg-white rounded-3xl shadow-2xl shadow-slate-900/30 p-8 text-center">
        <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 flex items-center justify-center text-4xl">✉️</div>
        <h1 class="text-2xl font-extrabold tracking-tight mb-2">Activation link sent</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            Check your inbox at <strong class="text-slate-900">{{ $email }}</strong>. The link will let you set a password and access the parent portal at
            <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">/parent</code>.
        </p>
        <p class="text-xs text-slate-500 mt-5">The link expires in 7 days. If you don't see the email, check your spam folder.</p>

        <p class="mt-7 text-xs text-slate-400">
            Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
        </p>
    </div>
</body>
</html>
