<div>
    @if (count($availableRoles) > 1)
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Role:</span>
            <div class="flex gap-1 flex-wrap">
                @foreach ($availableRoles as $key => $label)
                    <button
                        wire:click="switchRole('{{ $key }}')"
                        @class([
                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-colors',
                            'bg-primary-500 text-white' => $activeRole === $key,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $activeRole !== $key,
                        ])
                    >
                        {{ str_replace('_', ' ', $label) }}
                    </button>
                @endforeach
            </div>
        </div>
    @elseif (count($availableRoles) === 1)
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Role:</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-500 text-white">
                {{ str_replace('_', ' ', $activeRole) }}
            </span>
        </div>
    @endif
</div>
