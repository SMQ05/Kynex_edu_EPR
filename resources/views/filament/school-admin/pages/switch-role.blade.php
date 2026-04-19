<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($availableRoles as $role)
            <div
                wire:click="switchTo('{{ $role }}')"
                class="relative cursor-pointer rounded-xl border p-6 transition hover:shadow-lg
                    {{ $role === $currentRole
                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-950 ring-2 ring-primary-500'
                        : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 hover:border-primary-300' }}"
            >
                <div class="flex items-center gap-3">
                    <x-heroicon-o-shield-check class="h-8 w-8 {{ $role === $currentRole ? 'text-primary-500' : 'text-gray-400' }}" />
                    <div>
                        <h3 class="text-base font-semibold {{ $role === $currentRole ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                            {{ str_replace('_', ' ', $role) }}
                        </h3>
                        @if ($role === $currentRole)
                            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">Currently active</span>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400">Click to switch</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
