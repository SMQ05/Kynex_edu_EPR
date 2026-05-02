<x-filament-panels::page>
    @php $children = $this->children; @endphp

    @if($children->isEmpty())
        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-800 p-6">
            <h3 class="text-lg font-semibold">No children linked to your account yet.</h3>
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">
                Ask the school office to link your account to your child's record. Once linked, you'll see their full
                profile, marks, attendance, upcoming homework, and exams here.
            </p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($children as $child)
                @php $s = $this->summaryFor($child); @endphp
                <div class="rounded-2xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 p-6 shadow-sm">
                    {{-- Child header --}}
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold">
                                {{ strtoupper(substr($child->first_name, 0, 1) . substr($child->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">{{ $child->full_name }}</h2>
                                <div class="text-sm text-gray-500">
                                    {{ $child->schoolClass?->name }}{{ $child->section ? ' / '.$child->section->name : '' }}
                                    @if($child->roll_number) &middot; Roll {{ $child->roll_number }} @endif
                                    @if($child->registration_number) &middot; Reg {{ $child->registration_number }} @endif
                                </div>
                            </div>
                        </div>
                        @if($s['attendancePct'] !== null)
                            <div class="text-right">
                                <div class="text-xs text-gray-500 uppercase">Attendance</div>
                                <div class="text-2xl font-bold {{ $s['attendancePct'] >= 80 ? 'text-emerald-600' : ($s['attendancePct'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $s['attendancePct'] }}%
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Latest result --}}
                    @if($s['latestResult'])
                        <div class="rounded-lg ring-1 ring-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:ring-emerald-800 p-4 mb-4">
                            <div class="text-xs uppercase font-semibold text-emerald-700 dark:text-emerald-300">Latest Result</div>
                            <div class="text-lg font-bold text-emerald-900 dark:text-emerald-100 mt-1">
                                {{ $s['latestResult']->exam?->name ?? 'Exam' }}
                            </div>
                            <div class="text-sm text-emerald-800 dark:text-emerald-200 mt-1">
                                {{ $s['latestResult']->marks_obtained }} / {{ $s['latestResult']->total_marks }}
                                @if($s['latestResult']->percentage)
                                    &middot; <strong>{{ number_format($s['latestResult']->percentage, 1) }}%</strong>
                                @endif
                                @if($s['latestResult']->grade) &middot; Grade <strong>{{ $s['latestResult']->grade }}</strong> @endif
                                @if($s['latestResult']->rank) &middot; Rank <strong>{{ $s['latestResult']->rank }}</strong> @endif
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Upcoming homework --}}
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                            <h4 class="font-semibold mb-3">Upcoming Homework</h4>
                            @if($s['upcomingHomework']->isEmpty())
                                <p class="text-sm text-gray-500">Nothing due right now.</p>
                            @else
                                <ul class="space-y-2 text-sm">
                                    @foreach($s['upcomingHomework'] as $hw)
                                        <li class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-medium">{{ $hw->title }}</div>
                                                <div class="text-xs text-gray-500">{{ $hw->subject?->name ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $hw->type?->value ?? $hw->type ?? 'homework')) }}</div>
                                            </div>
                                            <span class="text-xs text-amber-700 whitespace-nowrap">
                                                Due {{ \Carbon\Carbon::parse($hw->due_date)->format('d M') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Upcoming exams --}}
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                            <h4 class="font-semibold mb-3">Upcoming Exams</h4>
                            @if($s['upcomingExams']->isEmpty())
                                <p class="text-sm text-gray-500">No exams scheduled.</p>
                            @else
                                <ul class="space-y-2 text-sm">
                                    @foreach($s['upcomingExams'] as $sch)
                                        <li class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-medium">{{ $sch->exam?->name ?? 'Exam' }} — {{ $sch->subject?->name ?? '—' }}</div>
                                                <div class="text-xs text-gray-500">/{{ $sch->full_marks }} marks</div>
                                            </div>
                                            <span class="text-xs text-blue-700 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($sch->exam_date)->format('d M') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Recent marks --}}
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                            <h4 class="font-semibold mb-3">Recent Exam Marks</h4>
                            @if($s['recentMarks']->isEmpty())
                                <p class="text-sm text-gray-500">No marks recorded yet.</p>
                            @else
                                <ul class="space-y-1 text-sm">
                                    @foreach($s['recentMarks'] as $m)
                                        <li class="flex items-center justify-between">
                                            <span>{{ $m->schedule?->exam?->name ?? 'Exam' }} — {{ $m->schedule?->subject?->name ?? '—' }}</span>
                                            <span class="font-mono">
                                                @if($m->is_absent) <span class="text-red-600">Absent</span>
                                                @else {{ $m->marks_obtained ?? '—' }} / {{ $m->schedule?->full_marks ?? '—' }} @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        {{-- Recent homework grades --}}
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                            <h4 class="font-semibold mb-3">Recent Homework Grades</h4>
                            @if($s['recentHomeworkMarks']->isEmpty())
                                <p class="text-sm text-gray-500">No homework graded yet.</p>
                            @else
                                <ul class="space-y-1 text-sm">
                                    @foreach($s['recentHomeworkMarks'] as $h)
                                        <li class="flex items-center justify-between">
                                            <span>{{ $h->homework?->title ?? '—' }} <span class="text-xs text-gray-500">({{ $h->homework?->subject?->name ?? '—' }})</span></span>
                                            <span class="font-mono">{{ $h->marks_obtained }} / {{ $h->total_marks ?? '—' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
