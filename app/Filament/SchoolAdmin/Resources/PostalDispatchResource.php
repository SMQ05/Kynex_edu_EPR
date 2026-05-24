<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Concerns\PostalResourceShared;
use App\Filament\SchoolAdmin\Resources\PostalDispatchResource\Pages;
use App\Models\Tenant\PostalRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostalDispatchResource extends Resource
{
    use HasPermissionCheck;
    use PostalResourceShared;

    protected static string $rbacPermission = 'manage_postal';

    protected static ?string $model = PostalRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|\UnitEnum|null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Postal Dispatch';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('direction', PostalRecord::DIRECTION_DISPATCH);
    }

    public static function form(Schema $schema): Schema
    {
        return static::postalForm($schema, PostalRecord::DIRECTION_DISPATCH);
    }

    public static function table(Table $table): Table
    {
        return static::postalTable($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPostalDispatches::route('/'),
            'create' => Pages\CreatePostalDispatch::route('/create'),
            'edit'   => Pages\EditPostalDispatch::route('/{record}/edit'),
        ];
    }
}
