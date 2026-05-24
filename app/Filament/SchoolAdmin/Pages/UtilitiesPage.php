<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\UserActivity;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Utilities — cache/optimize maintenance actions (Infix "Utilities").
 * Each action is confirm-guarded and runs an Artisan command.
 */
class UtilitiesPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_system_utilities';

    protected static string $rbacWritePermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Utilities';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Utilities';

    protected string $view = 'filament.school-admin.pages.utilities';

    public function getSubheading(): ?string
    {
        return 'Maintenance actions for this installation. Run after configuration changes or if the panel behaves unexpectedly.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->utility('clear_cache', 'Clear Cache', 'heroicon-o-trash', 'Clears application, route, config and view caches (optimize:clear).', 'optimize:clear'),
            $this->utility('optimize', 'Optimize', 'heroicon-o-bolt', 'Caches config, routes, events and views for performance (optimize).', 'optimize'),
            $this->utility('clear_views', 'Clear Compiled Views', 'heroicon-o-document', 'Removes compiled Blade view files (view:clear).', 'view:clear'),
            $this->utility('clear_config', 'Clear Config Cache', 'heroicon-o-cog-6-tooth', 'Removes the cached config file (config:clear).', 'config:clear'),
            Action::make('filament_optimize_clear')
                ->label('Clear Filament Cache')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Clears Filament component and icon caches (filament:optimize-clear).')
                ->action(function (): void {
                    try {
                        Artisan::call('filament:optimize-clear');
                        UserActivity::log('utility', null, null, 'Ran filament:optimize-clear');
                        Notification::make()->title('Filament cache cleared')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Command failed')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    private function utility(string $key, string $label, string $icon, string $description, string $command): Action
    {
        return Action::make($key)
            ->label($label)
            ->icon($icon)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading($label)
            ->modalDescription($description)
            ->action(function () use ($command, $label): void {
                try {
                    Artisan::call($command);
                    UserActivity::log('utility', null, null, "Ran {$command}");
                    Notification::make()
                        ->title($label . ' complete')
                        ->body(trim(Artisan::output()) ?: 'Done.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()->title($label . ' failed')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
