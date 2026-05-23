<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\SaasAdmin\Resources\ApprovalRequestResource\Pages;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Approval Requests';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Request Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('tenant_id')
                        ->label('Tenant')
                        ->disabled(),

                    Components\TextInput::make('action_type')
                        ->label('Action Type')
                        ->disabled(),

                    Components\TextInput::make('requested_by_type')
                        ->label('Requester Type')
                        ->disabled(),

                    Components\TextInput::make('requested_by_id')
                        ->label('Requester ID')
                        ->disabled(),

                    Components\TextInput::make('subject_type')
                        ->label('Subject Type')
                        ->disabled()
                        ->columnSpanFull(),

                    Components\KeyValue::make('payload')
                        ->label('Payload')
                        ->disabled()
                        ->columnSpanFull(),

                    Components\Select::make('status')
                        ->options(ApprovalStatus::class)
                        ->disabled(),

                    Components\Textarea::make('review_notes')
                        ->label('Admin Note')
                        ->rows(3)
                        ->columnSpanFull(),

                    Components\DateTimePicker::make('expires_at')
                        ->disabled(),

                    Components\DateTimePicker::make('reviewed_at')
                        ->disabled(),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action_type')
                    ->label('Action')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approver_level')
                    ->label('Approver Level')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'institute_head_or_saas' => 'warning',
                        'multi_head_or_saas'     => 'danger',
                        'saas_admin'             => 'danger',
                        default                  => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('requested_by_type')
                    ->label('Requester Type')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ApprovalStatus::class),
                Tables\Filters\SelectFilter::make('approver_level')
                    ->label('Approver Level')
                    ->options([
                        'institute_head'         => 'Institute Head',
                        'institute_head_or_saas' => 'Institute Head OR SaaS (elevated)',
                        'multi_head_or_saas'     => 'Multi-Campus Head OR SaaS (elevated)',
                        'saas_admin'             => 'SaaS Admin only',
                        'exam_admin'             => 'Exam Admin',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Components\Textarea::make('admin_note')
                            ->label('Note (optional)')
                            ->rows(3),
                    ])
                    ->action(function (ApprovalRequest $record, array $data): void {
                        app(ApprovalService::class)->approve(
                            request: $record,
                            reviewer: auth()->user(),
                            adminNote: $data['admin_note'] ?? null,
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
                            reviewer: auth()->user(),
                            adminNote: $data['admin_note'],
                        );
                        Notification::make()
                            ->title('Request Rejected')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (ApprovalRequest $record): bool => $record->status === ApprovalStatus::Pending),

                EditAction::make(),
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
            'index' => Pages\ListApprovalRequests::route('/'),
            'edit'  => Pages\EditApprovalRequest::route('/{record}/edit'),
        ];
    }
}
