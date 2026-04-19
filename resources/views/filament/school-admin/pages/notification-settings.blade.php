@php
    // This page uses a form rendered inline via Livewire property bindings
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}
    </form>
</x-filament-panels::page>
