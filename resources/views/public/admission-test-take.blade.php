<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<title>{{ $attempt->test->name }} — {{ $tenant->school_name ?? '' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

<!-- Proctoring violation overlay -->
<div id="violationOverlay" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full text-center">
        <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-red-50 ring-1 ring-red-200 flex items-center justify-center text-4xl">⚠️</div>
        <h2 class="text-xl font-extrabold text-red-600 mb-2">Proctoring Warning</h2>
        <p id="violationMessage" class="text-slate-600 text-sm mb-4 leading-relaxed">
            You left the exam window. This is recorded as a violation.
        </p>
        <p id="violationCount" class="text-xs font-semibold text-slate-500 mb-5"></p>
        <button id="resumeBtn"
            class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 min-h-[48px]">
            Resume Exam
        </button>
    </div>
</div>

<!-- Exam cancelled overlay (shown after threshold reached) -->
<div id="cancelledOverlay" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full text-center">
        <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-red-50 ring-1 ring-red-200 flex items-center justify-center text-4xl">🚫</div>
        <h2 class="text-xl font-extrabold text-red-600 mb-2">Exam Cancelled</h2>
        <p class="text-slate-600 text-sm mb-4 leading-relaxed">
            Your exam has been automatically cancelled after repeated proctoring violations.
            The school has been notified. Please contact your school.
        </p>
        <p class="text-xs text-slate-400">You may close this window.</p>
    </div>
</div>

<div class="max-w-3xl mx-auto py-6 sm:py-8 px-4">
    <div class="bg-white rounded-2xl shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 p-4 sm:p-5 mb-4 flex items-center justify-between gap-3 sticky top-2 z-10">
        <div class="min-w-0">
            <div class="text-[11px] uppercase font-semibold tracking-wide text-slate-500 truncate">{{ $tenant->school_name ?? '' }}</div>
            <div class="text-base sm:text-lg font-extrabold tracking-tight truncate">{{ $attempt->test->name }}</div>
        </div>
        <div class="text-right flex-shrink-0">
            <div class="text-[11px] uppercase font-semibold tracking-wide text-slate-500">Time remaining</div>
            <div id="countdown" class="text-2xl sm:text-3xl font-mono font-bold text-emerald-600">--:--</div>
        </div>
    </div>

    <!-- Proctoring notice bar -->
    <div class="rounded-xl bg-amber-50 ring-1 ring-amber-200 px-4 py-2.5 mb-4 text-xs text-amber-800 flex items-start gap-2 leading-relaxed">
        <span class="text-sm">🔒</span>
        <span>This exam is proctored. Do not switch tabs, minimize, or open other windows. Violations are recorded and may cancel your exam.</span>
    </div>

    {{-- Question navigator pills (jump-to-question) --}}
    <div class="bg-white rounded-2xl shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs uppercase text-slate-500 font-semibold tracking-wide">Questions</div>
            <div class="text-xs font-medium text-slate-500"><span id="answeredCount" class="font-bold text-slate-700">0</span> / {{ $questions->count() }} answered</div>
        </div>
        <div id="qNav" class="flex flex-wrap gap-1.5">
            @foreach($questions as $i => $q)
                <button type="button"
                        data-jump="{{ $i }}"
                        class="qpill w-10 h-10 rounded-xl ring-1 ring-slate-200 text-xs font-mono font-bold text-slate-600 hover:bg-slate-50 transition">
                    {{ $i + 1 }}
                </button>
            @endforeach
        </div>
    </div>

    <form id="testForm" method="POST" action="{{ route('public.admission-test.submit', ['token' => $attempt->attempt_token]) }}">
        @csrf

        {{-- Each question is a hideable card. Only the active one shows. --}}
        @foreach($questions as $i => $q)
            <div data-question-card="{{ $i }}" data-question-id="{{ $q->id }}" class="question-card bg-white rounded-2xl shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 p-5 sm:p-6 mb-4 {{ $i === 0 ? '' : 'hidden' }}">
                <div class="flex items-baseline justify-between mb-3">
                    <div class="text-xs uppercase font-semibold tracking-wide text-slate-500">Question {{ $i + 1 }} of {{ $questions->count() }}</div>
                    <div class="text-xs font-semibold text-blue-600 bg-blue-50 ring-1 ring-blue-100 rounded-full px-2.5 py-0.5">{{ $q->marks }} marks</div>
                </div>
                <div class="font-semibold text-base mb-5 whitespace-pre-line leading-relaxed">{{ $q->question_text }}</div>

                @if($q->type === 'mcq' && is_array($q->options))
                    <div class="space-y-2.5">
                        @foreach($q->options as $key => $label)
                            <label class="flex items-start gap-3 p-3.5 rounded-xl ring-1 ring-slate-200 cursor-pointer hover:bg-slate-50 has-[:checked]:ring-2 has-[:checked]:ring-blue-500 has-[:checked]:bg-blue-50 transition min-h-[48px]">
                                <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" class="mt-1 q-input w-4 h-4 accent-blue-600">
                                <span class="text-sm leading-relaxed"><span class="font-mono text-xs font-bold text-slate-500">{{ $key }}</span> — {{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif($q->type === 'true_false')
                    <div class="space-y-2.5">
                        @foreach(['true' => 'True', 'false' => 'False'] as $key => $label)
                            <label class="flex items-center gap-3 p-3.5 rounded-xl ring-1 ring-slate-200 cursor-pointer hover:bg-slate-50 has-[:checked]:ring-2 has-[:checked]:ring-blue-500 has-[:checked]:bg-blue-50 transition min-h-[48px]">
                                <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" class="q-input w-4 h-4 accent-blue-600">
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif($q->type === 'short_answer')
                    <input type="text" name="answers[{{ $q->id }}]" class="q-input w-full rounded-xl ring-1 ring-slate-300 px-4 py-3 text-base min-h-[48px] focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your answer">
                @else
                    <textarea name="answers[{{ $q->id }}]" rows="6" class="q-input w-full rounded-xl ring-1 ring-slate-300 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Write your answer..."></textarea>
                @endif
            </div>
        @endforeach

        {{-- Navigation controls --}}
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-900/10 ring-1 ring-slate-100 p-3.5 sm:p-4 sticky bottom-2 z-10 flex items-center justify-between gap-2 sm:gap-3"
             style="margin-bottom: env(safe-area-inset-bottom);">
            <button type="button" id="prevBtn"
                    class="px-4 sm:px-5 py-2.5 min-h-[44px] rounded-xl ring-1 ring-slate-300 text-slate-700 font-semibold text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                ← Prev
            </button>

            <div class="text-xs font-medium text-slate-500 text-center">
                Question <span id="currentIdx" class="font-bold text-slate-700">1</span> of {{ $questions->count() }}
            </div>

            <button type="button" id="nextBtn"
                    class="px-4 sm:px-5 py-2.5 min-h-[44px] rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm">
                Next →
            </button>

            <button type="submit" id="submitBtn"
                    class="hidden px-5 sm:px-6 py-2.5 min-h-[44px] rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm">
                Submit Test
            </button>
        </div>

        <p class="mt-3 text-xs text-slate-500 text-center">
            Use the number pills above to jump to any question. Submit only available on the last question.
        </p>
    </form>
</div>

<script>
(function () {
    // ── Countdown timer ──────────────────────────────────────────────
    const expiresAt = @json(optional($attempt->expires_at)?->toIso8601String());
    if (expiresAt) {
        const expiry = new Date(expiresAt).getTime();
        const el = document.getElementById('countdown');
        const form = document.getElementById('testForm');
        let submitted = false;

        function tick() {
            const now = Date.now();
            const diff = Math.max(0, Math.floor((expiry - now) / 1000));
            const m = String(Math.floor(diff / 60)).padStart(2, '0');
            const s = String(diff % 60).padStart(2, '0');
            el.textContent = `${m}:${s}`;
            if (diff <= 60) el.classList.replace('text-emerald-600', 'text-red-600');
            if (diff === 0 && !submitted) { submitted = true; form.submit(); }
        }
        tick();
        setInterval(tick, 1000);
        form.addEventListener('submit', () => { submitted = true; });
    }

    // ── Fullscreen request ───────────────────────────────────────────
    const body = document.documentElement;
    function requestFullscreen() {
        try {
            if (body.requestFullscreen) body.requestFullscreen();
            else if (body.webkitRequestFullscreen) body.webkitRequestFullscreen();
        } catch (e) {}
    }
    // Request on first user interaction (browser policy requires gesture).
    document.getElementById('testForm').addEventListener('click', function handler() {
        requestFullscreen();
        document.getElementById('testForm').removeEventListener('click', handler);
    }, { once: true });

    // ── Proctoring ───────────────────────────────────────────────────
    const violationUrl   = @json(route('public.admission-test.violation', ['token' => $attempt->attempt_token]));
    const csrfToken      = document.querySelector('meta[name="csrf-token"]').content;
    const overlay        = document.getElementById('violationOverlay');
    const cancelledOverlay = document.getElementById('cancelledOverlay');
    const violationMsg   = document.getElementById('violationMessage');
    const violationCountEl = document.getElementById('violationCount');
    const resumeBtn      = document.getElementById('resumeBtn');
    const THRESHOLD      = 3;

    let violationInFlight = false;
    let examCancelled = false;
    let suppressNextBlur = false; // prevent double-reporting on fullscreen exit

    function reportViolation(type) {
        if (violationInFlight || examCancelled) return;
        violationInFlight = true;

        fetch(violationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ type }),
        })
        .then(r => r.json())
        .then(data => {
            violationInFlight = false;

            if (data.cancelled) {
                examCancelled = true;
                overlay.classList.add('hidden');
                cancelledOverlay.classList.remove('hidden');
                return;
            }

            const remaining = data.remaining ?? (THRESHOLD - data.violations);
            violationMsg.textContent = type === 'tab_switch'
                ? 'You switched tabs or minimised the window. This is a proctoring violation.'
                : 'The exam window lost focus. This is a proctoring violation.';
            violationCountEl.textContent = `Violation ${data.violations} of ${THRESHOLD}. ${remaining} more will cancel the exam.`;
            overlay.classList.remove('hidden');
            requestFullscreen();
        })
        .catch(() => { violationInFlight = false; });
    }

    // Tab/visibility change (covers switching tabs, minimising, Alt+Tab)
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && !examCancelled) {
            reportViolation('tab_switch');
        }
    });

    // Window blur (covers opening DevTools, clicking outside on desktop)
    window.addEventListener('blur', function () {
        if (suppressNextBlur) { suppressNextBlur = false; return; }
        if (!examCancelled) {
            reportViolation('window_blur');
        }
    });

    // Fullscreen exit also fires blur — suppress the duplicate
    document.addEventListener('fullscreenchange', function () {
        if (!document.fullscreenElement) {
            suppressNextBlur = true;
        }
    });

    // Dismiss overlay — re-request fullscreen
    resumeBtn.addEventListener('click', function () {
        overlay.classList.add('hidden');
        requestFullscreen();
    });

    // Block right-click
    document.addEventListener('contextmenu', e => e.preventDefault());

    // Block common keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        const blocked = (
            e.key === 'F12' ||
            (e.ctrlKey && ['u', 'U', 's', 'S', 'p', 'P', 'a', 'A'].includes(e.key)) ||
            (e.ctrlKey && e.shiftKey && ['i', 'I', 'j', 'J', 'c', 'C'].includes(e.key)) ||
            (e.altKey && ['Tab', 'F4'].includes(e.key))
        );
        if (blocked) e.preventDefault();
    });

    // ── One-question-at-a-time navigation ───────────────────────────
    const cards    = Array.from(document.querySelectorAll('[data-question-card]'));
    const pills    = Array.from(document.querySelectorAll('.qpill'));
    const prevBtn  = document.getElementById('prevBtn');
    const nextBtn  = document.getElementById('nextBtn');
    const submitBtn= document.getElementById('submitBtn');
    const idxLabel = document.getElementById('currentIdx');
    const answeredCountEl = document.getElementById('answeredCount');
    let current = 0;
    const total = cards.length;

    function isAnswered(card) {
        // Radio: any radio with this name has :checked
        const radios = card.querySelectorAll('input[type="radio"]');
        if (radios.length) {
            return Array.from(radios).some(r => r.checked);
        }
        // Text/textarea: non-empty trimmed value
        const text = card.querySelector('input[type="text"], textarea');
        if (text) {
            return (text.value || '').trim().length > 0;
        }
        return false;
    }

    function refreshAnsweredState() {
        let answered = 0;
        cards.forEach((card, i) => {
            const ok = isAnswered(card);
            if (ok) answered++;
            const pill = pills[i];
            if (!pill) return;
            pill.classList.remove('bg-emerald-100','ring-emerald-400','text-emerald-700','bg-blue-600','ring-blue-600','text-white');
            if (i === current) {
                pill.classList.add('bg-blue-600','ring-blue-600','text-white');
            } else if (ok) {
                pill.classList.add('bg-emerald-100','ring-emerald-400','text-emerald-700');
            }
        });
        answeredCountEl.textContent = answered;
    }

    function showQuestion(index) {
        if (index < 0) index = 0;
        if (index >= total) index = total - 1;
        cards.forEach((c, i) => c.classList.toggle('hidden', i !== index));
        current = index;
        idxLabel.textContent = String(index + 1);

        prevBtn.disabled = (index === 0);

        const onLast = (index === total - 1);
        nextBtn.classList.toggle('hidden', onLast);
        submitBtn.classList.toggle('hidden', !onLast);

        refreshAnsweredState();
        // Scroll to top of card so it's in view
        cards[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    prevBtn.addEventListener('click', () => showQuestion(current - 1));
    nextBtn.addEventListener('click', () => showQuestion(current + 1));

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const target = parseInt(pill.dataset.jump, 10);
            if (!Number.isNaN(target)) showQuestion(target);
        });
    });

    // Update answered indicators as the student types or selects
    document.querySelectorAll('.q-input').forEach(input => {
        input.addEventListener('change', refreshAnsweredState);
        input.addEventListener('input',  refreshAnsweredState);
    });

    // Auto-submit on timer expiry already covered above; ensure the form
    // collects answers from all hidden cards (it does — form serializes
    // every named input regardless of visibility).
    showQuestion(0);
})();
</script>
</body>
</html>
