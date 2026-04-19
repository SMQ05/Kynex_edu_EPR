<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Quick Book Search</x-slot>
        <x-slot name="description">Library module will be fully available in Phase 5</x-slot>

        <div class="flex items-center gap-4">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" placeholder="Search books by title, ISBN, or author..." disabled />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button disabled icon="heroicon-m-magnifying-glass">
                Search
            </x-filament::button>
        </div>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Book search functionality coming in Phase 5 — Library Management Module.
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
