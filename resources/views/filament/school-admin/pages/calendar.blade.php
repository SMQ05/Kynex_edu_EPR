<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Month header --}}
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                {{ $this->getMonthLabel() }}
            </h2>
        </div>

        {{-- Month grid --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="grid grid-cols-7 border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800/50">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow)
                    <div class="px-2 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $dow }}
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($this->getWeeks() as $week)
                    @foreach ($week as $cell)
                        @php
                            $isToday = $cell['date']->isToday();
                            $inMonth = $cell['inMonth'];
                        @endphp
                        <div @class([
                            'min-h-[96px] border-b border-r border-gray-100 p-1.5 dark:border-white/5',
                            'bg-gray-50/60 dark:bg-gray-800/30' => ! $inMonth,
                        ])>
                            <div @class([
                                'mb-1 flex h-6 w-6 items-center justify-center rounded-full text-xs',
                                'font-bold text-gray-400 dark:text-gray-600' => ! $inMonth,
                                'font-medium text-gray-700 dark:text-gray-300' => $inMonth && ! $isToday,
                                'bg-primary-600 font-bold text-white' => $isToday,
                            ])>
                                {{ $cell['date']->day }}
                            </div>

                            <div class="space-y-1">
                                @foreach ($cell['events'] as $event)
                                    <a href="{{ \App\Filament\SchoolAdmin\Resources\EventResource::getUrl('edit', ['record' => $event]) }}"
                                       class="block truncate rounded px-1.5 py-0.5 text-xs text-white"
                                       style="background-color: {{ $event->color ?? '#1a56db' }}"
                                       title="{{ $event->title }} — {{ $event->start_at->format('H:i') }}">
                                        @unless ($event->all_day)
                                            <span class="opacity-80">{{ $event->start_at->format('H:i') }}</span>
                                        @endunless
                                        {{ $event->title }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Upcoming agenda --}}
        <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-base font-semibold text-gray-950 dark:text-white">Upcoming (next 60 days)</h3>

            @php $upcoming = $this->getUpcoming(); @endphp

            @if ($upcoming->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($upcoming as $event)
                        <li class="flex items-start gap-3 py-2.5">
                            <span class="mt-1 inline-block h-3 w-3 flex-shrink-0 rounded-full"
                                  style="background-color: {{ $event->color ?? '#1a56db' }}"></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ \App\Filament\SchoolAdmin\Resources\EventResource::getUrl('edit', ['record' => $event]) }}"
                                   class="font-medium text-gray-950 hover:underline dark:text-white">
                                    {{ $event->title }}
                                </a>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event->start_at->format('D, d M Y') }}
                                    @unless ($event->all_day)
                                        · {{ $event->start_at->format('H:i') }}@if ($event->end_at)–{{ $event->end_at->format('H:i') }}@endif
                                    @endunless
                                    @if ($event->location) · {{ $event->location }} @endif
                                    · {{ \App\Models\Tenant\Event::AUDIENCES[$event->audience] ?? $event->audience }}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-panels::page>
