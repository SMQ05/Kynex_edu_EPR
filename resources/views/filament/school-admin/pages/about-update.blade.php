<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">System Information</x-slot>
        <x-slot name="description">KynexEdu is updated centrally — there is nothing to install here.</x-slot>

        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($this->info() as $label => $value)
                <div class="grid grid-cols-3 gap-4 py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Updates</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Your school is hosted as part of the KynexEdu SaaS platform by
            <a href="https://kynexsolutions.com" target="_blank" class="text-primary-600 hover:underline">kynexsolutions.com</a>.
            New features and security updates are rolled out automatically — no manual update is required.
        </p>
    </x-filament::section>
</x-filament-panels::page>
