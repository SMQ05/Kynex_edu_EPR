<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Exam Cancelled — {{ $tenant->school_name ?? 'School' }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-900 relative overflow-hidden"
      style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.4), transparent 60%), radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%);"></div>

<div class="relative w-full max-w-md">
    <div class="bg-white rounded-3xl shadow-2xl shadow-slate-900/30 p-8 text-center">
        <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-red-50 ring-1 ring-red-200 flex items-center justify-center text-4xl">🚫</div>
        <h1 class="text-xl font-extrabold text-red-600 mb-2">Exam Cancelled</h1>
        <p class="text-slate-600 text-sm mb-4 leading-relaxed">
            Your exam was cancelled because the system detected multiple attempts to switch
            tabs or leave the exam window. The school has been notified.
        </p>
        <p class="text-slate-600 text-sm mb-6 leading-relaxed">
            Please contact <strong>{{ $tenant->school_name ?? 'your school' }}</strong> to discuss next steps.
        </p>
        <p class="text-xs text-slate-400">Cancelled at: {{ $attempt->proctoring_cancelled_at?->format('d M Y, H:i') }}</p>
    </div>

    <p class="mt-6 text-center text-xs text-white/60">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-white/80 hover:text-white">KynexEdu</a>
    </p>
</div>
</body>
</html>
