<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Notifications\Notification;

class ApprovalQueue extends Page implements HasTable
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_audit_logs';

    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    /**
     * Restrict access to SCHOOL_ADMIN and INSTITUTE_HEAD roles only.
     * Phase 9.3 — prevents any authenticated user from approving/rejecting requests.
     */
    public static function canAccess(): bool
    {
        $user = auth()->guard('school_users')->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']);
    }

    protected static ?string $navigationLabel = 'Approval Queue';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.school-admin.pages.approval-queue';

    public static function getNavigationBadge(): ?string
    {
        if (! tenancy()->initialized) {
            return null;
        }

        $count = ApprovalRequest::pending()
            ->forTenant(tenant()->id)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApprovalRequest::query()
                    ->when(
                        tenancy()->initialized,
                        fn ($q) => $q->forTenant(tenant()->id),
                    )
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('action_type')
                    ->label('Action')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('requested_by_type')
                    ->label('Requested By Type')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ApprovalStatus $state): string => match ($state) {
                        ApprovalStatus::Pending   => 'warning',
                        ApprovalStatus::Approved  => 'success',
                        ApprovalStatus::Rejected  => 'danger',
                        ApprovalStatus::Cancelled => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('review_notes')
                    ->label('Note')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ApprovalStatus::class),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Components\Textarea::make('admin_note')
                            ->label('Reason for approval')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ApprovalRequest $record, array $data): void {
                        app(ApprovalService::class)->approve(
                            request: $record,
                            reviewer: auth()->guard('school_users')->user(),
                            adminNote: $data['admin_note'],
                        );
                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::Pending),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Components\Textarea::make('admin_note')
                            ->label('Reason for rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ApprovalRequest $record, array $data): void {
                        app(ApprovalService::class)->reject(
                            request: $record,
                            reviewer: auth()->guard('school_users')->user(),
                            adminNote: $data['admin_note'],
                        );
                        Notification::make()
                            ->title('Request Rejected')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::Pending),

                Action::make('viewPayload')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Request Payload')
                    ->modalContent(fn (ApprovalRequest $record) => view(
                        'filament.school-admin.pages.approval-payload',
                        ['payload' => $record->payload, 'record' => $record],
                    ))
                    ->modalSubmitAction(false),
            ]);
    }
}
