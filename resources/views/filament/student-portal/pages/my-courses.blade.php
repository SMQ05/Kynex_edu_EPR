<x-filament-panels::page>
    @include('filament.portal.styles')

    @php
        $courses = $this->courses;
        $overview = $this->overview;
        $focus = $this->focus;
    @endphp

    @if ($courses->isEmpty())
        <x-filament::section>
            <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Your class does not have a published course plan yet. Once your teachers publish one,
                this page will show every unit, what has been taught, and the material for each.
            </p>
        </x-filament::section>
    @else
        {{-- Headline position across every course --}}
        <x-filament::section>
            <div class="sp-grid-stats">
                <div class="sp-stat">
                    <div class="sp-stat__value sp-teal">{{ $overview['pct'] }}%</div>
                    <div class="sp-stat__label">Course covered</div>
                    <span class="sp-stat__hint">{{ $overview['taught'] }} of {{ $overview['units'] }} units taught</span>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value">{{ $overview['courses'] }}</div>
                    <div class="sp-stat__label">Courses</div>
                    <span class="sp-stat__hint">with a published plan</span>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value">{{ $overview['material'] }}</div>
                    <div class="sp-stat__label">Recordings</div>
                    <span class="sp-stat__hint">placed on your units</span>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value">{{ $overview['practised'] }}</div>
                    <div class="sp-stat__label">Practised</div>
                    <span class="sp-stat__hint">lectures you have quizzed on</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Where the time is best spent --}}
        @if (count($focus) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <span class="inline-flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-flag" class="h-4 w-4 text-primary-500" />
                        Worth your time next
                    </span>
                </x-slot>
                <x-slot name="description">
                    Based on everything of yours that has been marked — exams, homework and practice.
                    Subjects without enough marked work yet are left out.
                </x-slot>

                <div class="sp-focus">
                    @foreach ($focus as $item)
                        <div class="sp-focus__item">
                            <div class="sp-focus__ring" style="--v: {{ max(3, min(100, $item['pct'])) }}">
                                <span>{{ $item['pct'] }}<small>%</small></span>
                            </div>
                            <div>
                                <div class="sp-focus__subject">{{ $item['subject'] }}</div>
                                <div class="sp-focus__meta">across {{ $item['pieces'] }} marked {{ \Illuminate\Support\Str::plural('piece', $item['pieces']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- The course map itself --}}
        @foreach ($courses as $course)
            @php $open = $this->openSubject === $course['id']; @endphp
            <x-filament::section>
                <div class="sp-course__top">
                    <div style="min-width:0;">
                        <div class="sp-course__subject">{{ $course['subject'] }}</div>
                        <div class="sp-course__title">{{ $course['title'] }}</div>
                        @if ($course['teacher'])
                            <div class="sp-course__teacher">{{ $course['teacher'] }}</div>
                        @endif
                    </div>
                    <div class="sp-course__right">
                        @if ($course['standing'])
                            <div class="sp-course__standing" title="Your average across marked work in this subject">
                                <span class="sp-course__standingv">{{ $course['standing']['pct'] }}%</span>
                                <span class="sp-course__standingl">your average</span>
                            </div>
                        @endif
                        <div class="sp-course__pct">{{ $course['pct'] }}%</div>
                    </div>
                </div>

                <div class="pp-bar"><span style="width: {{ max(2, $course['pct']) }}%"></span></div>

                <div class="sp-course__meta">
                    {{ $course['done'] }} of {{ $course['total'] }} units taught
                    @if ($course['current'])
                        · now on <strong>{{ $course['current']['title'] }}</strong>
                    @endif
                </div>

                <button type="button" class="sp-course__toggle" wire:click="toggleSubject('{{ $course['id'] }}')">
                    {{ $open ? 'Hide the full plan' : 'See all ' . $course['total'] . ' units' }}
                </button>

                @if ($open)
                    <ol class="sp-units">
                        @foreach ($course['units'] as $unit)
                            <li class="sp-unit sp-unit--{{ $unit['status'] }}">
                                <div class="sp-unit__mark" aria-hidden="true"></div>
                                <div class="sp-unit__body">
                                    <div class="sp-unit__head">
                                        <span class="sp-unit__title">{{ $unit['title'] }}</span>
                                        <span class="sp-unit__tag">
                                            @if ($unit['status'] === 'completed')
                                                Taught
                                            @elseif ($unit['status'] === 'in_progress')
                                                In class now
                                            @else
                                                Week {{ $unit['week'] }}
                                            @endif
                                        </span>
                                    </div>

                                    @if ($unit['description'])
                                        <p class="sp-unit__desc">{{ $unit['description'] }}</p>
                                    @endif

                                    @foreach ($unit['material'] as $m)
                                        <a class="sp-unit__mat"
                                           href="{{ \App\Filament\StudentPortal\Pages\MyLectures::getUrl(['lecture' => $m['id']]) }}">
                                            <x-filament::icon icon="heroicon-m-play-circle" class="h-4 w-4" />
                                            <span class="sp-unit__matname">{{ $m['title'] }}</span>
                                            @if ($m['questions'] > 0)
                                                <span class="sp-chip">{{ $m['questions'] }} questions</span>
                                            @endif
                                            @if ($m['cards'] > 0)
                                                <span class="sp-chip">{{ $m['cards'] }} cards</span>
                                            @endif
                                            @if ($m['best'] !== null)
                                                <span class="sp-chip sp-chip--best">best {{ (int) $m['best'] }}%</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-filament::section>
        @endforeach
    @endif
</x-filament-panels::page>
