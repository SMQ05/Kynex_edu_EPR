<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\CustomReport;
use App\Services\ReportBuilderService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ReportBuilderPage extends Page implements HasForms, HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_all_reports';

    use InteractsWithForms, InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';
    protected static string | \UnitEnum | null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Custom Reports';
    protected static ?string $title = 'Custom Report Builder';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.school-admin.pages.report-builder';

    // ── Form state ───────────────────────────────────────────────
    public ?string $activeTab = 'build';
    public ?string $base_model = null;
    public array $selected_columns = [];
    public array $filters = [];
    public ?string $sort_by = null;
    public string $sort_direction = 'asc';
    public ?string $group_by = null;

    // Save report fields
    public ?string $report_name = null;
    public ?string $report_description = null;
    public bool $is_scheduled = false;
    public ?string $schedule_frequency = null;
    public ?string $schedule_email = null;

    // Preview results
    public Collection|array $previewResults = [];
    public int $totalCount = 0;

    public function mount(): void
    {
        $this->previewResults = collect();
    }

    // ── Available columns based on selected model ────────────────
    public function getAvailableColumnsProperty(): array
    {
        if (! $this->base_model) {
            return [];
        }
        return ReportBuilderService::getColumnsForModel($this->base_model);
    }

    // ── Run report preview ───────────────────────────────────────
    public function runReport(): void
    {
        if (! $this->base_model || empty($this->selected_columns)) {
            Notification::make()
                ->title('Select a data source and at least one column')
                ->warning()
                ->send();
            return;
        }

        $report = new CustomReport([
            'base_model'       => $this->base_model,
            'selected_columns' => $this->selected_columns,
            'filters'          => $this->filters,
            'sort_by'          => $this->sort_by,
            'sort_direction'   => $this->sort_direction,
            'group_by'         => $this->group_by,
        ]);

        $service = app(ReportBuilderService::class);
        $results = $service->run($report);

        $this->totalCount = $results->count();
        $this->previewResults = $results->take(500)->toArray();

        Notification::make()
            ->title("Report generated — {$this->totalCount} rows found")
            ->success()
            ->send();
    }

    // ── Export actions ────────────────────────────────────────────
    public function exportReport(string $format): void
    {
        $report = new CustomReport([
            'name'             => $this->report_name ?? 'Untitled Report',
            'base_model'       => $this->base_model,
            'selected_columns' => $this->selected_columns,
            'filters'          => $this->filters,
            'sort_by'          => $this->sort_by,
            'sort_direction'   => $this->sort_direction,
            'group_by'         => $this->group_by,
        ]);

        $service = app(ReportBuilderService::class);
        $path = $service->export($report, $format);

        Notification::make()
            ->title("Report exported as {$format}")
            ->success()
            ->send();

        // Trigger file download
        $this->dispatch('download-file', path: $path);
    }

    // ── Save report ──────────────────────────────────────────────
    public function saveReport(): void
    {
        if (! $this->report_name) {
            Notification::make()
                ->title('Please enter a report name')
                ->warning()
                ->send();
            return;
        }

        CustomReport::create([
            'name'               => $this->report_name,
            'description'        => $this->report_description,
            'base_model'         => $this->base_model,
            'selected_columns'   => $this->selected_columns,
            'filters'            => $this->filters,
            'sort_by'            => $this->sort_by,
            'sort_direction'     => $this->sort_direction,
            'group_by'           => $this->group_by,
            'is_scheduled'       => $this->is_scheduled,
            'schedule_frequency' => $this->is_scheduled ? $this->schedule_frequency : null,
            'schedule_email'     => $this->is_scheduled ? $this->schedule_email : null,
            'created_by'         => Auth::id(),
        ]);

        Notification::make()
            ->title("Report '{$this->report_name}' saved successfully")
            ->success()
            ->send();

        $this->report_name = null;
        $this->report_description = null;
        $this->is_scheduled = false;
    }

    // ── Table for Saved Reports tab ──────────────────────────────
    public function table(Table $table): Table
    {
        return $table
            ->query(CustomReport::query()->orderByDesc('updated_at'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_model')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('last_run_at')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('is_scheduled')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Scheduled' : 'Manual')
                    ->sortable(),
                TextColumn::make('schedule_frequency')
                    ->placeholder('—'),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('run')
                    ->label('Run')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (CustomReport $record) {
                        $service = app(ReportBuilderService::class);
                        $results = $service->run($record);
                        $record->update(['last_run_at' => now()]);

                        $this->totalCount = $results->count();
                        $this->previewResults = $results->take(500)->toArray();
                        $this->activeTab = 'build';

                        Notification::make()
                            ->title("Ran '{$record->name}' — {$this->totalCount} rows")
                            ->success()
                            ->send();
                    }),
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->action(function (CustomReport $record) {
                        $this->base_model = $record->base_model;
                        $this->selected_columns = $record->selected_columns ?? [];
                        $this->filters = $record->filters ?? [];
                        $this->sort_by = $record->sort_by;
                        $this->sort_direction = $record->sort_direction ?? 'asc';
                        $this->group_by = $record->group_by;
                        $this->report_name = $record->name;
                        $this->report_description = $record->description;
                        $this->is_scheduled = $record->is_scheduled;
                        $this->schedule_frequency = $record->schedule_frequency;
                        $this->schedule_email = $record->schedule_email;
                        $this->activeTab = 'build';
                    }),
                Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (CustomReport $record) {
                        $clone = $record->replicate();
                        $clone->name = $record->name . ' (Copy)';
                        $clone->last_run_at = null;
                        $clone->created_by = Auth::id();
                        $clone->save();

                        Notification::make()
                            ->title("Cloned '{$record->name}'")
                            ->success()
                            ->send();
                    }),
                Action::make('email_now')
                    ->label('Run & Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (CustomReport $record) => $record->schedule_email)
                    ->action(function (CustomReport $record) {
                        \App\Jobs\RunScheduledReport::dispatch($record);
                        Notification::make()
                            ->title("Report queued for email delivery")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    // ── Reset when model changes ─────────────────────────────────
    public function updatedBaseModel(): void
    {
        $this->selected_columns = [];
        $this->filters = [];
        $this->sort_by = null;
        $this->group_by = null;
        $this->previewResults = [];
        $this->totalCount = 0;
    }
}
