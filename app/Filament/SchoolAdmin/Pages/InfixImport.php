<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Jobs\ProcessInfixImport;
use App\Services\InfixImportService;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InfixImport extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'create_student';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Import from Infix';

    protected static ?string $title = 'InfixEdu Data Import';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.school-admin.pages.infix-import';

    // Form state
    public string $db_host = '127.0.0.1';
    public string $db_port = '3306';
    public string $db_database = '';
    public string $db_username = 'root';
    public string $db_password = '';

    public ?array $importProgress = null;

    protected string $cacheKey = 'infix_import_progress';

    public function mount(): void
    {
        $this->importProgress = Cache::get($this->cacheKey);
    }

    public function connectionForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('🔌 InfixEdu MySQL Connection')
                    ->description('Enter the connection details of the InfixEdu MySQL database to import data from. The database should be accessible from this server.')
                    ->schema([
                        Components\TextInput::make('db_host')
                            ->label('MySQL Host')
                            ->required()
                            ->default('127.0.0.1'),

                        Components\TextInput::make('db_port')
                            ->label('Port')
                            ->required()
                            ->default('3306'),

                        Components\TextInput::make('db_database')
                            ->label('Database Name')
                            ->required()
                            ->placeholder('infixedu_db'),

                        Components\TextInput::make('db_username')
                            ->label('Username')
                            ->required()
                            ->default('root'),

                        Components\TextInput::make('db_password')
                            ->label('Password')
                            ->password(),
                    ]),
            ])
            ->statePath('');
    }

    /**
     * Register all named forms for Filament v5.
     */
    protected function getForms(): array
    {
        return [
            'connectionForm',
        ];
    }

    public function testConnection(): void
    {
        try {
            $this->configureConnection();

            DB::connection('infix_source')->getPdo();

            $tables = DB::connection('infix_source')
                ->select("SHOW TABLES");

            $count = count($tables);

            Notification::make()
                ->title("Connection successful! Found {$count} tables.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Connection failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startImport(): void
    {
        try {
            $this->configureConnection();

            // Verify connection first
            DB::connection('infix_source')->getPdo();

            // Dispatch the import job
            $tenantId = tenancy()->tenant?->id ?? 'unknown';

            ProcessInfixImport::dispatch($tenantId, $this->cacheKey);

            $this->importProgress = [
                'status'  => 'queued',
                'message' => 'Import job queued. Processing will begin shortly...',
                'percent' => 0,
            ];

            Cache::put($this->cacheKey, $this->importProgress, now()->addHours(1));

            Notification::make()
                ->title('Import job dispatched')
                ->body('The import is running in the background. Refresh this page to check progress.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to start import')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runImportSync(): void
    {
        try {
            $this->configureConnection();
            DB::connection('infix_source')->getPdo();

            $service = new InfixImportService();
            $result = $service->import();

            $this->importProgress = [
                'status'  => 'completed',
                'message' => 'Import completed successfully',
                'percent' => 100,
                'result'  => $result,
            ];

            Cache::put($this->cacheKey, $this->importProgress, now()->addHours(1));

            Notification::make()
                ->title('Import completed!')
                ->body("Imported: {$result['stats']['students']} students, {$result['stats']['staff']} staff, {$result['stats']['classes']} classes")
                ->success()
                ->duration(10000)
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Import failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshProgress(): void
    {
        $this->importProgress = Cache::get($this->cacheKey);
    }

    public function clearProgress(): void
    {
        Cache::forget($this->cacheKey);
        $this->importProgress = null;
    }

    protected function configureConnection(): void
    {
        config([
            'database.connections.infix_source' => [
                'driver'    => 'mysql',
                'host'      => $this->db_host,
                'port'      => $this->db_port,
                'database'  => $this->db_database,
                'username'  => $this->db_username,
                'password'  => $this->db_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ],
        ]);

        DB::purge('infix_source');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('documentation')
                ->label('Import Guide')
                ->icon('heroicon-o-book-open')
                ->url('https://docs.kynexsolution.com/import-infix')
                ->openUrlInNewTab()
                ->color('gray'),
        ];
    }
}
