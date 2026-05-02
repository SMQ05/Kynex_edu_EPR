<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-paper-airplane">
                Send Notification
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
