@extends('cms.layout')

@section('title', 'Admissions - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">Admissions</h1>
            <p class="text-lg text-white/80">Join our family of learners</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">Admissions</span>
            </nav>
        </div>
    </div>

    {{-- Admission Status Banner --}}
    @if($settings->admission_open)
        <div class="bg-green-50 border-b border-green-200">
            <div class="max-w-7xl mx-auto px-4 py-6 text-center">
                <div class="inline-flex items-center gap-2 bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-semibold mb-3">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Admissions Open
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">We are accepting applications!</h2>
                <p class="text-gray-600 mb-4">Apply now for the upcoming academic session.</p>
                @if($settings->admission_form_url)
                    <a href="{{ $settings->admission_form_url }}"
                       target="_blank"
                       class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                        Apply Online →
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="bg-gray-50 border-b">
            <div class="max-w-7xl mx-auto px-4 py-6 text-center">
                <div class="inline-flex items-center gap-2 bg-gray-200 text-gray-600 px-4 py-2 rounded-full text-sm font-semibold mb-3">
                    Admissions Closed
                </div>
                <p class="text-gray-600">Admissions for the current session are currently closed. Please check back later or contact us for more information.</p>
            </div>
        </div>
    @endif

    {{-- Admission Process --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Admission Process</h2>
                <div class="w-16 h-1 bg-primary mx-auto mb-4"></div>
                <p class="text-gray-600 max-w-2xl mx-auto">Follow these simple steps to enrol your child at our school.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
                    <h3 class="font-bold text-gray-900 mb-2">Enquiry</h3>
                    <p class="text-sm text-gray-600">Visit the school or call us to learn about programs and fee structure.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
                    <h3 class="font-bold text-gray-900 mb-2">Application</h3>
                    <p class="text-sm text-gray-600">Fill out the admission form online or collect it from the office.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
                    <h3 class="font-bold text-gray-900 mb-2">Assessment</h3>
                    <p class="text-sm text-gray-600">Students undergo a basic assessment and interview process.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold mx-auto mb-4">4</div>
                    <h3 class="font-bold text-gray-900 mb-2">Enrolment</h3>
                    <p class="text-sm text-gray-600">Submit required documents, pay the fee, and welcome aboard!</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Required Documents --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Required Documents</h2>
                <div class="w-16 h-1 bg-primary mx-auto"></div>
            </div>
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-xl shadow-sm p-8">
                    <ul class="space-y-4">
                        @foreach([
                            'Birth Certificate (original & copy)',
                            'Previous School Leaving Certificate / Transfer Certificate',
                            'Recent passport-size photographs (4)',
                            'Parent / Guardian CNIC copies',
                            'Previous academic reports / report cards',
                            'Immunization / Vaccination record',
                            'Domicile Certificate (if applicable)',
                        ] as $doc)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-gray-700">{{ $doc }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Contact CTA --}}
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Have Questions?</h2>
            <p class="text-gray-600 mb-8 text-lg">Our admissions team is ready to help you with any queries.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/contact" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition">
                    Contact Admissions Office
                </a>
                @if($settings->phone)
                    <a href="tel:{{ $settings->phone }}" class="border-2 border-primary text-primary px-8 py-3 rounded-lg font-semibold hover:bg-primary hover:text-white transition">
                        Call {{ $settings->phone }}
                    </a>
                @endif
            </div>
        </div>
    </section>

@endsection
