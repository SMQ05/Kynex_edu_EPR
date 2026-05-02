<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex-1">
                <h3 class="text-lg font-semibold">{{ $this->record->title }}</h3>
                <p class="text-sm text-gray-500">
                    {{ $this->record->schoolClass?->name }}
                    @if($this->record->section) / {{ $this->record->section->name }} @endif
                    &bull; {{ $this->record->scheduled_at->format('M d, Y h:i A') }}
                    &bull; Status: <span class="font-medium">{{ $this->record->status->value }}</span>
                </p>
            </div>
            <div>
                <a href="{{ \App\Filament\SchoolAdmin\Resources\OnlineClassResource::getUrl('index') }}"
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                    &larr; Back to Classes
                </a>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
