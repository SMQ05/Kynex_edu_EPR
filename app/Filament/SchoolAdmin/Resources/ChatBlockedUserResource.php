<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ChatBlockedUserResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\ChatBlockedUser;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChatBlockedUserResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'use_chat';

    protected static ?string $model = ChatBlockedUser::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Blocked Users';

    protected static ?int $navigationSort = 23;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Block')
                ->columns(2)
                ->schema([
                    Select::make('blocker_id')
                        ->label('Blocker')
                        ->options(fn (): array => SchoolUser::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Select::make('blocked_id')
                        ->label('Blocked user')
                        ->options(fn (): array => SchoolUser::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->different('blocker_id'),
                    TextInput::make('reason')->maxLength(255)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('blocker.name')->label('Blocker')->searchable()->sortable(),
                TextColumn::make('blocked.name')->label('Blocked')->searchable()->sortable(),
                TextColumn::make('reason')->placeholder('—')->toggleable()->wrap(),
                TextColumn::make('created_at')->dateTime('d M Y, H:i')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make()->label('Unblock'),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChatBlockedUsers::route('/'),
            'create' => Pages\CreateChatBlockedUser::route('/create'),
            'edit'   => Pages\EditChatBlockedUser::route('/{record}/edit'),
        ];
    }
}
