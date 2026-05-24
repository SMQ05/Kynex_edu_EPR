<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Maintenance</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Use the buttons in the page header to clear caches and optimize the application.
            Each action asks for confirmation before running.
        </p>

        <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li><strong>Clear Cache</strong> — runs <code>optimize:clear</code> (app, route, config, view caches).</li>
            <li><strong>Optimize</strong> — runs <code>optimize</code> (caches config, routes, events, views).</li>
            <li><strong>Clear Compiled Views</strong> — runs <code>view:clear</code>.</li>
            <li><strong>Clear Config Cache</strong> — runs <code>config:clear</code>.</li>
            <li><strong>Clear Filament Cache</strong> — runs <code>filament:optimize-clear</code>.</li>
        </ul>
    </div>
</x-filament-panels::page>
