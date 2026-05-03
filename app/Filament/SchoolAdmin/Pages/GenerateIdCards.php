<?php

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\IdCardTemplate;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Services\CertificateService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateIdCards extends Page implements HasForms, HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    use InteractsWithForms, InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & ID Cards';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.school-admin.pages.generate-id-cards';

    public ?string $template_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_id')
                    ->label('ID Card Template')
                    ->options(IdCardTemplate::where('is_active', true)
                        ->where('card_type', 'student')
                        ->pluck('name', 'id'))
                    ->required()
                    ->reactive(),

                Select::make('class_id')
                    ->label('Class')
                    ->options(SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->section_id = null),

                Select::make('section_id')
                    ->label('Section')
                    ->options(fn () => $this->class_id
                        ? Section::where('class_id', $this->class_id)->pluck('name', 'id')
                        : [])
                    ->reactive(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->when($this->class_id, fn ($q) => $q->where('class_id', $this->class_id))
                    ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
                    ->where('status', 'enrolled')
            )
            ->columns([
                TextColumn::make('admission_number')->sortable()->searchable(),
                TextColumn::make('full_name')->label('Student Name')->searchable(['first_name', 'last_name']),
                TextColumn::make('schoolClass.name')->label('Class'),
                TextColumn::make('section.name')->label('Section'),
            ])
            ->bulkActions([
                BulkAction::make('generate_id_cards')
                    ->label('Generate & Download')
                    ->icon('heroicon-o-identification')
                    ->requiresConfirmation()
                    ->modalHeading('Generate ID cards and download')
                    ->modalDescription('A PDF card will be generated per selected student. Multiple students will be bundled into a single ZIP archive.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        if (! $this->template_id) {
                            Notification::make()
                                ->title('Please select an ID card template first')
                                ->danger()
                                ->send();
                            return null;
                        }

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('No students selected')
                                ->warning()
                                ->send();
                            return null;
                        }

                        $template = IdCardTemplate::findOrFail($this->template_id);
                        $service  = app(CertificateService::class);

                        if ($records->count() === 1) {
                            $built = $service->buildStudentIdCardPdf($template, $records->first());
                            return $this->streamBytes($built['bytes'], $built['filename'], 'application/pdf');
                        }

                        $items = $records->map(fn (Student $s) => $service->buildStudentIdCardPdf($template, $s));
                        $zipName = 'student-id-cards-' . now()->format('Ymd-His');
                        $bundle  = $service->bundleAsZip(collect($items), $zipName);

                        Notification::make()
                            ->title("Generated ID cards for {$records->count()} students")
                            ->success()
                            ->send();

                        return $this->streamBytes($bundle['bytes'], $bundle['filename'], 'application/zip');
                    }),
            ]);
    }

    public function getTitle(): string
    {
        return 'Generate ID Cards';
    }

    public static function getNavigationLabel(): string
    {
        return 'Generate ID Cards';
    }

    private function streamBytes(string $bytes, string $filename, string $mime): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($bytes) {
                echo $bytes;
            },
            $filename,
            ['Content-Type' => $mime, 'Content-Length' => (string) strlen($bytes)],
        );
    }
}
