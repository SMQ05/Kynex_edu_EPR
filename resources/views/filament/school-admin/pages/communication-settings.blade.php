<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="saveWhatsapp">
            {{ $this->whatsappForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                    Save WhatsApp Settings
                </x-filament::button>
            </div>
        </form>

        <form wire:submit.prevent="saveSms">
            {{ $this->smsForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Save SMS Settings
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
