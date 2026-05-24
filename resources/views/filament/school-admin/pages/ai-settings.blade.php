<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status overview --}}
        <x-filament::section>
            <x-slot name="heading">AI status this month</x-slot>
            <x-slot name="description">
                @if ($statusEnabled)
                    AI is <strong>enabled</strong>
                    @if ($usingOwnKey) (using your own API key) @else (platform-managed) @endif
                    — provider <strong>{{ $statusProvider }}</strong>, model <strong>{{ $statusModel }}</strong>.
                @else
                    AI is currently <strong>turned off</strong> for your school. Add your own key below, or ask your platform admin to enable it.
                @endif
            </x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Monthly budget</div>
                    <div class="text-xl font-bold">{{ $this->budgetPkr() }} <span class="text-sm font-normal">PKR</span></div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Used this month</div>
                    <div class="text-xl font-bold">{{ $this->usedPkr() }} <span class="text-sm font-normal">PKR</span></div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Remaining</div>
                    <div class="text-xl font-bold">{{ $this->remainingPkr() }} <span class="text-sm font-normal">PKR</span></div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">AI requests</div>
                    <div class="text-xl font-bold">{{ number_format($callsThisMonth) }}</div>
                </div>
            </div>

            @if ($budgetPaisas > 0)
                <div class="mt-4">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-2 rounded-full {{ $this->usedPercent() >= 90 ? 'bg-danger-500' : 'bg-primary-500' }}"
                             style="width: {{ $this->usedPercent() }}%"></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">{{ $this->usedPercent() }}% of monthly budget used</div>
                </div>
            @endif
        </x-filament::section>

        {{-- BYO key form --}}
        <form wire:submit.prevent="saveOwnKey">
            {{ $this->settingsForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                    Save AI provider settings
                </x-filament::button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
