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
use Illuminate\Support\Facades\Storage;
use Filament\Actions\BulkAction;

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
                    ->label('Generate ID Cards')
                    ->icon('heroicon-o-identification')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        if (! $this->template_id) {
                            Notification::make()
                                ->title('Please select an ID card template first')
                                ->danger()
                                ->send();
                            return;
                        }

                        $template = IdCardTemplate::findOrFail($this->template_id);
                        $service = app(CertificateService::class);

                        $filename = $service->generateBulkStudentIdCards($template, $records);

                        Notification::make()
                            ->title("Generated ID cards for {$records->count()} students")
                            ->body('Download will start shortly.')
                            ->success()
                            ->send();

                        return Storage::disk('tenant')->download($filename, 'student-id-cards.pdf');
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
}
