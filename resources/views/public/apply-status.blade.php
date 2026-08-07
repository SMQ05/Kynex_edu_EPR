<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1e1b4b">
<title>Application Status — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --grad-hero: linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9); }
    body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; -webkit-font-smoothing: antialiased; }
</style>
</head>
<body class="min-h-screen text-slate-900 bg-slate-50">

{{-- ══ Branded header band ════════════════════════════════════════ --}}
<header class="relative overflow-hidden text-white" style="background: var(--grad-hero);">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60"
         style="background: radial-gradient(30rem 30rem at 85% -20%, rgba(6,182,212,.45), transparent 60%);"></div>
    <div class="relative max-w-2xl mx-auto px-4 pt-7 pb-9 sm:pt-9 sm:pb-12">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur flex items-center justify-center text-white font-black text-xl flex-shrink-0">
                {{ mb_substr($tenant->school_name, 0, 1) }}
            </div>
            <div class="min-w-0">
                <div class="font-extrabold text-lg sm:text-xl leading-tight truncate">{{ $tenant->school_name }}</div>
                <div class="text-xs sm:text-sm text-white/70">Online Admissions</div>
            </div>
        </div>
        <h1 class="mt-5 text-2xl sm:text-3xl font-extrabold tracking-tight">Application Status</h1>
        <p class="mt-1.5 text-sm sm:text-base text-white/80">Track the progress of your admission application.</p>
    </div>
</header>

<div class="max-w-2xl mx-auto px-4 -mt-5 sm:-mt-6 pb-12">
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-100 p-6 sm:p-8">

        <div class="rounded-2xl ring-1 ring-slate-200 bg-slate-50 p-5 mb-6">
            <div class="text-xs uppercase font-semibold tracking-wide text-slate-500">Applicant</div>
            <div class="text-xl font-extrabold tracking-tight mt-1">{{ $application->full_name }}</div>
            @if($application->schoolClass)
                <div class="text-sm text-slate-500 mt-1">Class: {{ $application->schoolClass->name }}</div>
            @endif
        </div>

        @php
            $statusColors = [
                'submitted'              => 'bg-blue-50 ring-blue-200 text-blue-800',
                'entry_test_scheduled'   => 'bg-indigo-50 ring-indigo-200 text-indigo-800',
                'entry_test_taken'       => 'bg-amber-50 ring-amber-200 text-amber-800',
                'interview_scheduled'    => 'bg-indigo-50 ring-indigo-200 text-indigo-800',
                'interview_taken'        => 'bg-amber-50 ring-amber-200 text-amber-800',
                'pending_approval'       => 'bg-amber-50 ring-amber-200 text-amber-800',
                'admitted'               => 'bg-emerald-50 ring-emerald-200 text-emerald-800',
                'rejected'               => 'bg-red-50 ring-red-200 text-red-800',
                'waitlisted'             => 'bg-slate-50 ring-slate-200 text-slate-700',
            ];
            $key = $application->status?->value ?? (string) $application->status;
        @endphp

        <div class="rounded-2xl ring-1 p-5 sm:p-6 {{ $statusColors[$key] ?? 'bg-slate-50 ring-slate-200' }}">
            <div class="text-xs uppercase font-semibold tracking-wide">Current status</div>
            <div class="text-2xl font-extrabold tracking-tight mt-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>

            @if($application->entry_test_scheduled_at)
                <div class="text-sm mt-3">
                    <strong>Entry test:</strong> {{ $application->entry_test_scheduled_at->format('d M Y, h:i A') }}
                    @if($application->entry_test_room) at room {{ $application->entry_test_room }} @endif
                </div>
            @endif

            @php
                // Hide test/interview/final scores from the applicant unless
                // the resolved test has show_score_to_applicant ON and its
                // results are published.
                $resolvedTest = \App\Models\Tenant\AdmissionTest::resolveFor(
                    $application->academic_year_id,
                    $application->class_id,
                );
                $applicantCanSeeMarks = $resolvedTest
                    && $resolvedTest->show_score_to_applicant
                    && $resolvedTest->areResultsPublished();
            @endphp

            @if($applicantCanSeeMarks && $application->entry_test_score !== null)
                <div class="text-sm mt-1"><strong>Entry test score:</strong> {{ $application->entry_test_score }}</div>
            @endif

            @if($application->interview_scheduled_at)
                <div class="text-sm mt-3">
                    <strong>Interview:</strong> {{ $application->interview_scheduled_at->format('d M Y, h:i A') }}
                    @if($application->interview_room) at room {{ $application->interview_room }} @endif
                    @if($application->interview_panel) — {{ $application->interview_panel }} @endif
                </div>
            @endif

            @if($applicantCanSeeMarks && $application->interview_score !== null)
                <div class="text-sm mt-1"><strong>Interview score:</strong> {{ $application->interview_score }}</div>
            @endif

            @if($applicantCanSeeMarks && $application->final_percentage !== null)
                <div class="text-sm mt-3">
                    <strong>Weighted final:</strong>
                    {{ number_format((float) $application->final_percentage, 2) }}%
                </div>
            @endif

            @if($application->decision_notes)
                <div class="text-sm mt-3 whitespace-pre-line"><strong>Notes:</strong> {{ $application->decision_notes }}</div>
            @endif
        </div>

        @if($application->status?->value === 'admitted')
            <div class="mt-6 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-4 text-sm text-emerald-800">
                🎉 <strong>Congratulations!</strong> Watch your email — an account activation link has been sent to set your password.
            </div>
        @endif

        <p class="mt-6 text-xs text-slate-500">
            🔖 Bookmark this page to check your status later. The link is unique to your application.
        </p>
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        Powered by <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="font-semibold text-slate-500 hover:text-slate-700">KynexEdu</a>
    </p>
</div>
</body>
</html>
