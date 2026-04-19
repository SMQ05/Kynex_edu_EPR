<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineClassResource\Pages;

use App\Filament\SchoolAdmin\Resources\OnlineClassResource;
use App\Models\Tenant\OnlineClass;
use App\Models\Tenant\OnlineClassAttendance;
use App\Models\Tenant\Student;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageOnlineClassAttendance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = OnlineClassResource::class;

    protected string $view = 'filament.school-admin.pages.online-class-attendance';

    public OnlineClass $record;

    public function getTitle(): string
    {
        return "Attendance: {$this->record->title}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OnlineClassAttendance::query()
                    ->where('online_class_id', $this->record->id)
                    ->with('student')
            )
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student Name')
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->sortable(),

                TextColumn::make('joined_at')
                    ->dateTime('h:i A')
                    ->label('Joined At')
                    ->placeholder('—'),

                TextColumn::make('left_at')
                    ->dateTime('h:i A')
                    ->label('Left At')
                    ->placeholder('—'),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('—'),

                SelectColumn::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                    ])
                    ->label('Status'),
            ])
            ->headerActions([
                Action::make('populate_students')
                    ->label('Populate All Students')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('This will add all enrolled students from this class/section to the attendance list as absent.')
                    ->action(function (): void {
                        $students = Student::where('class_id', $this->record->class_id)
                            ->when($this->record->section_id, fn ($q) => $q->where('section_id', $this->record->section_id))
                            ->where('status', 'enrolled')
                            ->get();

                        $count = 0;
                        foreach ($students as $student) {
                            $created = OnlineClassAttendance::firstOrCreate(
                                [
                                    'online_class_id' => $this->record->id,
                                    'student_id' => $student->id,
                                ],
                                [
                                    'status' => 'absent',
                                ],
                            );
                            if ($created->wasRecentlyCreated) {
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title("{$count} students added to attendance list")
                            ->success()
                            ->send();
                    }),

                Action::make('mark_all_present')
                    ->label('Mark All Present')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        OnlineClassAttendance::where('online_class_id', $this->record->id)
                            ->update([
                                'status' => 'present',
                                'joined_at' => $this->record->actual_start_at ?? $this->record->scheduled_at,
                            ]);

                        Notification::make()
                            ->title('All students marked as present')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('toggle_status')
                    ->label(fn (OnlineClassAttendance $record): string => match ($record->status) {
                        'absent' => 'Mark Present',
                        'present' => 'Mark Absent',
                        'late' => 'Mark Present',
                        default => 'Toggle',
                    })
                    ->icon(fn (OnlineClassAttendance $record): string => match ($record->status) {
                        'absent' => 'heroicon-o-check',
                        'present' => 'heroicon-o-x-mark',
                        'late' => 'heroicon-o-check',
                        default => 'heroicon-o-arrow-path',
                    })
                    ->color(fn (OnlineClassAttendance $record): string => match ($record->status) {
                        'absent' => 'success',
                        'present' => 'danger',
                        'late' => 'success',
                        default => 'gray',
                    })
                    ->action(function (OnlineClassAttendance $record): void {
                        $newStatus = $record->status === 'present' ? 'absent' : 'present';
                        $record->update([
                            'status' => $newStatus,
                            'joined_at' => $newStatus === 'present' ? now() : null,
                        ]);
                    }),
            ]);
    }
}
