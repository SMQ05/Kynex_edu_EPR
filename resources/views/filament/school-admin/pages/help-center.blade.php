<x-filament-panels::page>
    <div class="space-y-6">
        @foreach ($this->topics() as $topic)
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 dark:border-white/10">
                    <x-filament::icon
                        :icon="$topic['icon']"
                        class="h-5 w-5 text-primary-500"
                    />
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $topic['title'] }}
                    </h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($topic['items'] as $item)
                        <details class="group px-5 py-3" open>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 text-sm font-medium text-gray-900 dark:text-white">
                                <span>{{ $item['q'] }}</span>
                                <x-filament::icon
                                    icon="heroicon-m-chevron-down"
                                    class="h-4 w-4 flex-shrink-0 text-gray-400 transition group-open:rotate-180"
                                />
                            </summary>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $item['a'] }}
                            </p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="rounded-xl bg-blue-50 p-5 ring-1 ring-blue-100 dark:bg-blue-950/30 dark:ring-blue-800/40">
            <div class="text-sm text-blue-900 dark:text-blue-200">
                <strong>Need more help?</strong>
                Email <a href="mailto:hello@kynexsolutions.com" class="underline">hello@kynexsolutions.com</a>
                or use the AI Assistant (bottom-right of every page) — it can explain any feature in plain words.
            </div>
        </div>
    </div>
</x-filament-panels::page>
