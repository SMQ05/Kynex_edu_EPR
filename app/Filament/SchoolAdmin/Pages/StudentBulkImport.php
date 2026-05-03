<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\Campus;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Services\StudentBulkImporter;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentBulkImport extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'view_students';
    protected static string $rbacWritePermission = 'create_students';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Bulk Import';

    protected string $view = 'filament.school-admin.pages.student-bulk-import';

    /** Livewire-bound upload state */
    public ?array $upload = [];

    /** Result of the most recent import (kept in memory) */
    public ?array $lastResult = null;

    public function getTitle(): string
    {
        return 'Bulk-Import Students from CSV';
    }

    public function getSubheading(): ?string
    {
        return 'Download the template, fill in your data, and upload it back here. Existing rows with the same admission number are updated; new ones are created.';
    }

    public function uploadForm(Schema $form): Schema
    {
        return $form
            ->components([
                FormSection::make('Upload')
                    ->description('Pick the filled-in CSV. Maximum 5 MB. UTF-8 encoded.')
                    ->schema([
                        Components\FileUpload::make('upload')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(5120)
                            ->disk('local')
                            ->directory('imports')
                            ->required()
                            ->helperText('Use the template above. Don\'t change column names.'),
                    ]),
            ])
            ->statePath('upload');
    }

    /** Stream the empty CSV template to the browser. */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = StudentBulkImporter::templateHeaders();
        $example = StudentBulkImporter::templateExampleRow();

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens with UTF-8 properly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'students-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Run the import on the uploaded file. */
    public function runImport(): void
    {
        $path = is_array($this->upload) ? reset($this->upload) : $this->upload;
        if (! $path) {
            Notification::make()->title('Pick a CSV file first')->danger()->send();
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if (! $disk->exists($path)) {
            Notification::make()->title('Uploaded file not found')->danger()->send();
            return;
        }

        $absolutePath = $disk->path($path);

        try {
            $result = app(StudentBulkImporter::class)->import($absolutePath);
        } catch (\Throwable $e) {
            Notification::make()->title('Import failed')->body($e->getMessage())->danger()->send();
            \Illuminate\Support\Facades\Log::warning('StudentBulkImport failed', ['error' => $e->getMessage()]);
            return;
        }

        $this->lastResult = $result;

        // Clean up the temp file once we've processed it.
        $disk->delete($path);
        $this->upload = [];

        $errorCount = count($result['errors'] ?? []);
        Notification::make()
            ->title("Import complete: {$result['created']} created · {$result['updated']} updated · {$result['skipped']} skipped")
            ->body($errorCount > 0 ? "$errorCount row(s) had errors — see report below." : 'All rows processed successfully.')
            ->color($errorCount > 0 ? 'warning' : 'success')
            ->send();
    }

    /** Reference data shown on the page so the operator gets the names exactly right. */
    public function referenceLists(): array
    {
        return [
            'classes'  => SchoolClass::query()->orderBy('sort_order')->orderBy('name')->pluck('name')->all(),
            'sections' => Section::query()->with('schoolClass:id,name')->orderBy('name')
                ->get(['id', 'name', 'class_id'])
                ->map(fn ($s) => trim(($s->schoolClass?->name ?? '?') . ' / ' . $s->name))
                ->all(),
            'years'    => AcademicYear::query()->orderByDesc('start_date')->pluck('name')->all(),
            'campuses' => Campus::query()->orderBy('name')->pluck('name')->all(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download CSV Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('downloadTemplate'),
        ];
    }

    protected function getForms(): array
    {
        return ['uploadForm'];
    }
}
