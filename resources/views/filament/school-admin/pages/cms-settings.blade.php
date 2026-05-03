<x-filament-panels::page>
    <div class="space-y-8">

        @php
            $sections = [
                ['label' => 'General Settings',          'form' => 'generalForm',         'save' => 'saveGeneral'],
                ['label' => 'Hero / Top of homepage',    'form' => 'heroForm',            'save' => 'saveHero'],
                ['label' => 'Vision & Mission',          'form' => 'visionForm',          'save' => 'saveVision'],
                ['label' => 'Why Choose Us',             'form' => 'whyUsForm',           'save' => 'saveWhyUs'],
                ['label' => 'Stats / Achievements',      'form' => 'statsForm',           'save' => 'saveStats'],
                ['label' => 'Facilities',                'form' => 'facilitiesForm',      'save' => 'saveFacilities'],
                ['label' => 'Testimonials',              'form' => 'testimonialsForm',    'save' => 'saveTestimonials'],
                ['label' => 'Admission Steps',           'form' => 'admissionStepsForm',  'save' => 'saveAdmissionSteps'],
                ['label' => 'Exam Highlights',           'form' => 'examHighlightsForm',  'save' => 'saveExamHighlights'],
                ['label' => 'About & Principal',         'form' => 'aboutForm',           'save' => 'saveAbout'],
                ['label' => 'Contact Info',              'form' => 'contactForm',         'save' => 'saveContact'],
                ['label' => 'Map embed',                 'form' => 'mapForm',             'save' => 'saveMap'],
                ['label' => 'Social Links',              'form' => 'socialForm',          'save' => 'saveSocial'],
                ['label' => 'Admissions Status',         'form' => 'admissionForm',       'save' => 'saveAdmission'],
            ];
        @endphp

        @foreach ($sections as $sec)
            <form wire:submit.prevent="{{ $sec['save'] }}">
                {{ $this->{$sec['form']} }}
                <div class="mt-4">
                    <x-filament::button type="submit" color="primary">
                        Save {{ $sec['label'] }}
                    </x-filament::button>
                </div>
            </form>
        @endforeach

    </div>
</x-filament-panels::page>
