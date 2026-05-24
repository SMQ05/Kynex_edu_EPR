<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\AttendanceClerk;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\InAppNotification;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AttendanceClerkRecentAbsentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Absences — Notify Parents';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AttendanceRecord::query()
                    ->with(['student', 'schoolClass', 'section'])
                    ->where('status', 'absent')
                    ->whereDate('date', '>=', now()->subDays(7))
                    ->latest('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['student.first_name', 'student.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('notified_at')
                    ->label('Notified')
                    ->boolean()
                    ->getStateUsing(fn (AttendanceRecord $record): bool => $record->notified_at !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('notifyParent')
                    ->label('Notify Parent')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->hidden(fn (AttendanceRecord $record): bool => $record->notified_at !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Notify Parent?')
                    ->modalDescription(fn (AttendanceRecord $record): string =>
                        "Send absence notification for {$record->student->full_name} on {$record->date->format('d M Y')} to their guardians?"
                    )
                    ->action(function (AttendanceRecord $record) {
                        // Send in-app notification to linked guardians
                        $student = $record->student;
                        $guardians = $student->guardians;

                        foreach ($guardians as $guardian) {
                            if ($guardian->school_user_id) {
                                InAppNotification::create([
                                    'user_id'    => $guardian->school_user_id,
                                    'title'      => 'Absence Notification',
                                    'body'       => "{$student->full_name} was marked absent on {$record->date->format('d M Y')}.",
                                    'type'       => 'attendance_absence',
                                    'action_url' => null,
                                ]);
                            }
                        }

                        // Mark as notified
                        $record->update(['notified_at' => now()]);

                        Notification::make()
                            ->title('Parent Notified')
                            ->body("Absence notification sent for {$student->full_name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->poll('30s');
    }
}
