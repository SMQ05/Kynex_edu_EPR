<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Counseling & Behavior Module</x-slot>

        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 0; text-align: center;">
            <svg style="width: 4rem; height: 4rem; color: var(--gray-400, #9ca3af); margin-bottom: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--gray-700, #374151);">Counseling Module Coming Soon</h3>
            <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-500, #6b7280); max-width: 28rem;">
                Behavior incident logging, counseling case management, session scheduling, 
                and follow-up tracking will be available in Phase 5.
            </p>
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <x-filament::button color="gray" disabled icon="heroicon-m-flag">
                    Log Incident
                </x-filament::button>
                <x-filament::button color="gray" disabled icon="heroicon-m-calendar">
                    Schedule Session
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
