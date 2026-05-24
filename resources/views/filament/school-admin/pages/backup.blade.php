<x-filament-panels::page>
    @php($command = $this->availableCommand())

    <x-filament::section>
        <x-slot name="heading">Database Backup</x-slot>
        <x-slot name="description">Create an on-demand backup of your school's data.</x-slot>

        @if ($command)
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Backup command detected: <code class="font-mono">{{ $command }}</code>.
                Use the <strong>Run Backup Now</strong> button above to start a backup.
            </p>
        @else
            <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 p-4 text-sm text-warning-700 dark:text-warning-400">
                <p class="font-medium">No on-demand backup command is installed in this environment.</p>
                <p class="mt-1">
                    Your data is protected by infrastructure-level backups (database host snapshots /
                    Coolify scheduled backups). To enable on-demand backups from this page, install
                    <code class="font-mono">spatie/laravel-backup</code> and its <code class="font-mono">backup:run</code> command.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
