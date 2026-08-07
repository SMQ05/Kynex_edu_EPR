<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Choose your school — KynexEdu</title>
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

<div class="relative w-full max-w-md">
    <div class="bg-white rounded-3xl shadow-2xl shadow-slate-900/30 p-7 sm:p-8">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white font-black text-lg flex-shrink-0"
                 style="background: linear-gradient(135deg,#4f46e5,#2563eb 55%,#06b6d4);">K</div>
            <div>
                <div class="font-extrabold text-lg tracking-tight leading-tight">KynexEdu</div>
                <div class="text-xs text-slate-500">School selection</div>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold tracking-tight mb-1">Choose your school</h1>
        <p class="text-sm text-slate-500 mb-6">Pick the school you're applying to (or signing up as a parent for).</p>

        <div class="space-y-2.5">
            @forelse($tenants as $t)
                <a href="{{ route('public.' . $next, ['tenant' => $t->id]) }}"
                   class="group flex items-center justify-between gap-3 rounded-2xl ring-1 ring-slate-200 hover:ring-blue-300 hover:bg-blue-50/50 px-4 py-3.5 min-h-[56px] transition">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 truncate">{{ $t->school_name }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ $t->id }}</div>
                    </div>
                    <span class="text-slate-300 group-hover:text-blue-500 transition flex-shrink-0">→</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">No schools registered yet.</p>
            @endforelse
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-white/60">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-white/80 hover:text-white">KynexEdu</a>
    </p>
</div>
</body>
</html>
