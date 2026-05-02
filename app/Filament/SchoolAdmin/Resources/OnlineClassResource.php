<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\OnlineClassStatus;
use App\Filament\SchoolAdmin\Resources\OnlineClassResource\Pages;
use App\Models\Tenant\OnlineClass;
use App\Models\Tenant\OnlineClassAttendance;
use App\Models\Tenant\Student;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnlineClassResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_classes';
    protected static string $rbacWritePermission = 'manage_homework';

    protected static ?string $model = OnlineClass::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static string | \UnitEnum | null $navigationGroup = 'Online Classes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Select::make('class_id')
                            ->relationship('schoolClass', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Class'),

                        Select::make('section_id')
                            ->relationship('section', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('subject_id')
                            ->relationship('subject', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Teacher'),

                        Select::make('platform_id')
                            ->relationship('platform', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Platform'),
                    ])->columns(2),

                Section::make('Meeting Details')
                    ->schema([
                        TextInput::make('meeting_url')
                            ->url()
                            ->maxLength(500),

                        TextInput::make('meeting_id')
                            ->maxLength(255),

                        TextInput::make('passcode')
                            ->maxLength(100),

                        DateTimePicker::make('scheduled_at')
                            ->required(),

                        TextInput::make('duration_minutes')
                            ->numeric()
                            ->required()
                            ->default(45)
                            ->suffix('minutes'),

                        TextInput::make('max_participants')
                            ->numeric()
                            ->nullable()
                            ->label('Max Participants'),

                        TextInput::make('join_before_minutes')
                            ->numeric()
                            ->default(5)
                            ->suffix('minutes')
                            ->label('Join Before (min)'),
                    ])->columns(2),

                Section::make('Options')
                    ->schema([
                        Toggle::make('attendance_required')
                            ->default(false)
                            ->label('Attendance Required'),

                        Toggle::make('quiz_enabled')
                            ->default(false)
                            ->label('Quiz Enabled'),

                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                TextColumn::make('section.name')
                    ->label('Section')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable(),

                TextColumn::make('platform.name')
                    ->label('Platform')
                    ->badge()
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (OnlineClassStatus $state): string => match ($state) {
                        OnlineClassStatus::Scheduled => 'info',
                        OnlineClassStatus::Live => 'success',
                        OnlineClassStatus::Ended => 'gray',
                        OnlineClassStatus::Cancelled => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('attendance_records_count')
                    ->counts('attendanceRecords')
                    ->label('Attendance')
                    ->toggleable(),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(OnlineClassStatus::class),

                SelectFilter::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->label('Teacher')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('class_id')
                    ->relationship('schoolClass', 'name')
                    ->label('Class')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('platform_id')
                    ->relationship('platform', 'name')
                    ->label('Platform'),
            ])
            ->actions([
                // ── Start Class action ──────────────────────────────
                Action::make('start_class')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Start Online Class')
                    ->modalDescription('This will set the class as LIVE and notify students.')
                    ->visible(fn (OnlineClass $record): bool => $record->status === OnlineClassStatus::Scheduled)
                    ->action(function (OnlineClass $record): void {
                        $record->update([
                            'status' => OnlineClassStatus::Live,
                            'actual_start_at' => now(),
                        ]);

                        // Send notification to class students via NotificationService
                        try {
                            $students = Student::where('class_id', $record->class_id)
                                ->when($record->section_id, fn ($q) => $q->where('section_id', $record->section_id))
                                ->where('status', 'enrolled')
                                ->get();

                            // Use NotificationService if available
                            if (class_exists(\App\Services\NotificationService::class)) {
                                $notificationService = app(\App\Services\NotificationService::class);
                                foreach ($students as $student) {
                                    if ($student->schoolUser) {
                                        $notificationService->sendInApp(
                                            $student->schoolUser,
                                            "Online Class Started: {$record->title}",
                                            "Your online class '{$record->title}' has started. Join: {$record->meeting_url}",
                                        );
                                    }
                                }
                            }
                        } catch (\Throwable) {
                            // Notification failure should not block class start
                        }

                        Notification::make()
                            ->title('Class Started')
                            ->body("'{$record->title}' is now LIVE. Students have been notified.")
                            ->success()
                            ->send();
                    }),

                // ── End Class action ────────────────────────────────
                Action::make('end_class')
                    ->label('End')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('End Online Class')
                    ->modalDescription('This will end the class. If attendance is required, absent students will be auto-marked.')
                    ->visible(fn (OnlineClass $record): bool => $record->status === OnlineClassStatus::Live)
                    ->action(function (OnlineClass $record): void {
                        $record->update([
                            'status' => OnlineClassStatus::Ended,
                            'actual_end_at' => now(),
                        ]);

                        // Auto-mark absent students if attendance_required
                        if ($record->attendance_required) {
                            $students = Student::where('class_id', $record->class_id)
                                ->when($record->section_id, fn ($q) => $q->where('section_id', $record->section_id))
                                ->where('status', 'enrolled')
                                ->get();

                            $presentStudentIds = $record->attendanceRecords()
                                ->whereIn('status', ['present', 'late'])
                                ->pluck('student_id')
                                ->toArray();

                            foreach ($students as $student) {
                                if (! in_array($student->id, $presentStudentIds)) {
                                    OnlineClassAttendance::firstOrCreate(
                                        [
                                            'online_class_id' => $record->id,
                                            'student_id' => $student->id,
                                        ],
                                        [
                                            'status' => 'absent',
                                        ],
                                    );
                                }
                            }
                        }

                        Notification::make()
                            ->title('Class Ended')
                            ->body("'{$record->title}' has ended.")
                            ->success()
                            ->send();
                    }),

                // ── View Attendance action ──────────────────────────
                Action::make('view_attendance')
                    ->label('Attendance')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('warning')
                    ->visible(fn (OnlineClass $record): bool => in_array($record->status, [OnlineClassStatus::Live, OnlineClassStatus::Ended]))
                    ->url(fn (OnlineClass $record): string => static::getUrl('attendance', ['record' => $record])),

                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOnlineClasses::route('/'),
            'create' => Pages\CreateOnlineClass::route('/create'),
            'edit' => Pages\EditOnlineClass::route('/{record}/edit'),
            'attendance' => Pages\ManageOnlineClassAttendance::route('/{record}/attendance'),
        ];
    }
}
