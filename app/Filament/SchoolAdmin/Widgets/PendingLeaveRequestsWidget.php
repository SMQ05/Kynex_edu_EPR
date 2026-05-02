<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Enums\LeaveStatus;
use App\Models\Tenant\LeaveRequest;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingLeaveRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Pending Leave Requests';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveRequest::query()
                    ->where('status', LeaveStatus::Pending->value)
                    ->with(['schoolUser', 'leaveType'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('schoolUser.name')
                    ->label('Staff Member'),

                TextColumn::make('leaveType.name')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('from_date')
                    ->date('d M')
                    ->label('From'),

                TextColumn::make('total_days')
                    ->label('Days')
                    ->suffix('d'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status'      => LeaveStatus::Approved,
                            'reviewed_by' => auth()->guard('school_users')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Leave approved.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status'      => LeaveStatus::Rejected,
                            'reviewed_by' => auth()->guard('school_users')->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Leave rejected.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->paginated(false);
    }
}
