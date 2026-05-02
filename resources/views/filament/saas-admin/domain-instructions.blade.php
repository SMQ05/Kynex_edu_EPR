<div class="space-y-4 text-sm">
    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Step 1: Add a CNAME Record</h3>
        <div class="mt-1 rounded-lg bg-gray-50 p-3 font-mono text-xs dark:bg-gray-800">
            <div><span class="text-gray-500">Type:</span> CNAME</div>
            <div><span class="text-gray-500">Name:</span> {{ Str::before($info['domain'], '.') }}</div>
            <div><span class="text-gray-500">Value:</span> {{ $info['cname_record'] }}</div>
            <div><span class="text-gray-500">TTL:</span> 300</div>
        </div>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Step 2: Add a TXT Record (Verification)</h3>
        <div class="mt-1 rounded-lg bg-gray-50 p-3 font-mono text-xs dark:bg-gray-800">
            <div><span class="text-gray-500">Type:</span> TXT</div>
            <div><span class="text-gray-500">Name:</span> {{ $info['txt_record_name'] }}</div>
            <div><span class="text-gray-500">Value:</span> <span class="break-all">{{ $info['txt_record_value'] }}</span></div>
            <div><span class="text-gray-500">TTL:</span> 300</div>
        </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
        <strong>Note:</strong> DNS changes can take up to 10 minutes to propagate.
        After adding both records, click <strong>Verify Now</strong> on the domain row.
    </div>
</div>
