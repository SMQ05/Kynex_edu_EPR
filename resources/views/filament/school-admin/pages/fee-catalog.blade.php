<x-filament-panels::page>
    @php $catalog = $this->catalog(); @endphp

    @if (empty($catalog))
        <div class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-4xl">📂</div>
            <h2 class="mt-3 text-lg font-semibold text-gray-950 dark:text-white">No fee groups yet</h2>
            <p class="mt-1 text-sm text-gray-500">Click <strong>+ New Group</strong> above to create one (e.g. Tuition, Transport).</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($catalog as $row)
                @php $g = $row['group']; $types = $row['types']; @endphp
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/10 px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="text-xl">📁</div>
                            <div>
                                <div class="font-semibold text-gray-950 dark:text-white">{{ $g->name }}</div>
                                @if ($g->description ?? null)
                                    <div class="text-xs text-gray-500">{{ $g->description }}</div>
                                @endif
                            </div>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">
                                {{ count($types) }} {{ count($types) === 1 ? 'type' : 'types' }}
                            </span>
                        </div>
                        @if ($g->id)
                            <button
                                type="button"
                                wire:click="deleteGroup('{{ $g->id }}')"
                                wire:confirm="Delete this group? Only works if it has no fee types inside."
                                class="text-xs text-rose-600 hover:underline"
                            >Delete group</button>
                        @endif
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @if (count($types) === 0)
                            <div class="px-5 py-6 text-sm text-gray-400 italic">No fee types yet — add one with the <strong>+ Add Fee Type</strong> button above.</div>
                        @else
                            @foreach ($types as $t)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-primary-50 text-primary-600 flex items-center justify-center text-xs font-semibold dark:bg-primary-950">
                                            {{ strtoupper(\Illuminate\Support\Str::substr($t->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $t->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                @if ($t->is_recurring)
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] uppercase tracking-wide font-semibold">Monthly</span>
                                                @else
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] uppercase tracking-wide font-semibold">One-time</span>
                                                @endif
                                                @if ($t->fee_masters_count > 0)
                                                    · {{ $t->fee_masters_count }} fee structure {{ $t->fee_masters_count === 1 ? 'row' : 'rows' }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="toggleRecurring('{{ $t->id }}')"
                                            class="text-xs text-primary-600 hover:underline"
                                            title="Toggle monthly / one-time"
                                        >Toggle type</button>
                                        <button
                                            type="button"
                                            wire:click="deleteType('{{ $t->id }}')"
                                            wire:confirm="Delete this fee type? Only works if it has no fee-structure rows or invoices."
                                            class="text-xs text-rose-600 hover:underline"
                                        >Delete</button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 rounded-xl bg-blue-50 p-5 ring-1 ring-blue-200 dark:bg-blue-950/30 dark:ring-blue-800/40">
        <div class="text-sm text-blue-900 dark:text-blue-200">
            <strong>How fees work in 3 steps:</strong>
            <ol class="mt-1 ml-5 list-decimal space-y-1 text-xs">
                <li>Define groups + types here. Mark types as monthly (recurring) or one-time.</li>
                <li>In <strong>Fees → Fee Structure</strong>, set the price for each <em>Class × Fee Type × Year</em>.</li>
                <li>In <strong>Fees → Generate Fees</strong>, roll out the bills monthly or charge a one-time fee to a class / school.</li>
            </ol>
        </div>
    </div>
</x-filament-panels::page>
