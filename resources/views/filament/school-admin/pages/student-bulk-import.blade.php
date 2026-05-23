<x-filament-panels::page>
    @php $ref = $this->referenceLists(); @endphp

    {{-- Step 1: Get the template --}}
    <div class="rounded-xl bg-blue-50 p-5 ring-1 ring-blue-200 dark:bg-blue-950/30 dark:ring-blue-800/40">
        <div class="flex items-start gap-3">
            <div class="text-2xl">📥</div>
            <div class="flex-1 text-sm text-blue-900 dark:text-blue-200">
                <strong>Step 1 — Download the template.</strong>
                The CSV has every column the system understands, plus one example row. Click
                <strong>Download CSV Template</strong> at the top right, fill it in Excel / Google Sheets,
                save as CSV (UTF-8), then come back to upload it.
            </div>
        </div>
    </div>

    {{-- Step 2: Reference values --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Step 2 — Use these exact names in the CSV</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            class_name, section_name, academic_year, campus_name and category_name are matched by
            <strong>name</strong>. Spelling must match (case-insensitive). Create any missing classes / years
            from their respective admin pages first.
        </p>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">class_name</div>
                <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5 max-h-40 overflow-y-auto rounded bg-gray-50 dark:bg-gray-800 p-2">
                    @forelse ($ref['classes'] as $c)
                        <li>· {{ $c }}</li>
                    @empty
                        <li class="italic text-gray-400">No classes yet — create them under Academic Setup.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">section_name (Class / Section)</div>
                <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5 max-h-40 overflow-y-auto rounded bg-gray-50 dark:bg-gray-800 p-2">
                    @forelse ($ref['sections'] as $s)
                        <li>· {{ $s }}</li>
                    @empty
                        <li class="italic text-gray-400">No sections yet.</li>
                    @endforelse
                </ul>
                <div class="mt-1 text-[10px] text-gray-500">In the CSV use just the section name (e.g. "A").</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">academic_year</div>
                <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5 max-h-40 overflow-y-auto rounded bg-gray-50 dark:bg-gray-800 p-2">
                    @forelse ($ref['years'] as $y)
                        <li>· {{ $y }}</li>
                    @empty
                        <li class="italic text-gray-400">No years configured.</li>
                    @endforelse
                </ul>
                <div class="mt-1 text-[10px] text-gray-500">Leave blank to use the current year.</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">campus_name</div>
                <ul class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5 max-h-40 overflow-y-auto rounded bg-gray-50 dark:bg-gray-800 p-2">
                    @forelse ($ref['campuses'] as $c)
                        <li>· {{ $c }}</li>
                    @empty
                        <li class="italic text-gray-400">No campuses yet.</li>
                    @endforelse
                </ul>
                <div class="mt-1 text-[10px] text-gray-500">Leave blank if your school has only one campus.</div>
            </div>
        </div>
    </div>

    {{-- Step 3: Upload --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Step 3 — Upload your CSV</h2>
        <form method="POST" action="{{ route('admin.bulk-import.run') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSV file</label>
                <input
                    type="file"
                    name="csv_file"
                    accept=".csv,.txt"
                    required
                    class="block w-full text-sm text-gray-700 dark:text-gray-300
                           file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700
                           hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300"
                >
            </div>
            <div>
                <x-filament::button type="submit" color="success" icon="heroicon-o-cloud-arrow-up">
                    Run import
                </x-filament::button>
            </div>
        </form>
    </div>

    {{-- Import error banner --}}
    @if (session('import_error'))
        <div class="rounded-xl bg-rose-50 p-4 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:ring-rose-800/40">
            <p class="text-sm font-medium text-rose-800 dark:text-rose-300">{{ session('import_error') }}</p>
        </div>
    @endif

    {{-- Step 4: Result --}}
    @if (session('import_result'))
        @php $r = session('import_result'); @endphp
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Last import result</h2>

            <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-md bg-emerald-50 p-3 ring-1 ring-emerald-200">
                    <div class="text-xs uppercase text-emerald-800">Created</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-800">{{ $r['created'] }}</div>
                </div>
                <div class="rounded-md bg-blue-50 p-3 ring-1 ring-blue-200">
                    <div class="text-xs uppercase text-blue-800">Updated</div>
                    <div class="mt-1 text-2xl font-bold text-blue-800">{{ $r['updated'] }}</div>
                </div>
                <div class="rounded-md bg-amber-50 p-3 ring-1 ring-amber-200">
                    <div class="text-xs uppercase text-amber-800">Skipped</div>
                    <div class="mt-1 text-2xl font-bold text-amber-800">{{ $r['skipped'] }}</div>
                </div>
                <div class="rounded-md bg-rose-50 p-3 ring-1 ring-rose-200">
                    <div class="text-xs uppercase text-rose-800">Errors</div>
                    <div class="mt-1 text-2xl font-bold text-rose-800">{{ count($r['errors'] ?? []) }}</div>
                </div>
            </div>

            @if (! empty($r['errors']))
                <h3 class="mt-5 text-sm font-semibold text-rose-700">Rows that failed</h3>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="text-gray-500">
                            <tr class="border-b">
                                <th class="py-1 text-left">Row #</th>
                                <th class="py-1 text-left">Name</th>
                                <th class="py-1 text-left">Class</th>
                                <th class="py-1 text-left">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($r['errors'] as $e)
                                <tr>
                                    <td class="py-1 font-mono">{{ $e['row'] }}</td>
                                    <td class="py-1">
                                        {{ trim(($e['data']['first_name'] ?? '') . ' ' . ($e['data']['last_name'] ?? '')) ?: '—' }}
                                    </td>
                                    <td class="py-1">{{ $e['data']['class_name'] ?? '—' }}</td>
                                    <td class="py-1 text-rose-700">{{ $e['error'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Field reference --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Column reference</h2>
        <p class="mt-1 text-xs text-gray-500">Required columns are marked with *. Everything else is optional. Re-uploading the same CSV updates students whose <code>admission_number</code> already exists.</p>
        <div class="mt-3 grid md:grid-cols-2 gap-x-6 gap-y-1 text-xs">
            <div>
                <div class="font-semibold text-gray-700 mt-2 mb-1">Identity (required)</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>first_name</code> *</li>
                    <li><code>last_name</code> *</li>
                </ul>

                <div class="font-semibold text-gray-700 mt-3 mb-1">Placement (required)</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>class_name</code> * — must match exactly</li>
                    <li><code>section_name</code> — within the class</li>
                    <li><code>academic_year</code> — defaults to current year if blank</li>
                    <li><code>campus_name</code> — auto if school has one campus</li>
                </ul>

                <div class="font-semibold text-gray-700 mt-3 mb-1">Optional placement</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>category_name</code> · <code>admission_number</code> · <code>roll_number</code></li>
                    <li><code>admission_date</code> (YYYY-MM-DD or DD/MM/YYYY)</li>
                    <li><code>status</code> (enrolled / pending_admission)</li>
                </ul>

                <div class="font-semibold text-gray-700 mt-3 mb-1">Personal</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>date_of_birth</code> · <code>gender</code> (male/female/other)</li>
                    <li><code>blood_group</code> · <code>religion</code> · <code>nationality</code></li>
                    <li><code>student_phone</code> · <code>student_email</code></li>
                    <li><code>address</code> · <code>city</code></li>
                    <li><code>previous_school</code> · <code>special_needs_notes</code> · <code>medical_notes</code></li>
                </ul>
            </div>
            <div>
                <div class="font-semibold text-gray-700 mt-2 mb-1">Father (option A — preferred)</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>father_name</code> · <code>father_phone</code> *</li>
                    <li><code>father_email</code> (parent portal login)</li>
                    <li><code>father_whatsapp</code> · <code>father_occupation</code> · <code>father_cnic</code></li>
                </ul>

                <div class="font-semibold text-gray-700 mt-3 mb-1">Mother (option A — preferred)</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>mother_name</code> · <code>mother_phone</code></li>
                    <li><code>mother_email</code> (parent portal login)</li>
                    <li><code>mother_whatsapp</code> · <code>mother_occupation</code> · <code>mother_cnic</code></li>
                </ul>

                <div class="font-semibold text-gray-700 mt-3 mb-1">Single guardian (option B — fallback)</div>
                <ul class="text-gray-600 space-y-0.5">
                    <li><code>guardian_name</code> · <code>guardian_relationship</code> (father/mother/guardian)</li>
                    <li><code>guardian_phone</code> *</li>
                    <li><code>guardian_email</code> · <code>guardian_whatsapp</code></li>
                    <li><code>guardian_occupation</code> · <code>guardian_cnic</code></li>
                </ul>

                <div class="mt-3 rounded-md bg-amber-50 p-3 text-amber-900 ring-1 ring-amber-200">
                    <strong>At least one of:</strong> <code>father_phone</code>, <code>mother_phone</code>, or <code>guardian_phone</code> is required.
                    Leave the un-used set blank.
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
