<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Application Status — {{ $tenant->school_name }}</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
<div class="max-w-2xl mx-auto py-12 px-4">
    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-2xl font-bold mb-1">Application Status</h1>
        <p class="text-sm text-slate-600 mb-6">{{ $tenant->school_name }}</p>

        <div class="rounded-lg ring-1 ring-slate-200 p-5 mb-6">
            <div class="text-sm text-slate-500">Applicant</div>
            <div class="text-lg font-semibold">{{ $application->full_name }}</div>
            @if($application->schoolClass)
                <div class="text-sm text-slate-500 mt-1">Class: {{ $application->schoolClass->name }}</div>
            @endif
        </div>

        @php
            $statusColors = [
                'submitted'              => 'bg-blue-50 ring-blue-200 text-blue-800',
                'entry_test_scheduled'   => 'bg-indigo-50 ring-indigo-200 text-indigo-800',
                'entry_test_taken'       => 'bg-amber-50 ring-amber-200 text-amber-800',
                'pending_approval'       => 'bg-amber-50 ring-amber-200 text-amber-800',
                'admitted'               => 'bg-emerald-50 ring-emerald-200 text-emerald-800',
                'rejected'               => 'bg-red-50 ring-red-200 text-red-800',
                'waitlisted'             => 'bg-slate-50 ring-slate-200 text-slate-700',
            ];
            $key = $application->status?->value ?? (string) $application->status;
        @endphp

        <div class="rounded-lg ring-1 p-5 {{ $statusColors[$key] ?? 'bg-slate-50 ring-slate-200' }}">
            <div class="text-xs uppercase font-semibold tracking-wide">Current status</div>
            <div class="text-xl font-bold mt-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>

            @if($application->entry_test_scheduled_at)
                <div class="text-sm mt-3">
                    <strong>Entry test:</strong> {{ $application->entry_test_scheduled_at->format('d M Y, h:i A') }}
                    @if($application->entry_test_room) at room {{ $application->entry_test_room }} @endif
                </div>
            @endif

            @if($application->entry_test_score !== null)
                <div class="text-sm mt-1"><strong>Entry test score:</strong> {{ $application->entry_test_score }}</div>
            @endif

            @if($application->decision_notes)
                <div class="text-sm mt-3 whitespace-pre-line"><strong>Notes:</strong> {{ $application->decision_notes }}</div>
            @endif
        </div>

        @if($application->status?->value === 'admitted')
            <p class="mt-6 text-sm text-emerald-800">
                Congratulations! Watch your email — an account activation link has been sent to set your password.
            </p>
        @endif

        <p class="mt-6 text-xs text-slate-500">
            Bookmark this page to check your status later. The link is unique to your application.
        </p>
    </div>
</div>
</body>
</html>
