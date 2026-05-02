<x-filament-panels::page>
    <form wire:submit.prevent="send" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                Send Message
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
