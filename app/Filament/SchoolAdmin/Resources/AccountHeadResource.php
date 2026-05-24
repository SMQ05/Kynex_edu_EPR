<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AccountHeadResource\Pages;
use App\Models\Tenant\AccountHead;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountHeadResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_accounts';

    protected static ?string $model = AccountHead::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Account Head')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('code')->maxLength(40),
                    Select::make('type')->options(AccountHead::TYPES)->default('income')->required()->native(false),
                    Select::make('parent_id')
                        ->label('Parent head')
                        ->relationship('parent', 'name')
                        ->searchable()->preload()->nullable(),
                    Toggle::make('is_active')->default(true),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->placeholder('—')->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AccountHead::TYPES[$state] ?? $state),
                TextColumn::make('parent.name')->label('Parent')->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options(AccountHead::TYPES),
            ])
            ->defaultSort('type')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccountHeads::route('/'),
            'create' => Pages\CreateAccountHead::route('/create'),
            'edit'   => Pages\EditAccountHead::route('/{record}/edit'),
        ];
    }
}
