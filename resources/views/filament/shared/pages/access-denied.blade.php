<x-filament-panels::page>
    <div class="mx-auto w-full max-w-2xl">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center
                    dark:border-amber-800/50 dark:bg-amber-950/40">

            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center
                        rounded-full bg-amber-100 dark:bg-amber-900/60">
                <x-filament::icon
                    icon="heroicon-o-lock-closed"
                    class="h-6 w-6 text-amber-600 dark:text-amber-300"
                />
            </div>

            <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                You don't have permission to access this page
            </h2>

            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">
                This area requires a permission your current role doesn't include.
                Contact your school administrator if you believe this is a mistake.
            </p>

            @if($this->getDeniedUrl())
                <p class="mt-3 break-all font-mono text-xs text-amber-700 dark:text-amber-300">
                    {{ $this->getDeniedUrl() }}
                </p>
            @endif

            <div class="mt-6">
                <x-filament::button
                    tag="a"
                    href="{{ \Filament\Facades\Filament::getUrl() }}"
                    icon="heroicon-o-arrow-left"
                    color="gray"
                >
                    Back to Dashboard
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
