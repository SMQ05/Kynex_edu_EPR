<x-filament-panels::page>

    {{-- ── Help banner: clarifies that Test and Interview are two distinct
         session types managed under the same page via the Type filter. --}}
    <div class="rounded-xl bg-sky-50 dark:bg-sky-900/20 ring-1 ring-sky-200 dark:ring-sky-800 px-4 py-3 mb-5 text-sm text-sky-900 dark:text-sky-200">
        <strong>How this page works:</strong>
        Use the <em>Type</em> filter to switch between <strong>Admission Tests</strong> (the entry exam slot) and <strong>Interviews</strong>. Each <em>Session</em> is a batch — a single date, time, and capacity that auto-assigns the next pending applicants. Click <em>New Session</em> to add a slot in either mode.
        @php
            try {
                $skipForAny = \App\Models\Tenant\AdmissionCriteria::where('is_active', true)
                    ->where('skip_interview_for_all', true)
                    ->exists();
            } catch (\Throwable $e) {
                $skipForAny = false;
            }
        @endphp
        @if($skipForAny)
            <div class="mt-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 ring-1 ring-amber-300 dark:ring-amber-700 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                ⚠️ One or more active admission criteria has <em>Skip interview for all applicants</em> turned ON. You don't need to schedule any Interview sessions for those classes — the auto-decision will run on the test &amp; previous-school components alone.
            </div>
        @endif
    </div>

    {{-- ── Filters ────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-5">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Academic Year</label>
            <select wire:model.live="filterYearId"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2 min-w-[160px]">
                @foreach($this->getYearOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
            <select wire:model.live="filterType"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2">
                <option value="test">Admission Tests</option>
                <option value="interview">Interviews</option>
            </select>
        </div>
    </div>

    {{-- ── Sessions Grid ───────────────────────────────────────────────── --}}
    @php $sessions = $this->getFilteredSessions(); @endphp

    @if($sessions->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <x-heroicon-o-calendar-days class="w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm">No sessions yet. Click <strong>New Session</strong> to create one.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sessions as $session)
                @php
                    $assigned = $session->assignedCount();
                    $cap      = $session->max_capacity;
                    $pct      = $cap > 0 ? min(100, round(($assigned / $cap) * 100)) : 0;
                    $barColor = $pct >= 100 ? 'bg-rose-500' : ($pct >= 80 ? 'bg-amber-400' : 'bg-emerald-500');
                    $dispatched = $session->credentials_dispatched_at;
                @endphp
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 p-4 flex flex-col gap-3">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ $filterType === 'test' ? 'Test Slot' : 'Interview Slot' }}
                            </span>
                            <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm mt-0.5">
                                {{ $session->schoolClass?->name ?? 'All Classes' }}
                            </p>
                        </div>
                        @if($pct >= 100)
                            <span class="shrink-0 text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full font-medium">Full</span>
                        @elseif($pct >= 80)
                            <span class="shrink-0 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Almost Full</span>
                        @endif
                    </div>

                    {{-- Test name --}}
                    @if($session->admissionTest)
                    <div class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                        <x-heroicon-o-document-text class="w-3.5 h-3.5 shrink-0" />
                        {{ $session->admissionTest->name }}
                    </div>
                    @endif

                    {{-- Date / Room --}}
                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-clock class="w-4 h-4 text-gray-400 shrink-0" />
                            {{ $session->scheduled_at->format('D, d M Y — H:i') }}
                        </div>
                        @if($session->room)
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400 shrink-0" />
                            {{ $session->room }}
                        </div>
                        @endif
                        @if($session->panel)
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-user-group class="w-4 h-4 text-gray-400 shrink-0" />
                            {{ $session->panel }}
                        </div>
                        @endif
                    </div>

                    {{-- Capacity Bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>{{ $assigned }} / {{ $cap }} students</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-2 rounded-full {{ $barColor }} transition-all"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>

                    {{-- Credential status --}}
                    <div class="flex items-center gap-1.5 text-xs">
                        @if($dispatched)
                            <x-heroicon-o-envelope-open class="w-4 h-4 text-emerald-500" />
                            <span class="text-emerald-600 dark:text-emerald-400">
                                Credentials sent {{ $dispatched->diffForHumans() }}
                            </span>
                        @elseif($filterType === 'test')
                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400" />
                            <span class="text-gray-400">
                                @if($session->isWithinCredentialWindow())
                                    Login emails dispatched to queue
                                @else
                                    Login emails will send 7 days before
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Delivery Report (test sessions only) --}}
                    @if($filterType === 'test' && $assigned > 0)
                        @php $rep = $this->getSessionDeliveryReport($session->id); @endphp
                        <div x-data="{ open: false }" class="mt-1 border-t border-gray-100 dark:border-gray-700 pt-2">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full flex items-center justify-between text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                <span class="flex items-center gap-1.5">
                                    <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                                    Delivery Report
                                </span>
                                <span class="flex items-center gap-1 text-[10px]">
                                    <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 px-1.5 py-0.5 rounded font-semibold">{{ count($rep['sent']) }} sent</span>
                                    @if(count($rep['pending']))
                                        <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 px-1.5 py-0.5 rounded font-semibold">{{ count($rep['pending']) }} pending</span>
                                    @endif
                                    @if(count($rep['no_email']))
                                        <span class="bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 px-1.5 py-0.5 rounded font-semibold">{{ count($rep['no_email']) }} no email</span>
                                    @endif
                                    @if(count($rep['admitted']))
                                        <span class="bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300 px-1.5 py-0.5 rounded font-semibold">{{ count($rep['admitted']) }} admitted</span>
                                    @endif
                                    <x-heroicon-m-chevron-down x-show="!open" class="w-3.5 h-3.5" />
                                    <x-heroicon-m-chevron-up x-show="open" class="w-3.5 h-3.5" x-cloak />
                                </span>
                            </button>

                            <div x-show="open" x-cloak x-transition class="mt-2 space-y-2 text-xs">
                                {{-- Sent --}}
                                @if(count($rep['sent']))
                                <div>
                                    <div class="font-semibold text-emerald-700 dark:text-emerald-400 mb-1">✓ Credentials sent ({{ count($rep['sent']) }})</div>
                                    <ul class="space-y-0.5 ml-1">
                                        @foreach($rep['sent'] as $r)
                                            <li class="text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">{{ $r['name'] }}</span>
                                                <span class="text-gray-400">— {{ $r['email'] }}</span>
                                                <span class="text-gray-400">@ {{ $r['sent_at'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                {{-- Pending --}}
                                @if(count($rep['pending']))
                                <div>
                                    <div class="font-semibold text-amber-700 dark:text-amber-400 mb-1">⧗ Pending — will send shortly ({{ count($rep['pending']) }})</div>
                                    <ul class="space-y-0.5 ml-1">
                                        @foreach($rep['pending'] as $r)
                                            <li class="text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">{{ $r['name'] }}</span>
                                                <span class="text-gray-400">— {{ $r['email'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                {{-- No email --}}
                                @if(count($rep['no_email']))
                                <div>
                                    <div class="font-semibold text-rose-700 dark:text-rose-400 mb-1">⚠ Missing email — fix on application then re-send ({{ count($rep['no_email']) }})</div>
                                    <ul class="space-y-0.5 ml-1">
                                        @foreach($rep['no_email'] as $r)
                                            <li class="text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">{{ $r['name'] }}</span>
                                                <span class="text-gray-400">— application {{ Str::limit($r['application_id'], 12) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                {{-- Admitted --}}
                                @if(count($rep['admitted']))
                                <div>
                                    <div class="font-semibold text-sky-700 dark:text-sky-400 mb-1">★ Already admitted — no test needed ({{ count($rep['admitted']) }})</div>
                                    <ul class="space-y-0.5 ml-1">
                                        @foreach($rep['admitted'] as $r)
                                            <li class="text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">{{ $r['name'] }}</span>
                                                <span class="text-gray-400">— {{ $r['email'] ?? '(no email)' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                {{-- Resend action --}}
                                @if(count($rep['pending']) > 0 || count($rep['sent']) > 0)
                                <button
                                    type="button"
                                    wire:click="resendMissingCredentials('{{ $session->id }}')"
                                    wire:confirm="Re-send credentials to all applicants in this session who don't have them yet?"
                                    class="mt-2 inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium"
                                >
                                    <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                                    Re-send pending credentials
                                </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($session->notes)
                    <p class="text-xs text-gray-400 italic border-t border-gray-100 dark:border-gray-700 pt-2 mt-auto">
                        {{ $session->notes }}
                    </p>
                    @endif

                </div>
            @endforeach
        </div>

        {{-- Summary row --}}
        <div class="mt-4 text-sm text-gray-500 text-right">
            {{ $sessions->count() }} session(s) &nbsp;·&nbsp;
            {{ $sessions->sum(fn ($s) => $s->assignedCount()) }} applicants assigned
        </div>
    @endif

</x-filament-panels::page>
