<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ChatInvitationResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\ChatInvitation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChatInvitationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'use_chat';

    protected static ?string $model = ChatInvitation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat Invitations';

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Invitation')
                ->columns(2)
                ->schema([
                    Select::make('inviter_id')
                        ->label('From')
                        ->options(fn (): array => SchoolUser::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Select::make('invitee_id')
                        ->label('To')
                        ->options(fn (): array => SchoolUser::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->different('inviter_id'),
                    Select::make('status')
                        ->options(ChatInvitation::STATUSES)
                        ->default('pending')
                        ->native(false),
                    Textarea::make('message')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inviter.name')->label('From')->searchable()->sortable(),
                TextColumn::make('invitee.name')->label('To')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success', 'declined' => 'danger', default => 'warning',
                    }),
                TextColumn::make('created_at')->dateTime('d M Y, H:i')->sortable()->toggleable(),
                TextColumn::make('responded_at')->dateTime('d M Y, H:i')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChatInvitation::STATUSES),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ChatInvitation $record): bool => $record->status === 'pending')
                    ->action(fn (ChatInvitation $record) => static::respond($record, 'accepted')),
                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ChatInvitation $record): bool => $record->status === 'pending')
                    ->action(fn (ChatInvitation $record) => static::respond($record, 'declined')),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    protected static function respond(ChatInvitation $record, string $status): void
    {
        $record->update(['status' => $status, 'responded_at' => now()]);
        Notification::make()->title('Invitation ' . $status)->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChatInvitations::route('/'),
            'create' => Pages\CreateChatInvitation::route('/create'),
            'edit'   => Pages\EditChatInvitation::route('/{record}/edit'),
        ];
    }
}
