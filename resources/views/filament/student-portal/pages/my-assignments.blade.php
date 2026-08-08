{{--
    Assignments in three states: to do, submitted, graded.

    Every list comes from assignmentsQuery(), which is scoped to the signed-in
    student's own class and section in the page class.
--}}
<x-filament-panels::page>
@include('filament.portal.styles')

    @php $counts = $this->counts; @endphp

    <div class="sp-grid-stats">
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value {{ $counts['todo'] > 0 ? 'sp-warn' : 'sp-good' }}">{{ $counts['todo'] }}</div>
            <div class="sp-stat__label">To do<span class="sp-stat__hint">not handed in</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-teal">{{ $counts['submitted'] }}</div>
            <div class="sp-stat__label">Submitted<span class="sp-stat__hint">awaiting marking</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-good">{{ $counts['graded'] }}</div>
            <div class="sp-stat__label">Graded<span class="sp-stat__hint">feedback available</span></div>
        </x-filament::section>
    </div>

    {{-- ── To do ──────────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">To do</x-slot>
        <x-slot name="description">Soonest deadline first. Overdue work is flagged.</x-slot>

        @forelse ($this->todo as $hw)
            @php
                $due = $hw->due_date ? \Illuminate\Support\Carbon::parse($hw->due_date) : null;
                $overdue = $due && $due->isPast();
                $open = $this->submittingFor === $hw->id;
            @endphp
            <div style="padding:.75rem 0;border-bottom:1px solid #f3f4f6;">
                <div class="sp-row" style="border-bottom:0;padding:0;">
                    <div style="min-width:0;">
                        <div class="sp-row__title">{{ $hw->title }}</div>
                        <div class="sp-row__meta">
                            {{ $hw->subject?->name ?? 'General' }}
                            @if ($hw->teacher) · {{ $hw->teacher->name }} @endif
                            @if ($hw->total_marks) · {{ $hw->total_marks }} marks @endif
                        </div>
                        @if (filled($hw->description))
                            <div class="sp-row__meta" style="margin-top:.375rem;white-space:pre-line;">{{ \Illuminate\Support\Str::limit($hw->description, 260) }}</div>
                        @endif
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem;flex-shrink:0;">
                        <span class="sp-badge {{ $overdue ? 'sp-badge--late' : 'sp-badge--due' }}">
                            {{ $due ? ($overdue ? 'Overdue ' . $due->diffForHumans(short: true) : 'Due ' . $due->format('M j')) : 'No due date' }}
                        </span>
                        @unless ($open)
                            <x-filament::button size="xs" wire:click="startSubmission('{{ $hw->id }}')">
                                Hand in
                            </x-filament::button>
                        @endunless
                    </div>
                </div>

                @if ($open)
                    <form wire:submit="submit" style="margin-top:.75rem;">
                        <textarea
                            wire:model="submissionText"
                            rows="4"
                            placeholder="Type your answer here…"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        ></textarea>
                        <div style="display:flex;gap:.5rem;margin-top:.5rem;">
                            <x-filament::button type="submit" size="sm" wire:loading.attr="disabled" wire:target="submit">
                                <span wire:loading.remove wire:target="submit">Submit</span>
                                <span wire:loading wire:target="submit">Submitting…</span>
                            </x-filament::button>
                            <x-filament::button size="sm" color="gray" wire:click="cancelSubmission">Cancel</x-filament::button>
                        </div>
                        <p class="sp-row__meta" style="margin-top:.375rem;">
                            Once handed in this cannot be edited.
                        </p>
                    </form>
                @endif
            </div>
        @empty
            <p class="sp-empty">Nothing outstanding. You are caught up.</p>
        @endforelse
    </x-filament::section>

    <div class="sp-grid-two">
        {{-- ── Submitted ──────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Submitted</x-slot>
            <x-slot name="description">Handed in, waiting on your teacher.</x-slot>

            @forelse ($this->submitted as $hw)
                @php $sub = $this->submissionFor($hw->id); @endphp
                <div class="sp-row">
                    <div style="min-width:0;">
                        <div class="sp-row__title truncate">{{ $hw->title }}</div>
                        <div class="sp-row__meta">
                            {{ $hw->subject?->name ?? 'General' }}
                            @if ($sub?->submitted_at)
                                · sent {{ \Illuminate\Support\Carbon::parse($sub->submitted_at)->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                    <span class="sp-badge sp-badge--wait">Awaiting mark</span>
                </div>
            @empty
                <p class="sp-empty">Nothing waiting to be marked.</p>
            @endforelse
        </x-filament::section>

        {{-- ── Graded ─────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Graded</x-slot>
            <x-slot name="description">Marks and feedback from your teacher.</x-slot>

            @forelse ($this->graded as $hw)
                @php
                    $sub = $this->submissionFor($hw->id);
                    $out = $sub?->marks_obtained;
                    $of  = $sub?->total_marks ?: $hw->total_marks;
                    $pct = ($out !== null && $of) ? round($out / $of * 100) : null;
                @endphp
                <div class="sp-row" style="flex-direction:column;align-items:stretch;">
                    <div style="display:flex;justify-content:space-between;gap:.75rem;">
                        <div style="min-width:0;">
                            <div class="sp-row__title truncate">{{ $hw->title }}</div>
                            <div class="sp-row__meta">{{ $hw->subject?->name ?? 'General' }}</div>
                        </div>
                        <span class="sp-badge {{ $pct !== null && $pct >= 60 ? 'sp-badge--ok' : 'sp-badge--late' }}">
                            @if ($out !== null && $of)
                                {{ $out }}/{{ $of }}@if ($pct !== null) · {{ $pct }}%@endif
                            @else
                                {{ $sub?->grade ?? 'Marked' }}
                            @endif
                        </span>
                    </div>
                    @if (filled($sub?->feedback))
                        <div class="sp-row__meta" style="margin-top:.5rem;padding:.5rem .625rem;border-radius:.5rem;background:#f9fafb;white-space:pre-line;">
                            <strong>Feedback:</strong> {{ $sub->feedback }}
                        </div>
                    @endif
                </div>
            @empty
                <p class="sp-empty">Nothing graded yet.</p>
            @endforelse
        </x-filament::section>
    </div>
</x-filament-panels::page>
