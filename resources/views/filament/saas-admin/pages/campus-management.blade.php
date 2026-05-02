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
            {{-- Add / Edit form --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">{{ $editing_id ? 'Edit Campus' : 'Add Campus' }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="block">
                        <span class="text-sm font-medium">Name *</span>
                        <input wire:model.blur="name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Phone</span>
                        <input wire:model.blur="phone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-medium">Address</span>
                        <input wire:model.blur="address" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-medium">Email</span>
                        <input type="email" wire:model.blur="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    </label>
                </div>
                <div class="mt-4 flex gap-3">
                    <x-filament::button wire:click="saveCampus" color="primary">{{ $editing_id ? 'Update' : 'Add' }}</x-filament::button>
                    @if($editing_id)
                        <x-filament::button wire:click="resetForm" color="gray">Cancel</x-filament::button>
                    @endif
                </div>
            </div>

            {{-- Existing campuses --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Existing Campuses</h3>
                @if($this->campuses->isEmpty())
                    <p class="text-sm text-gray-500">No campuses yet for this tenant.</p>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Address</th>
                                <th class="px-4 py-3 font-medium">Phone</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->campuses as $c)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $c->name }}</td>
                                    <td class="px-4 py-3">{{ $c->address ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $c->phone ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $c->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button wire:click="startEdit('{{ $c->id }}')" class="text-primary-600 hover:underline text-sm">Edit</button>
                                        <button wire:click="deleteCampus('{{ $c->id }}')" wire:confirm="Delete this campus?" class="text-red-600 hover:underline text-sm">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
