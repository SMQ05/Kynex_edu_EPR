<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\BloodGroup;
use App\Enums\StudentGender;
use App\Enums\StudentStatus;
use App\Events\StudentDeactivated;
use App\Filament\SchoolAdmin\Resources\StudentResource\Pages;
use App\Models\Tenant\Student;
use App\Services\ApprovalService;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class StudentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';

    protected static ?string $model = Student::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Students';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 2;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Admission Details')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('admission_number')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),

                    Components\TextInput::make('roll_number')
                        ->maxLength(50)
                        ->nullable(),

                    Components\DatePicker::make('admission_date')
                        ->required()
                        ->native(false)
                        ->default(now()),
                ]),

            Section::make('Academic Placement')
                ->columns(2)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->relationship('academicYear', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Components\Select::make('campus_id')
                        ->label('Campus')
                        ->relationship('campus', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Components\Select::make('class_id')
                        ->label('Class')
                        ->relationship('schoolClass', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Components\Select::make('section_id')
                        ->label('Section')
                        ->relationship('section', 'name', fn ($query, $get) => $query->when(
                            $get('class_id'),
                            fn ($q, $classId) => $q->where('class_id', $classId),
                        ))
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Components\Select::make('category_id')
                        ->label('Student Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

            Section::make('Personal Information')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('first_name')
                        ->required()
                        ->maxLength(100),

                    Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(100),

                    Components\DatePicker::make('date_of_birth')
                        ->native(false)
                        ->nullable(),

                    Components\Select::make('gender')
                        ->options(StudentGender::class)
                        ->required(),

                    Components\Select::make('blood_group')
                        ->options(BloodGroup::class)
                        ->nullable(),

                    Components\TextInput::make('religion')
                        ->maxLength(50)
                        ->nullable(),

                    Components\TextInput::make('nationality')
                        ->maxLength(50)
                        ->nullable(),

                    Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20)
                        ->nullable(),

                    Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255)
                        ->nullable(),

                    Components\FileUpload::make('profile_photo_path')
                        ->label('Profile Photo')
                        ->image()
                        ->directory('student-photos')
                        ->maxSize(2048)
                        ->nullable()
                        ->columnSpanFull(),

                    Components\Textarea::make('address')
                        ->rows(2)
                        ->nullable()
                        ->columnSpan(2),

                    Components\TextInput::make('city')
                        ->maxLength(100)
                        ->nullable(),
                ]),

            Section::make('Status & Enrolment')
                ->columns(2)
                ->schema([
                    Components\Select::make('status')
                        ->options(StudentStatus::class)
                        ->default(StudentStatus::Enrolled)
                        ->required(),

                    Components\TextInput::make('previous_school')
                        ->label('Previous School')
                        ->maxLength(255)
                        ->nullable(),

                    Components\Textarea::make('special_needs_notes')
                        ->label('Special Needs Notes')
                        ->rows(2)
                        ->nullable(),

                    Components\Textarea::make('medical_notes')
                        ->label('Medical Notes')
                        ->rows(2)
                        ->nullable(),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('admission_number')
                    ->label('Adm #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),

                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('campus.name')
                    ->label('Campus')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (StudentStatus $state): string => match ($state) {
                        StudentStatus::Enrolled => 'success',
                        StudentStatus::Left => 'gray',
                        StudentStatus::Graduated => 'info',
                        StudentStatus::Expelled => 'danger',
                        StudentStatus::Suspended => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('admission_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('admission_number', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name'),

                Tables\Filters\SelectFilter::make('section_id')
                    ->label('Section')
                    ->relationship('section', 'name'),

                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options(StudentStatus::class),

                Tables\Filters\SelectFilter::make('gender')
                    ->options(StudentGender::class),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'name'),
            ])
            ->actions([
                EditAction::make(),

                // ── Change Status action with approval wiring ──
                Action::make('changeStatus')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Components\Select::make('new_status')
                            ->label('New Status')
                            ->options(StudentStatus::class)
                            ->required(),

                        Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->minLength(10)
                            ->rows(3),

                        Components\DatePicker::make('date')
                            ->label('Effective Date')
                            ->default(now())
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Student $record, array $data) {
                        $newStatus = $data['new_status'];
                        $sensitiveStatuses = ['left', 'expelled', 'graduated'];

                        // Check if this is a sensitive status change requiring approval
                        if (
                            in_array($newStatus, $sensitiveStatuses)
                            && ! auth()->user()->hasPermissionTo('bypass_approvals')
                        ) {
                            $approvalService = app(ApprovalService::class);
                            $approvalService->submit(
                                requestedBy: auth()->user(),
                                actionType: 'student_status_change',
                                subject: $record,
                                payload: [
                                    'new_status' => $newStatus,
                                    'reason'     => $data['reason'],
                                    'date'       => $data['date'],
                                ],
                            );

                            Notification::make()
                                ->title('Status change submitted for approval')
                                ->body("Changing {$record->full_name} to {$newStatus} requires Institute Owner approval.")
                                ->warning()
                                ->send();

                            return; // Do NOT change status yet
                        }

                        // If bypass_approvals: execute immediately as before
                        $previousStatus = $record->status;
                        $record->update([
                            'status'               => $newStatus,
                            'status_changed_at'    => $data['date'],
                            'status_change_reason' => $data['reason'],
                        ]);

                        if ($newStatus !== 'enrolled') {
                            event(new StudentDeactivated(
                                student: $record,
                                previousStatus: $previousStatus,
                            ));
                        }

                        Notification::make()
                            ->title('Status Updated')
                            ->body("{$record->full_name} status changed to {$newStatus}.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
