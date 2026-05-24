<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\CmsMenuResource\Pages;
use App\Filament\SchoolAdmin\Resources\CmsMenuResource\RelationManagers\ItemsRelationManager;
use App\Models\Tenant\CmsMenu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsMenuResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsMenu::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?string $navigationLabel = 'Menus';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Menu')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('location')
                        ->options(CmsMenu::LOCATIONS)
                        ->native(false)
                        ->helperText('Where this menu renders on the public website.'),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('location')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (CmsMenu::LOCATIONS[$state] ?? $state) : '—'),
                TextColumn::make('items_count')->counts('items')->label('Items')->badge()->color('info'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCmsMenus::route('/'),
            'create' => Pages\CreateCmsMenu::route('/create'),
            'edit'   => Pages\EditCmsMenu::route('/{record}/edit'),
        ];
    }
}
