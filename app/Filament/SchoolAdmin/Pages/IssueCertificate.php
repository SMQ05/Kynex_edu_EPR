<?php

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\CertificateTemplate;
use App\Models\Tenant\GeneratedCertificate;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Services\CertificateService;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\BulkAction;

class IssueCertificate extends Page implements HasForms, HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'issue_certificates';

    use InteractsWithForms, InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & ID Cards';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.school-admin.pages.issue-certificate';

    public ?string $template_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_id')
                    ->label('Certificate Template')
                    ->options(CertificateTemplate::where('is_active', true)->pluck('name', 'id'))
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
                TextColumn::make('generated_certificates_count')
                    ->counts('generatedCertificates')
                    ->label('Certificates'),
            ])
            ->bulkActions([
                BulkAction::make('generate_certificates')
                    ->label('Generate Certificates')
                    ->icon('heroicon-o-document-check')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records) {
                        if (! $this->template_id) {
                            Notification::make()
                                ->title('Please select a template first')
                                ->danger()
                                ->send();
                            return;
                        }

                        $template = CertificateTemplate::findOrFail($this->template_id);
                        $service = app(CertificateService::class);
                        $issuedBy = Auth::guard('school_users')->user();

                        $generated = $service->generateBulkCertificates($template, $records, $issuedBy);

                        Notification::make()
                            ->title("Generated {$generated->count()} certificates successfully")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function getTitle(): string
    {
        return 'Issue Certificates';
    }

    public static function getNavigationLabel(): string
    {
        return 'Issue Certificates';
    }

    /**
     * Action to download a previously generated certificate.
     */
    public function downloadCertificate(string $certificateId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $certificate = GeneratedCertificate::findOrFail($certificateId);

        return Storage::disk('tenant')->download($certificate->file_path, "{$certificate->certificate_number}.pdf");
    }
}
