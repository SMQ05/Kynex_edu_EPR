<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-3">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    @php $summary = $this->getSeatSummary(); @endphp
    @if(! empty($summary))
        <x-filament::section class="mt-6">
            <x-slot name="heading">Current Allocation</x-slot>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Room</th>
                            <th class="px-4 py-2 font-medium text-right">Seated students</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($summary as $room => $count)
                            <tr>
                                <td class="px-4 py-2">{{ $room }}</td>
                                <td class="px-4 py-2 text-right">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
