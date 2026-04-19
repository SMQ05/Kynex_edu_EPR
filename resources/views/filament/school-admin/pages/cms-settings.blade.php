<x-filament-panels::page>
    <div class="space-y-8">

        {{-- General Information --}}
        <form wire:submit.prevent="saveGeneral">
            {{ $this->generalForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Save General Settings
                </x-filament::button>
            </div>
        </form>

        {{-- Contact Information --}}
        <form wire:submit.prevent="saveContact">
            {{ $this->contactForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Save Contact Info
                </x-filament::button>
            </div>
        </form>

        {{-- Social Media --}}
        <form wire:submit.prevent="saveSocial">
            {{ $this->socialForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Save Social Links
                </x-filament::button>
            </div>
        </form>

        {{-- About & Principal --}}
        <form wire:submit.prevent="saveAbout">
            {{ $this->aboutForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Save About & Principal
                </x-filament::button>
            </div>
        </form>

        {{-- Admissions --}}
        <form wire:submit.prevent="saveAdmission">
            {{ $this->admissionForm }}
            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Save Admission Settings
                </x-filament::button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
