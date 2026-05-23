<div class="space-y-3 p-1">
    @if(!$attempt->violation_log)
        <p class="text-sm text-gray-500">No violation log recorded.</p>
    @else
        <div class="flex items-center gap-3 mb-3">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800">
                {{ $attempt->violation_count }} violation(s)
            </span>
            @if($attempt->proctoring_cancelled_at)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-600 text-white">
                    Exam cancelled at {{ $attempt->proctoring_cancelled_at->format('d M, H:i') }}
                </span>
            @endif
        </div>

        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="text-left border-b border-gray-200">
                    <th class="pb-2 pr-4 font-medium text-gray-600">#</th>
                    <th class="pb-2 pr-4 font-medium text-gray-600">Type</th>
                    <th class="pb-2 font-medium text-gray-600">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attempt->violation_log as $i => $v)
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4 text-gray-400">{{ $i + 1 }}</td>
                        <td class="py-2 pr-4">
                            <span class="font-medium">
                                @if($v['type'] === 'tab_switch') Tab switch / minimise
                                @elseif($v['type'] === 'window_blur') Window focus lost
                                @else {{ $v['type'] }}
                                @endif
                            </span>
                        </td>
                        <td class="py-2 text-gray-500">{{ \Carbon\Carbon::parse($v['at'])->format('d M Y, H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
