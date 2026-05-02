<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Health Module</x-slot>

        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 0; text-align: center;">
            <svg style="width: 4rem; height: 4rem; color: var(--gray-400, #9ca3af); margin-bottom: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--gray-700, #374151);">Health Management Coming Soon</h3>
            <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-500, #6b7280); max-width: 28rem;">
                Clinic visit logging, medical records, allergy tracking, and medication management 
                will be available in Phase 5 — Health Module.
            </p>
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <x-filament::button color="gray" disabled icon="heroicon-m-plus">
                    Log Clinic Visit
                </x-filament::button>
                <x-filament::button color="gray" disabled icon="heroicon-m-document-text">
                    View Medical Records
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
