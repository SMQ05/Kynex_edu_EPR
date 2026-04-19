<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Action Type</p>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->action_type }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->status->value }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</p>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->tenant_id ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</p>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('M d, Y H:i') }}</p>
        </div>
    </div>

    @if ($record->admin_note)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Admin Note</p>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->admin_note }}</p>
        </div>
    @endif

    @if ($payload && count($payload) > 0)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Payload Data</p>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left font-medium text-gray-500 dark:text-gray-400 pr-4 pb-2">Key</th>
                            <th class="text-left font-medium text-gray-500 dark:text-gray-400 pb-2">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payload as $key => $value)
                            <tr>
                                <td class="pr-4 py-1 text-gray-700 dark:text-gray-300 font-mono">{{ $key }}</td>
                                <td class="py-1 text-gray-900 dark:text-white">
                                    @if (is_array($value))
                                        <pre class="text-xs">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No payload data.</p>
    @endif
</div>
