<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Tenant selector --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tenant (school)</label>
            <select wire:model.live="tenant_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                <option value="">-- Select Tenant --</option>
                @foreach($this->tenantOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        @if($tenant_id)
            {{-- Current holders --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-3">Current Heads in this tenant</h3>
                @php $h = $this->currentHolders; @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg ring-1 ring-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:ring-blue-800 p-3">
                        <div class="text-xs uppercase font-semibold tracking-wide text-blue-700 dark:text-blue-300">Institute Head</div>
                        @if(empty($h['INSTITUTE_HEAD']))
                            <div class="italic text-blue-700 dark:text-blue-300 mt-1">None — assign one below.</div>
                        @else
                            @foreach($h['INSTITUTE_HEAD'] as $entry)
                                <div class="font-medium mt-1">{{ $entry }}</div>
                            @endforeach
                        @endif
                    </div>
                    <div class="rounded-lg ring-1 ring-purple-200 bg-purple-50 dark:bg-purple-900/20 dark:ring-purple-800 p-3">
                        <div class="text-xs uppercase font-semibold tracking-wide text-purple-700 dark:text-purple-300">Multi-Institute Head</div>
                        @if(empty($h['MULTI_INSTITUTE_HEAD']))
                            <div class="italic text-purple-700 dark:text-purple-300 mt-1">None.</div>
                        @else
                            @foreach($h['MULTI_INSTITUTE_HEAD'] as $entry)
                                <div class="font-medium mt-1">{{ $entry }}</div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Assignment form --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Assign Role</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium">Role</span>
                        <select wire:model.live="role" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="INSTITUTE_HEAD">Institute Head (single campus)</option>
                            <option value="MULTI_INSTITUTE_HEAD">Multi-Institute Head (multi campus)</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">Mode</span>
                        <select wire:model.live="mode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="new">Create new account &amp; send activation email</option>
                            <option value="existing">Promote an existing user in this tenant</option>
                        </select>
                    </label>
                </div>

                <div class="mt-4">
                    @if($mode === 'new')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="block">
                                <span class="text-sm font-medium">Full name</span>
                                <input wire:model.blur="name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="e.g. Ahmed Khan">
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium">Email</span>
                                <input type="email" wire:model.blur="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="head@example.com">
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            A SchoolUser will be created in this tenant (inactive) and an activation email sent. They set their own password via the link.
                        </p>
                    @else
                        <label class="block">
                            <span class="text-sm font-medium">Existing user</span>
                            <select wire:model.live="existing_user_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                <option value="">-- Pick a user in this tenant --</option>
                                @foreach($this->existingUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} &lt;{{ $u->email }}&gt;</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>

                <div class="mt-6">
                    <x-filament::button wire:click="assign" color="primary">
                        Assign {{ $role }}
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
