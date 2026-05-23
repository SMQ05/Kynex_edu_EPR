<x-filament-panels::page>
    {{-- User picker --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-4 mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Pick a user
        </label>
        <select
            wire:model.live="userId"
            class="block w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700"
        >
            <option value="">— select staff or teacher —</option>
            @foreach($this->getUserOptions() as $id => $label)
                <option value="{{ $id }}" @selected($userId === $id)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-gray-500">
            Granting an admission permission to a user takes effect immediately. Roles are not changed — only direct permissions are updated.
        </p>
    </div>

    {{-- Per-user delegation form --}}
    @if($this->resolveUser())
        <form wire:submit.prevent="save">
            {{ $this->form }}
            <div class="mt-4 flex justify-end">
                <x-filament::button type="submit" color="primary">
                    Save delegation
                </x-filament::button>
            </div>
        </form>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-8 text-center text-gray-500">
            Pick a user from the dropdown above to view and edit their admission permissions.
        </div>
    @endif

    {{-- Current delegations summary table --}}
    @php $summary = $this->getDelegationSummary(); @endphp

    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
            Currently Delegated Users
        </h2>

        @if(count($summary) === 0)
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-6 text-center text-gray-500 text-sm">
                No admission permissions have been delegated yet.
            </div>
        @else
            <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Role(s)</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Admission Permissions &amp; Scope</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($summary as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $row['name'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    @foreach(explode(', ', $row['roles']) as $role)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300 mr-1">
                                            {{ $role }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $row['email'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <ul class="space-y-1">
                                        @foreach($row['perms'] as $perm => $detail)
                                            <li class="flex items-start gap-2">
                                                <span class="mt-0.5 h-2 w-2 rounded-full bg-green-500 flex-shrink-0"></span>
                                                <span>
                                                    <span class="font-medium">{{ $detail['label'] }}</span>
                                                    <span class="text-gray-400 dark:text-gray-500"> — {{ $detail['scope'] }}</span>
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <button
                                        type="button"
                                        wire:click="$set('userId', '{{ $row['_uid'] ?? '' }}')"
                                        class="text-primary-600 hover:underline text-xs"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
