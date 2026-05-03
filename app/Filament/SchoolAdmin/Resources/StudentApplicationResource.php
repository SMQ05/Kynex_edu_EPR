<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\ApplicationStatus;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\StudentApplication;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StudentApplicationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';

    protected static ?string $model = StudentApplication::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Admissions';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Application';

    protected static ?string $pluralModelLabel = 'Admissions';

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = StudentApplication::query()
                ->whereIn('status', [
                    ApplicationStatus::Submitted->value,
                    ApplicationStatus::EntryTestScheduled->value,
                    ApplicationStatus::EntryTestTaken->value,
                    ApplicationStatus::PendingApproval->value,
                ])
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Applicant')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('first_name')->required(),
                    Components\TextInput::make('last_name')->required(),
                    Components\DatePicker::make('date_of_birth'),
                    Components\Select::make('gender')->options([
                        'male' => 'Male', 'female' => 'Female', 'other' => 'Other',
                    ]),
                    Components\TextInput::make('phone')->tel(),
                    Components\TextInput::make('email')->email(),
                    Components\TextInput::make('address')->columnSpanFull(),
                    Components\TextInput::make('city'),
                    Components\TextInput::make('previous_school'),
                ]),

            Section::make('Guardian')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('father_name'),
                    Components\TextInput::make('mother_name'),
                    Components\TextInput::make('guardian_phone')->tel(),
                    Components\TextInput::make('guardian_email')->email(),
                ]),

            Section::make('Class & Campus')
                ->columns(3)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->options(fn () => AcademicYear::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('campus_id')
                        ->options(fn () => Campus::pluck('name', 'id'))
                        ->searchable(),
                ]),

            Section::make('Entry Test')
                ->columns(2)
                ->schema([
                    Components\DateTimePicker::make('entry_test_scheduled_at')->label('Test scheduled at'),
                    Components\TextInput::make('entry_test_room'),
                    Components\TextInput::make('entry_test_score')->numeric()->step('0.01'),
                    Components\Textarea::make('entry_test_notes')->columnSpanFull()->rows(2),
                ]),

            Section::make('Decision')
                ->columns(1)
                ->schema([
                    Components\Select::make('status')
                        ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required(),
                    Components\Textarea::make('decision_notes')->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Applicant')
                    ->formatStateUsing(fn ($state, $record) => $record?->full_name ?? $state)
                    ->description(fn ($record) => $record
                        ? trim(
                            ($record->date_of_birth ? \Carbon\Carbon::parse($record->date_of_birth)->age . ' yrs' : '')
                            . ($record->gender ? ' · ' . ucfirst($record->gender) : '')
                        ) : null)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('campus.name')->label('Campus')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('guardian_phone')->label('Guardian phone')->toggleable(),
                Tables\Columns\TextColumn::make('entry_test_scheduled_at')
                    ->label('Test')
                    ->dateTime('d M · H:i')
                    ->placeholder('Not scheduled')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('entry_test_score')->label('Score')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ApplicationStatus ? $state->label() : ucfirst((string) $state))
                    ->color(function ($state): string {
                        if ($state instanceof ApplicationStatus) return $state->color();
                        return 'gray';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Applied')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                Tables\Filters\SelectFilter::make('campus_id')->relationship('campus', 'name')->label('Campus'),
            ])
            ->actions([
                EditAction::make(),

                // ── Lifecycle: Schedule entry test ───────────────────
                Action::make('scheduleTest')
                    ->label('Schedule test')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->visible(fn ($r) => self::statusValue($r) === 'submitted')
                    ->form([
                        Components\DateTimePicker::make('entry_test_scheduled_at')
                            ->label('Date & time')->required()->default(now()->addDays(7)),
                        Components\TextInput::make('entry_test_room')->label('Room / Hall')->placeholder('Hall A'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'entry_test_scheduled_at' => $data['entry_test_scheduled_at'],
                            'entry_test_room'         => $data['entry_test_room'] ?? null,
                            'status'                  => ApplicationStatus::EntryTestScheduled->value,
                        ]);
                        Notification::make()->title('Test scheduled')->success()->send();
                    }),

                // ── Lifecycle: Record test score ─────────────────────
                Action::make('recordScore')
                    ->label('Record score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn ($r) => in_array(self::statusValue($r), ['entry_test_scheduled', 'entry_test_taken'], true))
                    ->form([
                        Components\TextInput::make('entry_test_score')
                            ->label('Score')
                            ->numeric()->step('0.01')->required()
                            ->placeholder('e.g. 78.5'),
                        Components\Textarea::make('entry_test_notes')
                            ->label('Notes (optional)')->rows(2)->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'entry_test_score' => $data['entry_test_score'],
                            'entry_test_notes' => $data['entry_test_notes'] ?? null,
                            'status'           => ApplicationStatus::EntryTestTaken->value,
                        ]);
                        Notification::make()->title('Score recorded · ready for decision')->success()->send();
                    }),

                // ── Lifecycle: Recommend / Mark for institute approval ─
                Action::make('recommend')
                    ->label('Recommend')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('info')
                    ->visible(fn ($r) => self::statusValue($r) === 'entry_test_taken')
                    ->form([
                        Components\Textarea::make('decision_notes')->rows(3)->required()->minLength(5)
                            ->placeholder('Recommendation for the institute head'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'decision_notes' => $data['decision_notes'],
                            'status'         => ApplicationStatus::PendingApproval->value,
                            'reviewed_at'    => now(),
                            'reviewed_by'    => auth('school_users')->id(),
                        ]);
                        Notification::make()->title('Sent to institute head for final decision')->success()->send();
                    }),

                ActionGroup::make([
                    Action::make('admit')
                        ->label('Admit')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($r) => ! in_array(self::statusValue($r), ['admitted', 'rejected'], true))
                        ->requiresConfirmation()
                        ->modalHeading('Admit this applicant')
                        ->modalDescription('Creates the Student record, generates a registration number, and sends the parent portal invite.')
                        ->action(function ($record) {
                            try {
                                app(\App\Services\StudentApplicationService::class)->admit($record);
                                Notification::make()->title('Admitted · student record created')->success()->send();
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('Admission failed', ['error' => $e->getMessage()]);
                                Notification::make()->title('Admission failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($r) => self::statusValue($r) !== 'rejected')
                        ->form([
                            Components\Textarea::make('decision_notes')->required()->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status'         => ApplicationStatus::Rejected->value,
                                'decision_notes' => $data['decision_notes'],
                                'reviewed_at'    => now(),
                                'reviewed_by'    => auth('school_users')->id(),
                            ]);
                            Notification::make()->title('Application rejected')->danger()->send();
                        }),
                    Action::make('waitlist')
                        ->label('Waitlist')
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->visible(fn ($r) => ! in_array(self::statusValue($r), ['admitted', 'rejected', 'waitlisted'], true))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status'      => ApplicationStatus::Waitlisted->value,
                                'reviewed_at' => now(),
                            ]);
                            Notification::make()->title('Waitlisted')->info()->send();
                        }),
                ])->label('Decision')->icon('heroicon-o-ellipsis-vertical')->button()->size('sm'),
            ]);
    }

    /**
     * Coerce status to a plain string regardless of enum-or-not.
     */
    private static function statusValue($record): string
    {
        $s = $record->status ?? null;
        return $s instanceof \BackedEnum ? $s->value : (string) $s;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentApplications::route('/'),
            'create' => Pages\CreateStudentApplication::route('/create'),
            'edit'   => Pages\EditStudentApplication::route('/{record}/edit'),
        ];
    }
}
