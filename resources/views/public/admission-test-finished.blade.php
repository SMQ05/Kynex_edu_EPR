<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Test Submitted — {{ $tenant->school_name ?? '' }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: 'Inter','Segoe UI',system-ui,Arial,sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(28rem 28rem at 85% -20%, rgba(6,182,212,.45), transparent 60%);"></div>
    <div class="relative max-w-2xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12 text-center">
        <div class="mx-auto mb-3 w-16 h-16 rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur flex items-center justify-center text-4xl">🎉</div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Test submitted</h1>
        <p class="text-sm sm:text-base text-white/80 mt-1.5">{{ $tenant->school_name ?? '' }}</p>
    </div>
</header>

<div class="max-w-2xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-6 sm:p-8">

        @php
            $test = $attempt->test;
            $resultsPublished = $test->show_score_to_applicant && $test->areResultsPublished() && ! $attempt->needs_manual_grading;
        @endphp

        <div class="rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-5 sm:p-6 mb-6">
            <div class="text-xs uppercase font-semibold tracking-wide text-emerald-800">Status</div>
            <div class="text-2xl font-extrabold tracking-tight text-emerald-900 mt-1">
                @if($attempt->needs_manual_grading)
                    Submitted — under review
                @elseif($resultsPublished)
                    Graded
                @else
                    Submitted
                @endif
            </div>

            @if($resultsPublished)
                <div class="text-sm mt-3 text-emerald-900">
                    <strong>Score:</strong>
                    {{ $attempt->obtained_marks }} / {{ $attempt->total_marks }}
                    @if($attempt->percentage !== null)
                        ({{ number_format((float) $attempt->percentage, 2) }}%)
                    @endif
                </div>
            @elseif($test->result_publication_mode === 'scheduled' && $test->results_published_at)
                <div class="text-sm mt-3 text-emerald-800">
                    Results will be published on
                    <strong>{{ $test->results_published_at->format('d M Y \a\t H:i') }}</strong>.
                </div>
            @else
                <div class="text-sm mt-3 text-emerald-800">
                    Your answers have been recorded. The school will publish results on your application status page.
                </div>
            @endif
        </div>

        @if($attempt->application)
            <a href="{{ route('public.apply.status', ['token' => $attempt->application->public_token]) . (tenancy()->initialized ? '?tenant=' . urlencode(tenant()->id) : '') }}"
               class="inline-flex w-full items-center justify-center min-h-[52px] rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 text-base transition-colors">
                View application status →
            </a>
        @endif
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>
</body>
</html>
