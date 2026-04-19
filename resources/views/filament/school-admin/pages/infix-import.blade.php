<x-filament-panels::page>
    <style>
        .infix-warning-banner { border-radius: 0.75rem; background-color: #fffbeb; border: 1px solid #fde68a; padding: 1.5rem; display: flex; align-items: flex-start; gap: 1rem; }
        .infix-warning-banner svg { width: 2rem; height: 2rem; flex-shrink: 0; color: #f59e0b; }
        .infix-warning-banner h3 { font-size: 1.125rem; font-weight: 700; color: #92400e; }
        .infix-warning-banner ul { margin-top: 0.5rem; font-size: 0.875rem; color: #b45309; display: flex; flex-direction: column; gap: 0.25rem; }
        .infix-warning-banner code { background-color: #fef3c7; padding: 0 0.25rem; border-radius: 0.25rem; }
        .infix-form-actions { margin-top: 1rem; display: flex; gap: 0.75rem; }
        .infix-progress-box { border-radius: 0.75rem; border: 1px solid; padding: 1.5rem; }
        .infix-progress-box.completed { background-color: #f0fdf4; border-color: #bbf7d0; }
        .infix-progress-box.failed { background-color: #fef2f2; border-color: #fecaca; }
        .infix-progress-box.running { background-color: #eff6ff; border-color: #bfdbfe; }
        .infix-progress-box.default-status { background-color: var(--gray-50, #f9fafb); border-color: var(--gray-200, #e5e7eb); }
        .infix-progress-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .infix-progress-header h3 { font-size: 1.125rem; font-weight: 700; }
        .infix-progress-header h3.completed { color: #166534; }
        .infix-progress-header h3.failed { color: #991b1b; }
        .infix-progress-header h3.running { color: #1e40af; }
        .infix-progress-header h3.default-status { color: var(--gray-800, #1f2937); }
        .infix-progress-header .actions { display: flex; gap: 0.5rem; }
        .infix-progress-bar-bg { width: 100%; background-color: var(--gray-200, #e5e7eb); border-radius: 9999px; height: 0.75rem; margin-bottom: 1rem; }
        .infix-progress-bar { height: 0.75rem; border-radius: 9999px; transition: all 0.5s; }
        .infix-progress-bar.completed { background-color: #22c55e; }
        .infix-progress-bar.failed { background-color: #ef4444; }
        .infix-progress-bar.default-status { background-color: #3b82f6; }
        .infix-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem; }
        @media (min-width: 768px) { .infix-stats-grid { grid-template-columns: repeat(4, 1fr); } }
        .infix-stat-card { background-color: white; border-radius: 0.5rem; padding: 0.75rem; border: 1px solid var(--gray-200, #e5e7eb); text-align: center; }
        .infix-stat-card .emoji { font-size: 1.25rem; }
        .infix-stat-card .value { font-size: 1.5rem; font-weight: 700; color: var(--gray-900, #111827); }
        .infix-stat-card .label { font-size: 0.75rem; color: var(--gray-500, #6b7280); }
        .infix-log-details { margin-top: 1rem; }
        .infix-log-details summary { cursor: pointer; font-size: 0.875rem; font-weight: 500; color: var(--gray-600, #4b5563); }
        .infix-log-details summary:hover { color: var(--gray-800, #1f2937); }
        .infix-log-output { margin-top: 0.5rem; background-color: #111827; color: #4ade80; border-radius: 0.5rem; padding: 1rem; font-size: 0.75rem; font-family: monospace; max-height: 16rem; overflow-y: auto; }
        .infix-mapping-details { border-radius: 0.75rem; border: 1px solid var(--gray-200, #e5e7eb); padding: 1.5rem; background-color: var(--gray-50, #f9fafb); }
        .infix-mapping-details summary { cursor: pointer; font-size: 1.125rem; font-weight: 700; color: var(--gray-800, #1f2937); }
        .infix-mapping-table { width: 100%; font-size: 0.875rem; margin-top: 1rem; overflow-x: auto; border-collapse: collapse; }
        .infix-mapping-table thead tr { background-color: var(--gray-100, #f3f4f6); }
        .infix-mapping-table th { text-align: left; padding: 0.5rem; font-weight: 600; }
        .infix-mapping-table td { padding: 0.5rem; border-top: 1px solid var(--gray-200, #e5e7eb); }
        .infix-mapping-table code { font-family: monospace; font-size: 0.8125rem; }
    </style>

    <div style="display: flex; flex-direction: column; gap: 2rem;">

        {{-- Warning Banner --}}
        <div class="infix-warning-banner">
            <div style="flex-shrink: 0;">
                <svg style="width: 2rem; height: 2rem; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div>
                <h3>Important Notice</h3>
                <ul>
                    <li>• This tool imports data from an <strong>InfixEdu</strong> MySQL database into KynexEdu.</li>
                    <li>• It is recommended to <strong>backup your data</strong> before running the import.</li>
                    <li>• Existing records with matching names/emails will be <strong>skipped</strong> (not overwritten).</li>
                    <li>• Imported users will have the default password: <code>changeme123</code></li>
                    <li>• The InfixEdu MySQL database must be accessible from this server.</li>
                </ul>
            </div>
        </div>

        {{-- Connection Form --}}
        <form wire:submit.prevent="testConnection">
            {{ $this->connectionForm }}
            <div class="infix-form-actions">
                <x-filament::button type="submit" color="gray" icon="heroicon-o-signal">
                    Test Connection
                </x-filament::button>
                <x-filament::button wire:click="runImportSync" color="primary" icon="heroicon-o-arrow-down-tray">
                    Run Import Now
                </x-filament::button>
                <x-filament::button wire:click="startImport" color="warning" icon="heroicon-o-queue-list">
                    Queue Import (Background)
                </x-filament::button>
            </div>
        </form>

        {{-- Progress Section --}}
        @if($importProgress)
            @php
                $statusClass = match($importProgress['status'] ?? '') {
                    'completed' => 'completed',
                    'failed' => 'failed',
                    'running' => 'running',
                    default => 'default-status',
                };
            @endphp
            <div class="infix-progress-box {{ $statusClass }}">
                <div class="infix-progress-header">
                    <h3 class="{{ $statusClass }}">
                        Import Status: {{ ucfirst($importProgress['status'] ?? 'Unknown') }}
                    </h3>
                    <div class="actions">
                        <x-filament::button wire:click="refreshProgress" size="sm" color="gray" icon="heroicon-o-arrow-path">
                            Refresh
                        </x-filament::button>
                        @if(in_array($importProgress['status'] ?? '', ['completed', 'failed']))
                            <x-filament::button wire:click="clearProgress" size="sm" color="danger" icon="heroicon-o-trash">
                                Clear
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                <p style="font-size: 0.875rem; margin-bottom: 0.75rem;">{{ $importProgress['message'] ?? '' }}</p>

                @if(($importProgress['percent'] ?? 0) > 0)
                    <div class="infix-progress-bar-bg">
                        <div class="infix-progress-bar {{ $statusClass }}" style="width: {{ $importProgress['percent'] }}%"></div>
                    </div>
                @endif

                {{-- Import Results --}}
                @if(isset($importProgress['result']['stats']))
                    @php $stats = $importProgress['result']['stats']; @endphp
                    <div class="infix-stats-grid">
                        @foreach([
                            'students' => ['label' => 'Students', 'icon' => '🎓'],
                            'staff' => ['label' => 'Staff', 'icon' => '👨‍🏫'],
                            'classes' => ['label' => 'Classes', 'icon' => '🏫'],
                            'sections' => ['label' => 'Sections', 'icon' => '📋'],
                            'subjects' => ['label' => 'Subjects', 'icon' => '📚'],
                            'departments' => ['label' => 'Departments', 'icon' => '🏢'],
                            'guardians' => ['label' => 'Guardians', 'icon' => '👨‍👩‍👧'],
                            'skipped' => ['label' => 'Skipped', 'icon' => '⏭️'],
                            'errors' => ['label' => 'Errors', 'icon' => '❌'],
                        ] as $key => $info)
                            <div class="infix-stat-card">
                                <div class="emoji">{{ $info['icon'] }}</div>
                                <div class="value">{{ $stats[$key] ?? 0 }}</div>
                                <div class="label">{{ $info['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Import Log --}}
                @if(isset($importProgress['result']['log']) && count($importProgress['result']['log']) > 0)
                    <details class="infix-log-details">
                        <summary>
                            View Import Log ({{ count($importProgress['result']['log']) }} entries)
                        </summary>
                        <div class="infix-log-output">
                            @foreach($importProgress['result']['log'] as $logEntry)
                                <div>{{ $logEntry }}</div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @endif

        {{-- Data Mapping Reference --}}
        <details class="infix-mapping-details">
            <summary>
                📋 InfixEdu → KynexEdu Table Mapping Reference
            </summary>
            <div style="margin-top: 1rem; overflow-x: auto;">
                <table class="infix-mapping-table">
                    <thead>
                        <tr>
                            <th>InfixEdu Table</th>
                            <th>KynexEdu Table</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>sm_academic_years</code></td><td><code>academic_years</code></td><td>Session/Year mapping</td></tr>
                        <tr><td><code>sm_classes</code></td><td><code>school_classes</code></td><td>Class name import</td></tr>
                        <tr><td><code>sm_sections</code></td><td><code>sections</code></td><td>Linked to classes</td></tr>
                        <tr><td><code>sm_subjects</code></td><td><code>subjects</code></td><td>Subject name + code</td></tr>
                        <tr><td><code>sm_human_departments</code></td><td><code>departments</code></td><td>HR departments</td></tr>
                        <tr><td><code>sm_designations</code></td><td><code>designations</code></td><td>Job titles</td></tr>
                        <tr><td><code>sm_student_categories</code></td><td><code>student_categories</code></td><td>Student categories</td></tr>
                        <tr><td><code>sm_fees_groups</code></td><td><code>fee_groups</code></td><td>Fee group names</td></tr>
                        <tr><td><code>sm_fees_types</code></td><td><code>fee_types</code></td><td>Fee type + group link</td></tr>
                        <tr><td><code>sm_staffs</code></td><td><code>school_users + staff_profiles</code></td><td>User account + staff details</td></tr>
                        <tr><td><code>sm_students</code></td><td><code>school_users + students + student_guardians</code></td><td>Full student import with guardian</td></tr>
                    </tbody>
                </table>
            </div>
        </details>

    </div>
</x-filament-panels::page>
