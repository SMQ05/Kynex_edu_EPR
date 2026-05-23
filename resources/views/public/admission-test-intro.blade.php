<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Online Admission Test — {{ $tenant->school_name ?? 'School' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
    .btn-grad { background: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%); box-shadow: 0 16px 40px -12px rgba(37,99,235,.45); transition: transform .15s, box-shadow .2s, filter .2s; }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 20px 48px -12px rgba(37,99,235,.6); filter: brightness(1.04); }
    @media (prefers-reduced-motion: reduce) { .btn-grad { transition: none; } }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(28rem 28rem at 85% -20%, rgba(6,182,212,.45), transparent 60%);"></div>
    <div class="relative max-w-2xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-white/70">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/12 ring-1 ring-white/25 px-3 py-1">🎯 Proctored Entry Test</span>
        </div>
        <h1 class="mt-4 text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $attempt->test->name }}</h1>
        <p class="mt-1.5 text-sm sm:text-base text-white/80">{{ $tenant->school_name ?? '' }}</p>
    </div>
</header>

<div class="max-w-2xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-6 sm:p-8">

        <div class="rounded-2xl ring-1 ring-slate-200 bg-slate-50 p-5 mb-6">
            <div class="text-xs uppercase font-semibold tracking-wide text-slate-500">Applicant</div>
            <div class="text-xl font-extrabold tracking-tight mt-1">{{ $attempt->application->full_name ?? '—' }}</div>
        </div>

        <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-5 text-center">
            <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-4">
                <div class="text-[11px] uppercase font-semibold text-slate-500">Duration</div>
                <div class="text-lg sm:text-xl font-extrabold mt-1">{{ $attempt->test->duration_minutes }} min</div>
            </div>
            <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-4">
                <div class="text-[11px] uppercase font-semibold text-slate-500">Total marks</div>
                <div class="text-lg sm:text-xl font-extrabold mt-1">{{ $attempt->test->total_marks }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 ring-1 ring-slate-200 p-4">
                <div class="text-[11px] uppercase font-semibold text-slate-500">Pass marks</div>
                <div class="text-lg sm:text-xl font-extrabold mt-1">{{ $attempt->test->passing_marks }}</div>
            </div>
        </div>

        @if($attempt->test->scheduled_date || $attempt->test->window_opens_at)
            <div class="rounded-2xl bg-blue-50 ring-1 ring-blue-200 p-4 mb-4 text-sm text-blue-800 space-y-0.5">
                @if($attempt->test->scheduled_date)
                    <div><strong>Exam Date:</strong> {{ $attempt->test->scheduled_date->format('l, d M Y') }}</div>
                @endif
                @if($attempt->test->window_opens_at)
                    <div><strong>Window Opens:</strong> {{ $attempt->test->window_opens_at->format('H:i') }}</div>
                @endif
                @if($attempt->test->window_closes_at)
                    <div><strong>Window Closes:</strong> {{ $attempt->test->window_closes_at->format('H:i') }}</div>
                @endif
            </div>
        @endif

        @if($attempt->test->instructions)
            <div class="rounded-2xl bg-amber-50 ring-1 ring-amber-200 p-5 mb-6 whitespace-pre-line text-sm text-amber-900 leading-relaxed">
                <strong class="block mb-1.5 uppercase text-xs tracking-wide text-amber-800">Instructions</strong>
                {{ $attempt->test->instructions }}
            </div>
        @endif

        <div class="rounded-2xl bg-slate-900 text-slate-200 p-4 mb-6 text-xs flex items-start gap-2.5 leading-relaxed">
            <span class="text-base">🔒</span>
            <span>This is a proctored exam. Do not switch tabs, minimise the window, or open other apps once you start. Violations are recorded and may cancel your attempt.</span>
        </div>

        <form method="POST" action="{{ route('public.admission-test.begin', ['token' => $attempt->attempt_token]) }}">
            @csrf
            <button class="btn-grad w-full rounded-xl text-white font-semibold py-3.5 min-h-[52px] text-base">
                Start Test →
            </button>
        </form>

        <p class="mt-6 text-xs text-slate-500 text-center">
            Once you click Start, the timer begins. You cannot pause the test.
        </p>
    </div>
</div>
</body>
</html>
