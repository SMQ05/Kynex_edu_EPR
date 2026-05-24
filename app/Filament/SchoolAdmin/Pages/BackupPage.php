<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Backup control page. Runs a backup command if one is installed
 * (e.g. spatie/laravel-backup's `backup:run`); otherwise reports that
 * backups are managed at the infrastructure level (Coolify / DB host).
 */
class BackupPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_backup';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 67;

    protected static ?string $navigationLabel = 'Backup';

    protected static ?string $title = 'Backup';

    protected string $view = 'filament.school-admin.pages.backup';

    /** Candidate backup commands, in order of preference. */
    protected const BACKUP_COMMANDS = ['backup:run', 'db:backup', 'kynex:backup'];

    /** The backup command available in this install, or null. */
    public function availableCommand(): ?string
    {
        try {
            $all = Artisan::all();
        } catch (\Throwable) {
            return null;
        }

        foreach (self::BACKUP_COMMANDS as $command) {
            if (array_key_exists($command, $all)) {
                return $command;
            }
        }

        return null;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('Run Backup Now')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will start a backup of the school database. It may take a few minutes.')
                ->action(function (): void {
                    $command = $this->availableCommand();

                    if ($command === null) {
                        Notification::make()
                            ->title('No backup command installed')
                            ->body('Backups are handled at the infrastructure level (database host / Coolify scheduled snapshots). Install spatie/laravel-backup to enable on-demand backups here.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    try {
                        Artisan::call($command);
                        Notification::make()
                            ->title('Backup started')
                            ->body('Command "' . $command . '" ran successfully.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Backup failed')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
