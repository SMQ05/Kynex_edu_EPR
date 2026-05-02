<x-filament-panels::page>
    @php
        $s = $this->record;
        $att = $this->attendanceSummary;
    @endphp

    <div class="space-y-6">
        {{-- Header card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    @if($s->profile_photo_path)
                        <img src="{{ asset('storage/'.$s->profile_photo_path) }}" alt="" class="w-28 h-28 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                    @else
                        <div class="w-28 h-28 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 text-white flex items-center justify-center text-3xl font-bold">
                            {{ strtoupper(substr($s->first_name ?? '?', 0, 1) . substr($s->last_name ?? '', 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold">{{ $s->full_name }}</h2>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Adm No: <span class="font-mono font-medium">{{ $s->admission_number }}</span>
                                @if($s->registration_number)
                                    &middot; Reg No: <span class="font-mono font-medium text-emerald-600 dark:text-emerald-400">{{ $s->registration_number }}</span>
                                @endif
                                @if($s->roll_number)
                                    &middot; Roll: <span class="font-mono font-medium">{{ $s->roll_number }}</span>
                                @endif
                                &middot; Class: <span class="font-medium">{{ $s->schoolClass?->name ?? '—' }}{{ $s->section ? ' / '.$s->section->name : '' }}</span>
                                &middot; Year: <span class="font-medium">{{ $s->academicYear?->name ?? '—' }}</span>
                            </div>
                        </div>
                        <div>
                            @php
                                $statusColors = [
                                    'enrolled'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                    'left'      => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                                    'graduated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                    'expelled'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    'suspended' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                ];
                                $key = $s->status?->value ?? (string) $s->status;
                            @endphp
                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$key] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">DOB</div>
                            <div class="font-medium">{{ $s->date_of_birth?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Gender</div>
                            <div class="font-medium capitalize">{{ $s->gender?->value ?? $s->gender ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Blood Group</div>
                            <div class="font-medium">{{ $s->blood_group?->value ?? $s->blood_group ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Phone</div>
                            <div class="font-medium">{{ $s->phone ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Email</div>
                            <div class="font-medium truncate">{{ $s->email ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Campus</div>
                            <div class="font-medium">{{ $s->campus?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Category</div>
                            <div class="font-medium">{{ $s->category?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Admitted</div>
                            <div class="font-medium">{{ $s->admission_date?->format('d M Y') ?? '—' }}</div>
                        </div>
                    </div>

                    @if($s->address)
                        <div class="mt-4 text-sm">
                            <div class="text-gray-500 dark:text-gray-400 text-xs uppercase">Address</div>
                            <div>{{ $s->address }}{{ $s->city ? ', '.$s->city : '' }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500 uppercase">Attendance</div>
                <div class="text-2xl font-bold mt-1">
                    @if($att['percent'] !== null)
                        {{ $att['percent'] }}%
                    @else
                        <span class="text-gray-400 text-base">No data</span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ $att['present'] }} / {{ $att['total'] }} days</div>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500 uppercase">Exams Taken</div>
                <div class="text-2xl font-bold mt-1">{{ count($this->performanceByExam) }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $this->examMarks->count() }} subject marks recorded</div>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500 uppercase">Homework Graded</div>
                <div class="text-2xl font-bold mt-1">{{ $this->homeworkMarks->count() }}</div>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs text-gray-500 uppercase">Behavior Incidents</div>
                <div class="text-2xl font-bold mt-1 {{ $this->behaviorIncidents->count() > 0 ? 'text-amber-600' : '' }}">{{ $this->behaviorIncidents->count() }}</div>
            </div>
        </div>

        {{-- Exam performance --}}
        @if(! empty($this->performanceByExam))
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Exam Performance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Exam</th>
                                <th class="px-4 py-3 font-medium">Subjects</th>
                                <th class="px-4 py-3 font-medium">Obtained / Total</th>
                                <th class="px-4 py-3 font-medium">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->performanceByExam as $e)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $e['name'] }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        @foreach($e['subjects'] as $sub)
                                            <span class="inline-block px-2 py-0.5 mr-1 mb-1 rounded ring-1 ring-gray-200 dark:ring-gray-700">
                                                {{ $sub['subject'] }}: {{ $sub['is_absent'] ? 'AB' : ($sub['obtained'] ?? '—') }}/{{ $sub['full'] }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 font-mono">{{ $e['obtained'] }} / {{ $e['total'] }}</td>
                                    <td class="px-4 py-3">
                                        @if($e['percentage'] !== null)
                                            <span class="font-semibold {{ $e['percentage'] >= 75 ? 'text-emerald-600' : ($e['percentage'] >= 40 ? 'text-amber-600' : 'text-red-600') }}">
                                                {{ $e['percentage'] }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Homework --}}
        @if($this->homeworkMarks->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Homework / Assignment Marks (last 50)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Title</th>
                                <th class="px-4 py-3 font-medium">Subject</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Marks</th>
                                <th class="px-4 py-3 font-medium">Graded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->homeworkMarks as $hw)
                                <tr>
                                    <td class="px-4 py-3">{{ $hw->homework?->title ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $hw->homework?->subject?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs rounded ring-1 ring-gray-200 dark:ring-gray-700">
                                            {{ ucfirst(str_replace('_', ' ', $hw->homework?->type?->value ?? $hw->homework?->type ?? '—')) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono">{{ $hw->marks_obtained }} / {{ $hw->total_marks ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $hw->graded_at?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Behavior --}}
        @if($this->behaviorIncidents->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Behavior Incidents (last 20)</h3>
                <div class="space-y-2">
                    @foreach($this->behaviorIncidents as $incident)
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ $incident->title ?? ucfirst(str_replace('_', ' ', $incident->type?->value ?? $incident->type ?? 'Incident')) }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $incident->incident_date?->format('d M Y') ?? '' }}
                                        @if($incident->severity)
                                            · Severity: <span class="font-medium">{{ ucfirst($incident->severity?->value ?? $incident->severity) }}</span>
                                        @endif
                                    </div>
                                    @if($incident->description)
                                        <div class="text-sm mt-2">{{ $incident->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Guardians --}}
        @if($s->guardians->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Guardians</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($s->guardians as $g)
                        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ $g->name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $g->relationship ?? $g->guardian_type?->value ?? '—' }}</div>
                                </div>
                                @if($g->is_primary_contact)
                                    <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded">Primary</span>
                                @endif
                            </div>
                            <div class="mt-3 text-sm space-y-1">
                                @if($g->phone) <div>📞 {{ $g->phone }}</div> @endif
                                @if($g->email) <div>✉️ {{ $g->email }}</div> @endif
                                @if($g->occupation) <div class="text-gray-500">{{ $g->occupation }}</div> @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Certificates --}}
        @if($this->certificates->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Certificates Issued</h3>
                <ul class="space-y-1 text-sm">
                    @foreach($this->certificates as $cert)
                        <li class="flex items-center justify-between">
                            <span>{{ $cert->template?->name ?? 'Certificate' }} <span class="text-xs text-gray-500 ml-2 font-mono">{{ $cert->certificate_number }}</span></span>
                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($cert->issued_date)?->format('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! $att['total'] && $this->examMarks->isEmpty() && $this->homeworkMarks->isEmpty() && $this->behaviorIncidents->isEmpty())
            <div class="fi-section rounded-xl bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700 p-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No academic, attendance, or behaviour data recorded for this student yet.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
