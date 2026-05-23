<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Parent Sign-up — {{ $tenant->school_name }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root {
        --grad-brand: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%);
        --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
    }
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
    .pr-input {
        width: 100%; border-radius: 0.75rem; min-height: 48px;
        background: #fff; padding: 0.75rem 1rem; font-size: 16px; color: #0f172a;
        box-shadow: inset 0 0 0 1px #cbd5e1; outline: none; transition: box-shadow .15s ease;
    }
    .pr-input:focus { box-shadow: inset 0 0 0 2px #2563eb, 0 0 0 4px rgba(37,99,235,.12); }
    .btn-grad { background: var(--grad-brand); box-shadow: 0 16px 40px -12px rgba(37,99,235,.45); transition: transform .15s, box-shadow .2s, filter .2s; }
    .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 20px 48px -12px rgba(37,99,235,.6); filter: brightness(1.04); }
    @media (prefers-reduced-motion: reduce) { .btn-grad { transition: none; } }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(28rem 28rem at 85% -20%, rgba(6,182,212,.45), transparent 60%);"></div>
    <div class="relative max-w-md mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur flex items-center justify-center text-white font-black text-xl flex-shrink-0">
                {{ mb_substr($tenant->school_name, 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="font-extrabold text-lg sm:text-xl leading-tight truncate">{{ $tenant->school_name }}</div>
                <div class="text-xs sm:text-sm text-white/70">Parent Portal</div>
            </div>
        </div>
        <h1 class="mt-5 text-2xl sm:text-3xl font-extrabold tracking-tight">Parent Sign-up</h1>
        <p class="mt-1.5 text-sm sm:text-base text-white/80">Create your parent account to track your child's progress.</p>
    </div>
</header>

<div class="max-w-md mx-auto px-4 -mt-5 sm:-mt-6 pb-12">
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-6 sm:p-8">

        @if($errors->any())
            <div class="mb-5 p-4 rounded-2xl bg-red-50 ring-1 ring-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <p class="text-sm text-slate-500 mb-5 leading-relaxed">
            To sign up, enter your child's admission or registration number and the email you provided as their guardian.
        </p>

        <form method="POST" action="{{ route('public.parent.register.submit') }}" class="space-y-5">
            @csrf
            <div>
                <label for="student_reference" class="block text-sm font-semibold text-slate-700 mb-1.5">Student admission or registration number</label>
                <input id="student_reference" name="student_reference" required value="{{ old('student_reference') }}" placeholder="e.g. ADM-2025-001" class="pr-input">
            </div>
            <div>
                <label for="guardian_email" class="block text-sm font-semibold text-slate-700 mb-1.5">Your email <span class="text-slate-400 font-normal">(must match guardian record)</span></label>
                <input id="guardian_email" type="email" name="guardian_email" required value="{{ old('guardian_email') }}" placeholder="guardian@example.com" class="pr-input">
            </div>
            <button type="submit" class="btn-grad w-full rounded-xl text-white font-semibold py-3.5 min-h-[52px] text-base">
                Send Activation Link
            </button>
        </form>

        <p class="mt-5 text-sm text-slate-500 text-center">
            Already have an account? <a href="{{ url('/parent/login') }}" class="font-semibold text-blue-600 hover:underline">Login here</a>.
        </p>
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>
</body>
</html>
