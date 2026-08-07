<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Waiting for Exam — {{ $tenant->school_name ?? 'School' }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(28rem 28rem at 85% -20%, rgba(6,182,212,.45), transparent 60%);"></div>
    <div class="relative max-w-xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12 text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-white/70 mb-2">
            {{ $tenant->school_name ?? 'School' }}
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $attempt->test?->name ?? 'Admission Test' }}</h1>
        <p class="text-sm sm:text-base text-white/80 mt-1.5">
            Welcome, <strong class="text-white">{{ $attempt->application?->full_name ?? 'Applicant' }}</strong>
        </p>
    </div>
</header>

<div class="max-w-xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-6 sm:p-8 text-center">

        {{-- Countdown --}}
        @php
            $scheduledAt = $session?->scheduled_at;
        @endphp

        <div id="waiting-block" class="space-y-6">

            {{-- Countdown timer --}}
            @if($scheduledAt)
            <div class="rounded-2xl bg-slate-900 text-white p-5">
                <div class="text-xs uppercase font-semibold tracking-wide text-white/60 mb-2">Exam starts in</div>
                <div id="countdown" class="text-4xl sm:text-5xl font-mono font-bold tracking-tight">--:--:--</div>
                <div class="text-xs text-white/50 mt-2">
                    {{ $scheduledAt->format('l, d M Y') }} at {{ $scheduledAt->format('H:i') }}
                </div>
            </div>
            @endif

            {{-- Gate status --}}
            <div id="status-card" class="rounded-2xl ring-1 ring-slate-200 bg-slate-50 p-5">
                <div id="status-icon" class="flex justify-center mb-3">
                    <svg class="w-10 h-10 text-amber-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                    </svg>
                </div>
                <p id="status-text" class="text-sm font-semibold text-slate-700">
                    Waiting for the invigilator to check you in and start the exam…
                </p>
                <p class="text-xs text-slate-400 mt-1.5">This page refreshes automatically. Do not close it.</p>
            </div>

            {{-- Check-in status --}}
            <div id="checkin-badge" class="hidden rounded-lg p-3 text-sm font-medium">
            </div>

        </div>

        {{-- Exam unlocked — redirect prompt (shown by JS) --}}
        <div id="ready-block" class="hidden space-y-4">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="text-xl font-extrabold text-emerald-700">Exam is starting!</h2>
            <p class="text-sm text-slate-500">Redirecting you to the test…</p>
        </div>

        {{-- Not checked in + exam started --}}
        <div id="absent-block" class="hidden space-y-4">
            <div class="text-5xl mb-3">⚠️</div>
            <h2 class="text-xl font-extrabold text-rose-700">Not Checked In</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                The exam has started but you have not been marked as present.<br>
                Please contact the invigilator immediately.
            </p>
            <p class="text-xs text-slate-400 mt-2">Page will keep checking — if the invigilator marks you present, it will open automatically.</p>
        </div>

    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>

<script>
(function () {
    const scheduledAt = {{ $scheduledAt ? $scheduledAt->valueOf() : 'null' }};
    const statusUrl   = "{{ route('public.admission-test.waiting.status', ['token' => $attempt->attempt_token]) }}";
    const testUrl     = "{{ route('public.admission-test.start', ['token' => $attempt->attempt_token]) }}";

    // ── Countdown ──────────────────────────────────────────────────────
    function updateCountdown() {
        if (! scheduledAt) return;
        const diff = scheduledAt - Date.now();
        const el = document.getElementById('countdown');
        if (diff <= 0) {
            el.textContent = '00:00:00';
            return;
        }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        el.textContent = [h, m, s].map(n => String(n).padStart(2, '0')).join(':');
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    // ── Polling ────────────────────────────────────────────────────────
    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.ready) {
                    // Exam started + checked in — go to test.
                    document.getElementById('waiting-block').classList.add('hidden');
                    document.getElementById('ready-block').classList.remove('hidden');
                    setTimeout(() => { window.location.href = data.redirect || testUrl; }, 1500);
                    return;
                }

                if (data.exam_started && ! data.checked_in) {
                    // Exam started but not checked in.
                    document.getElementById('waiting-block').classList.add('hidden');
                    document.getElementById('absent-block').classList.remove('hidden');
                    // Keep polling — invigilator might still check them in.
                    setTimeout(poll, 5000);
                    return;
                }

                // Update status text based on gate state.
                const badge = document.getElementById('checkin-badge');
                if (data.checked_in) {
                    badge.className = 'rounded-lg p-3 text-sm font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
                    badge.textContent = '✓ You are checked in — waiting for the exam to start.';
                    badge.classList.remove('hidden');
                }

                setTimeout(poll, 5000);
            })
            .catch(() => setTimeout(poll, 8000));
    }

    setTimeout(poll, 3000);
})();
</script>
</body>
</html>
