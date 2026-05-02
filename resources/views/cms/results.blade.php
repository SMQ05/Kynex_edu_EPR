@extends('cms.layout')

@section('title', 'Exam Results - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">Exam Results</h1>
            <p class="text-lg text-white/80">Check your examination results online</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">Results</span>
            </nav>
        </div>
    </div>

    {{-- Search Form --}}
    <section class="py-16">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white border rounded-xl p-8 shadow-sm">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Search Your Results</h2>
                    <p class="text-gray-600">Enter your admission number to view published exam results.</p>
                </div>

                <form action="/results" method="GET" class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="admission_number" value="{{ $query ?? '' }}" required
                           class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition text-lg"
                           placeholder="Enter Admission Number (e.g., STD-2024-001)">
                    <button type="submit"
                            class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition whitespace-nowrap">
                        Search Results
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Results Display --}}
    @if(isset($query) && $query)
        <section class="pb-16">
            <div class="max-w-5xl mx-auto px-4">
                @if($results && $results->count())
                    {{-- Student Info --}}
                    @php $student = $results->first()->student; @endphp
                    <div class="bg-primary/5 rounded-xl p-6 mb-8 border border-primary/20">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $student->full_name ?? 'Student' }}</h3>
                                <p class="text-sm text-gray-600">
                                    Admission #: <span class="font-medium">{{ $student->admission_number }}</span>
                                    @if($student->schoolClass)
                                        &bull; Class: <span class="font-medium">{{ $student->schoolClass->name }}</span>
                                    @endif
                                    @if($student->section)
                                        &bull; Section: <span class="font-medium">{{ $student->section->name }}</span>
                                    @endif
                                </p>
                            </div>
                            <button onclick="window.print()" 
                                    class="text-primary text-sm font-semibold hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                        </div>
                    </div>

                    {{-- Results Table --}}
                    @foreach($results->groupBy('exam.name') as $examName => $examResults)
                        <div class="bg-white border rounded-xl overflow-hidden mb-6">
                            <div class="bg-gray-50 px-6 py-4 border-b">
                                <h3 class="font-bold text-gray-900 text-lg">{{ $examName }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left px-6 py-3 font-semibold text-gray-600">Field</th>
                                            <th class="text-center px-6 py-3 font-semibold text-gray-600">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Total Marks</td>
                                            <td class="px-6 py-3 text-center font-medium">{{ $examResults->first()->total_marks ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Obtained Marks</td>
                                            <td class="px-6 py-3 text-center font-medium">{{ $examResults->first()->obtained_marks ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Percentage</td>
                                            <td class="px-6 py-3 text-center font-medium">
                                                {{ $examResults->first()->percentage ? number_format($examResults->first()->percentage, 1) . '%' : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Grade</td>
                                            <td class="px-6 py-3 text-center">
                                                @if($examResults->first()->grade)
                                                    <span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-bold">
                                                        {{ $examResults->first()->grade }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">GPA</td>
                                            <td class="px-6 py-3 text-center font-medium">{{ $examResults->first()->gpa ? number_format($examResults->first()->gpa, 2) : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Position</td>
                                            <td class="px-6 py-3 text-center font-medium">{{ $examResults->first()->position ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-3 text-gray-700">Status</td>
                                            <td class="px-6 py-3 text-center">
                                                @php $status = $examResults->first()->status ?? null; @endphp
                                                @if($status)
                                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold
                                                        {{ $status === 'passed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                        {{ ucfirst($status) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
                        <svg class="w-12 h-12 text-yellow-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <h3 class="text-lg font-bold text-yellow-800 mb-2">No Results Found</h3>
                        <p class="text-yellow-700">
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
        nav, footer, .bg-gradient-to-r, .bg-gray-50.border-b, form, button[onclick="window.print()"] {
            display: none !important;
        }
        main { padding-top: 0 !important; }
    }
</style>
@endpush
