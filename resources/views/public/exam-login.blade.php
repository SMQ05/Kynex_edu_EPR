<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Exam Login — {{ $tenant->school_name ?? 'School' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
    .ex-input {
        width: 100%; border-radius: 0.75rem; min-height: 48px;
        background: #fff; padding: 0.75rem 1rem; font-size: 16px; color: #0f172a;
        box-shadow: inset 0 0 0 1px #cbd5e1; outline: none; transition: box-shadow .15s ease;
    }
    .ex-input:focus { box-shadow: inset 0 0 0 2px #2563eb, 0 0 0 4px rgba(37,99,235,.12); }
    .ex-input.ring-red-400 { box-shadow: inset 0 0 0 2px #f87171; }
    .btn-grad { background: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%); box-shadow: 0 16px 40px -12px rgba(37,99,235,.45); transition: transform .15s, box-shadow .2s, filter .2s; }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 20px 48px -12px rgba(37,99,235,.6); filter: brightness(1.04); }
    @media (prefers-reduced-motion: reduce) { .btn-grad { transition: none; } }
</style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-900 relative overflow-hidden"
      style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(34rem 34rem at 80% -10%, rgba(6,182,212,.45), transparent 60%), radial-gradient(28rem 28rem at 0% 110%, rgba(124,58,237,.4), transparent 55%);"></div>

<div class="relative w-full max-w-sm">
    <div class="bg-white rounded-3xl shadow-2xl shadow-slate-900/30 p-7 sm:p-8">
        <div class="mx-auto mb-5 w-14 h-14 rounded-2xl flex items-center justify-center text-white font-black text-2xl"
             style="background: linear-gradient(135deg,#4f46e5,#2563eb 55%,#06b6d4);">
            {{ $tenant ? mb_substr($tenant->school_name, 0, 1) : '🎯' }}
        </div>

        @if($tenant)
            <div class="text-center text-xs uppercase tracking-wide text-slate-500 mb-1 font-semibold">{{ $tenant->school_name }}</div>
        @endif
        <h1 class="text-center text-2xl font-extrabold tracking-tight mb-1">Admission Test Login</h1>
        <p class="text-center text-sm text-slate-500 mb-6">Enter the credentials sent to you by the school.</p>

        @if ($errors->any())
            <div class="rounded-2xl bg-red-50 ring-1 ring-red-200 text-red-700 text-sm px-4 py-3 mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('public.exam-login.submit') }}">
            @csrf
            @if(! empty($tenantId))
                <input type="hidden" name="tenant" value="{{ $tenantId }}">
            @endif
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="username">Username</label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        class="ex-input @error('username') ring-red-400 @enderror"
                        placeholder="e.g. ahmad.XY12"
                        required
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        class="ex-input"
                        placeholder="••••••••"
                        required
                    >
                </div>
            </div>
            <button
                type="submit"
                class="btn-grad w-full mt-6 rounded-xl text-white font-semibold py-3 min-h-[52px] text-base"
            >
                Enter Exam →
            </button>
        </form>

        <p class="mt-6 text-xs text-slate-400 text-center leading-relaxed">
            🔒 Credentials are provided by your school on exam day. Do not share them.
        </p>
    </div>

    <p class="mt-6 text-center text-xs text-white/60">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-white/80 hover:text-white">KynexEdu</a>
    </p>
</div>
</body>
</html>
