<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Services\StudentBulkImporter;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentBulkImport extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';
    protected static string $rbacWritePermission = 'create_students';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Bulk Import';

    protected string $view = 'filament.school-admin.pages.student-bulk-import';

    public function getTitle(): string
    {
        return 'Bulk-Import Students from CSV';
    }

    public function getSubheading(): ?string
    {
        return 'Download the template, fill in your data, and upload it back here. Existing rows with the same admission number are updated; new ones are created.';
    }

    /** Stream the empty CSV template to the browser. */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = StudentBulkImporter::templateHeaders();
        $example = StudentBulkImporter::templateExampleRow();

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'students-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
}
