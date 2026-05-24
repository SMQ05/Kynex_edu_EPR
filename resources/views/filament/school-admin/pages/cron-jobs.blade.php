<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Scheduled Tasks</x-slot>
        <x-slot name="description">
            These tasks run automatically via the system cron. This view is read-only.
        </x-slot>

        @php($tasks = $this->tasks())

        @if (count($tasks) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">No scheduled tasks are registered.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Task</th>
                            <th class="py-2 pr-4 font-medium">Schedule</th>
                            <th class="py-2 pr-4 font-medium">Last run</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($tasks as $task)
                            <tr>
                                <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ $task['name'] }}</td>
                                <td class="py-2 pr-4 font-mono text-gray-600 dark:text-gray-300">{{ $task['expression'] }}</td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $task['last_run'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
