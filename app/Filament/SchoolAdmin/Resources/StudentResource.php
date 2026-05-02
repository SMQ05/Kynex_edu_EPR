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
                        ->default(fn () => static::scopedCampusId())
                        // Locked to the admin's own campus unless they are
                        // institute-level or SaaS-level — those roles can
                        // place a student at any campus.
                        ->disabled(fn () => static::scopedCampusId() !== null && ! static::canChooseAnyCampus())
                        ->dehydrated(true)
                        ->required(fn () => static::scopedCampusId() !== null),

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
                        ->default('Pakistani')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : 'Pakistani'),

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

                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Reg #')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

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
                    ->formatStateUsing(fn ($state) => $state instanceof StudentStatus ? $state->label() : $state)
                    ->color(fn ($state): string => $state instanceof StudentStatus ? $state->color() : 'gray')
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
                Action::make('profile')
                    ->label('Profile')
                    ->icon('heroicon-o-identification')
                    ->color('info')
                    ->url(fn (Student $record): string => static::getUrl('profile', ['record' => $record]))
                    ->openUrlInNewTab(false),

                Action::make('sendStudentLogin')
                    ->label('Send Login')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (Student $record): bool => filled($record->email))
                    ->requiresConfirmation()
                    ->modalHeading('Send login credentials')
                    ->modalDescription(fn (Student $record) => "An activation link will be emailed to {$record->email}. The student will set their own password.")
                    ->action(function (Student $record) {
                        // force=true so admin can resend even if the student
                        // account is already active (e.g. if they lost the
                        // original email and need a fresh link).
                        $invite = app(\App\Services\StudentAccountActivator::class)->activateStudent($record, force: true);
                        if (! $invite) {
                            Notification::make()
                                ->title('Could not send link')
                                ->body('No email on file for this student.')
                                ->danger()
                                ->send();
                            return;
                        }
                        Notification::make()
                            ->title('Login link sent')
                            ->body("Activation email sent to {$record->email}. Link expires in 7 days.")
                            ->success()
                            ->send();
                    }),

                Action::make('inviteParent')
                    ->label('Invite Parent')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->visible(fn (Student $record): bool => $record->guardians()->whereNotNull('email')->exists())
                    ->form(function (Student $record) {
                        $options = $record->guardians()
                            ->whereNotNull('email')
                            ->get()
                            ->mapWithKeys(fn ($g) => [$g->id => "{$g->name} <{$g->email}>"])
                            ->toArray();

                        return [
                            Components\Select::make('guardian_id')
                                ->label('Guardian')
                                ->options($options)
                                ->required(),
                        ];
                    })
                    ->action(function (Student $record, array $data) {
                        $guardian = $record->guardians()->find($data['guardian_id']);
                        if (! $guardian || ! $guardian->email) {
                            Notification::make()->title('Guardian or email missing')->danger()->send();
                            return;
                        }

                        app(\App\Services\StudentAccountActivator::class)->activateGuardian(
                            $guardian->name,
                            $guardian->email,
                            tenant()->id,
                            ['student_id' => $record->id],
                        );

                        Notification::make()
                            ->title('Parent invite sent')
                            ->body("Activation link emailed to {$guardian->email}.")
                            ->success()
                            ->send();
                    }),

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
                            ,
                    ])
                    ->action(function (Student $record, array $data) {
                        $newStatus = $data['new_status'];
                        // Every state transition that takes the student
                        // out of the active "enrolled" state requires
                        // institute-head approval — including Suspended.
                        $sensitiveStatuses = ['left', 'expelled', 'graduated', 'suspended'];

                        $actor = auth('school_users')->user() ?? auth()->user();
                        $activeRole = $actor?->active_role ?? $actor?->roles->first()?->name;
                        $bypass = in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);

                        if (in_array($newStatus, $sensitiveStatuses) && ! $bypass) {
                            $approvalService = app(ApprovalService::class);
                            $approvalService->submit(
                                requestedBy: $actor,
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
            'index'   => Pages\ListStudents::route('/'),
            'create'  => Pages\CreateStudent::route('/create'),
            'edit'    => Pages\EditStudent::route('/{record}/edit'),
            'profile' => Pages\StudentProfile::route('/{record}/profile'),
        ];
    }

    /**
     * The campus the current actor is locked to. Returns null for users
     * who can manage students across every campus (INSTITUTE_HEAD,
     * MULTI_INSTITUTE_HEAD, REGISTRAR), so they bypass scoping. School
     * admins / receptionists / HR managers are scoped to their own
     * `school_users.campus_id`.
     */
    public static function scopedCampusId(): ?string
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return null;
        }
        if (static::canChooseAnyCampus()) {
            return null;
        }
        return $user->campus_id;
    }

    public static function canChooseAnyCampus(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }
        return $user->hasAnyRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'REGISTRAR']);
    }

    /**
     * Constrain the Student list query to the actor's campus when scoped.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $campusId = static::scopedCampusId();
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        return $query;
    }

    public static function canCreate(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        // Use the *active* role as the source of truth — a user holding
        // both TEACHER and SCHOOL_ADMIN who has switched into TEACHER
        // mode must not get admin powers. Filament's "Switch role" menu
        // updates active_role; this gate respects that choice.
        $activeRole = $user->active_role ?? $user->roles->first()?->name;

        $allowedRoles = [
            'SCHOOL_ADMIN',
            'INSTITUTE_HEAD',
            'MULTI_INSTITUTE_HEAD',
            'REGISTRAR',
            'HR_MANAGER',
            'RECEPTIONIST',
        ];

        return in_array($activeRole, $allowedRoles, true);
    }
}
