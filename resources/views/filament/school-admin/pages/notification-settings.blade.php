@php
    // This page uses a form rendered inline via Livewire property bindings
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->settingsForm }}
        <div class="mt-4">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
