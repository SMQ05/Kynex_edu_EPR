@extends('cms.layout')

@section('title', 'Exam Results - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <section class="no-print relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3">Exam Results</h1>
            <p class="text-lg text-white/85">Check your examination results online</p>
            <nav class="mt-5 text-sm text-white/70">
                <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
                <span class="mx-2">/</span>
                <span class="text-white">Results</span>
            </nav>
        </div>
    </section>

    {{-- Search Form --}}
    <section class="no-print py-14 sm:py-16">
        <div class="max-w-3xl mx-auto px-6">
            <div class="bg-white border border-slate-100 rounded-3xl p-8 sm:p-10 shadow-sm reveal">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-2xl grad-brand flex items-center justify-center mx-auto mb-5 shadow-md">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mb-2">Search Your Results</h2>
                    <p class="text-slate-500">Enter your admission number to view published exam results.</p>
                </div>

                <form action="/results" method="GET" class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="admission_number" value="{{ $query ?? '' }}" required
                           class="flex-1 min-h-[52px] px-5 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition text-lg"
                           placeholder="Enter Admission Number (e.g., STD-2024-001)">
                    <button type="submit"
                            class="inline-flex items-center justify-center min-h-[52px] grad-brand text-white px-8 py-3 rounded-xl font-semibold shadow-md hover:-translate-y-0.5 hover:shadow-xl transition whitespace-nowrap">
                        Search Results
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Results Display --}}
    @if(isset($query) && $query)
        <section class="pb-16 sm:pb-20">
            <div class="max-w-5xl mx-auto px-6">
                @if($results && $results->count())
                    {{-- Student Info --}}
                    @php $student = $results->first()->student; @endphp
                    <div class="bg-gradient-to-br from-primary/5 to-cyan-50 rounded-2xl p-6 mb-8 border border-primary/15">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $student->full_name ?? 'Student' }}</h3>
                                <p class="text-sm text-slate-600 mt-1">
                                    Admission #: <span class="font-semibold">{{ $student->admission_number }}</span>
                                    @if($student->schoolClass)
                                        &bull; Class: <span class="font-semibold">{{ $student->schoolClass->name }}</span>
                                    @endif
                                    @if($student->section)
                                        &bull; Section: <span class="font-semibold">{{ $student->section->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <button onclick="window.print()"
                                    class="no-print inline-flex items-center gap-1.5 text-primary text-sm font-semibold hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                        </div>
                    </div>

                    {{-- Results: table on desktop, cards on mobile --}}
                    @foreach($results->groupBy('exam.name') as $examName => $examResults)
                        @php $first = $examResults->first(); @endphp
                        <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden mb-6 shadow-sm">
                            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                                <h3 class="font-bold text-slate-900 text-lg">{{ $examName }}</h3>
                            </div>

                            {{-- Mobile cards --}}
                            <dl class="sm:hidden divide-y divide-slate-100">
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Total Marks</dt>
                                    <dd class="font-semibold text-slate-900">{{ $first->total_marks ?? '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Obtained Marks</dt>
                                    <dd class="font-semibold text-slate-900">{{ $first->obtained_marks ?? '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Percentage</dt>
                                    <dd class="font-semibold text-slate-900">{{ $first->percentage ? number_format($first->percentage, 1) . '%' : '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Grade</dt>
                                    <dd>
                                        @if($first->grade)
                                            <span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-bold">{{ $first->grade }}</span>
                                        @else - @endif
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">GPA</dt>
                                    <dd class="font-semibold text-slate-900">{{ $first->gpa ? number_format($first->gpa, 2) : '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Position</dt>
                                    <dd class="font-semibold text-slate-900">{{ $first->position ?? '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between px-6 py-3">
                                    <dt class="text-slate-500">Status</dt>
                                    <dd>
                                        @php $status = $first->status ?? null; @endphp
                                        @if($status)
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $status === 'passed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($status) }}</span>
                                        @else - @endif
                                    </dd>
                                </div>
                            </dl>

                            {{-- Desktop table --}}
                            <div class="hidden sm:block overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="text-left px-6 py-3 font-bold text-slate-600">Field</th>
                                            <th class="text-center px-6 py-3 font-bold text-slate-600">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Total Marks</td>
                                            <td class="px-6 py-3 text-center font-semibold">{{ $first->total_marks ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Obtained Marks</td>
                                            <td class="px-6 py-3 text-center font-semibold">{{ $first->obtained_marks ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Percentage</td>
                                            <td class="px-6 py-3 text-center font-semibold">
                                                {{ $first->percentage ? number_format($first->percentage, 1) . '%' : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Grade</td>
                                            <td class="px-6 py-3 text-center">
                                                @if($first->grade)
                                                    <span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-bold">{{ $first->grade }}</span>
                                                @else - @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">GPA</td>
                                            <td class="px-6 py-3 text-center font-semibold">{{ $first->gpa ? number_format($first->gpa, 2) : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Position</td>
                                            <td class="px-6 py-3 text-center font-semibold">{{ $first->position ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-slate-700">Status</td>
                                            <td class="px-6 py-3 text-center">
                                                @php $status = $first->status ?? null; @endphp
                                                @if($status)
                                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $status === 'passed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($status) }}</span>
                                                @else - @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                        <svg class="w-12 h-12 text-amber-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <h3 class="text-lg font-bold text-amber-800 mb-2">No Results Found</h3>
                        <p class="text-amber-700">
                            No published results found for admission number "<strong>{{ $query }}</strong>".
                            Please verify your admission number and try again.
                        </p>
                    </div>
                @endif
            </div>
        </section>
    @endif

@endsection

@push('styles')
<style>
    @media print {
        .no-print, nav, footer { display: none !important; }
        main { padding-top: 0 !important; }
    }
</style>
@endpush
