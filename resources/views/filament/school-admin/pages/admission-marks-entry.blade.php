<x-filament-panels::page>
    {{-- Applicant picker (independent of the form below; selecting reloads the page) --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-4 mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Pick an application
        </label>
        <select
            wire:model.live="applicationId"
            class="block w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700"
        >
            <option value="">— select —</option>
            @foreach($this->getApplicationOptions() as $id => $label)
                <option value="{{ $id }}" @selected($applicationId === $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @php
        $profile = $this->getProfile();
        $decision = $this->getDecisionSnapshot();
    @endphp

    @if($profile)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Read-only profile sidebar --}}
            <aside class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Applicant profile (read-only)</h3>
                    <dl class="space-y-2 text-sm">
                        @foreach($profile as $label => $value)
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="font-medium text-right">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Decision snapshot</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-semibold">{{ $decision['status'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Final %</dt><dd class="font-semibold">{{ $decision['final_percentage'] !== null ? number_format((float) $decision['final_percentage'], 2).'%' : '—' }}</dd></div>
                        @if(! empty($decision['auto_rejected']))
                            <div class="text-red-600 text-xs mt-2">Auto-rejected: {{ $decision['auto_reject_reason'] ?? '—' }}</div>
                        @endif
                        @if(! empty($decision['auto_decision_at']))
                            <div class="flex justify-between"><dt class="text-gray-500">Auto-decision at</dt><dd>{{ \Illuminate\Support\Carbon::parse($decision['auto_decision_at'])->format('d M Y, H:i') }}</dd></div>
                        @endif
                    </dl>

                    <hr class="my-3 border-gray-200 dark:border-gray-800">

                    @if($decision['criteria_present'])
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Criteria in effect</h4>
                        <dl class="space-y-1 text-xs">
                            <div class="flex justify-between"><dt>Weights (test/interview/prev)</dt><dd>{{ $decision['weights']['test'] }} / {{ $decision['weights']['interview'] }} / {{ $decision['weights']['previous'] }}</dd></div>
                            <div class="flex justify-between"><dt>Min test</dt><dd>{{ $decision['min_test'] ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Min interview</dt><dd>{{ $decision['min_interview'] ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Min final %</dt><dd>{{ $decision['min_final'] !== null ? $decision['min_final'].'%' : '—' }}</dd></div>
                        </dl>
                    @else
                        <div class="text-xs text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded p-2">
                            No admission criteria configured for this class/year — set one under Admissions → Admission Criteria so auto-decision can run.
                        </div>
                    @endif
                </div>
            </aside>

            {{-- Editable marks/dates form --}}
            <section class="lg:col-span-2">
                <form wire:submit.prevent="save">
                    {{ $this->form }}
                    <div class="mt-4 flex justify-end">
                        <x-filament::button type="submit" color="primary">
                            Save & evaluate
                        </x-filament::button>
                    </div>
                </form>
            </section>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-800 p-8 text-center text-gray-500">
            Pick an applicant from the dropdown above to start entering marks.
        </div>
    @endif
</x-filament-panels::page>
