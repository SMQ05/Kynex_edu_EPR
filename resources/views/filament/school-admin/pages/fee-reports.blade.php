<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            View fee payment reports. Only paid, partial, and refunded fees are shown. Use "Request Refund" to initiate refund approvals.
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
