<div class="space-y-3">
    @forelse($attempt->answers as $i => $answer)
        @php($q = $answer->question)
        <div class="rounded-lg ring-1 ring-gray-200 dark:ring-gray-800 p-4 bg-white dark:bg-gray-900">
            <div class="flex items-baseline justify-between mb-1">
                <div class="text-xs uppercase text-gray-500">
                    Q{{ $i + 1 }} · {{ $q?->type ?? '—' }} · max {{ $q?->marks ?? '—' }}
                </div>
                <div class="text-sm font-semibold">
                    {{ $answer->marks_awarded !== null ? number_format((float) $answer->marks_awarded, 2) : 'pending' }}
                </div>
            </div>
            <div class="font-medium mb-2">{{ $q?->question_text ?? '—' }}</div>

            @if($q && $q->type === 'mcq' && is_array($q->options))
                <div class="text-xs text-gray-500 mb-1">
                    Selected:
                    <span class="font-mono">{{ $answer->selected_option ?? '—' }}</span>
                    @if($answer->selected_option && isset($q->options[$answer->selected_option]))
                        — {{ $q->options[$answer->selected_option] }}
                    @endif
                </div>
                <div class="text-xs text-gray-500">
                    Correct: <span class="font-mono">{{ $q->correct_answer ?? '—' }}</span>
                    @if($q->correct_answer && isset($q->options[$q->correct_answer]))
                        — {{ $q->options[$q->correct_answer] }}
                    @endif
                </div>
            @else
                <div class="rounded bg-gray-50 dark:bg-gray-800 p-3 whitespace-pre-line text-sm">
                    {{ $answer->answer_text ?: '—' }}
                </div>
                @if($q && $q->correct_answer)
                    <div class="text-xs text-gray-500 mt-2">Reference: {{ $q->correct_answer }}</div>
                @endif
            @endif
        </div>
    @empty
        <div class="text-sm text-gray-500">No answers recorded.</div>
    @endforelse
</div>
