<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\SignupStatus;
use App\Models\TenantSignup;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSignupsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Latest Signups';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TenantSignup::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('school_name')
                    ->label('School Name')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-phone')
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SignupStatus $state): string => $state->label())
                    ->color(fn (SignupStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Signed Up')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('sendInvite')
                    ->label('Send Invite')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invite')
                    ->modalDescription(fn (TenantSignup $record): string => "Send an invitation to {$record->school_name} ({$record->email})?")
                    ->action(function (TenantSignup $record): void {
                        $record->update([
                            'status' => SignupStatus::Invited,
                            'invited_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Invite Sent')
                            ->body("Invitation marked as sent for {$record->school_name}.")
                            ->success()
                            ->send();
                    })
                    ->hidden(fn (TenantSignup $record): bool => in_array($record->status, [
                        SignupStatus::Invited,
                        SignupStatus::Onboarded,
                        SignupStatus::Rejected,
                    ])),
            ])
            ->paginated(false);
    }
}
