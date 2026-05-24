<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ModuleToggleResource\Pages;

use App\Filament\SchoolAdmin\Resources\ModuleToggleResource;
use App\Models\Tenant\ModuleToggle;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListModuleToggles extends ListRecords
{
    protected static string $resource = ModuleToggleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('seedKnown')
                ->label('Add standard modules')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Adds any of the known optional modules that are not yet listed (all enabled by default). Existing rows are left untouched.')
                ->action(function (): void {
                    $added = 0;
                    foreach (ModuleToggle::KNOWN_MODULES as $key => $label) {
                        $row = ModuleToggle::firstOrCreate(
                            ['module_key' => $key],
                            ['label' => $label, 'enabled' => true],
                        );
                        if ($row->wasRecentlyCreated) {
                            $added++;
                        }
                    }
                    Notification::make()
                        ->title($added > 0 ? "Added {$added} module(s)" : 'All standard modules already present')
                        ->success()
                        ->send();
                }),
        ];
    }
}
