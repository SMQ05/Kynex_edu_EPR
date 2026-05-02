<x-filament-panels::page>
    {{-- Analytics Dashboard is widget-driven. Header widgets render above. --}}
    <div class="text-sm text-gray-500 dark:text-gray-400">
        <p>
            📊 School analytics showing attendance trends, fee collection, exam performance, and enrolment distribution.
            Data refreshes automatically. Use the
            <a href="{{ \App\Filament\SchoolAdmin\Pages\ReportBuilderPage::getUrl() }}"
               class="text-primary-600 hover:underline font-medium">
                Report Builder
            </a>
            for custom queries and exports.
        </p>
    </div>
</x-filament-panels::page>
