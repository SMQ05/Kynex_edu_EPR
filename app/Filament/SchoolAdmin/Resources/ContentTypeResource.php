<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ContentTypeResource\Pages;
use App\Models\Tenant\ContentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentTypeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_download_center';

    protected static ?string $model = ContentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Content Types';

    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Content Type')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('slug')->maxLength(80)->placeholder('auto if blank'),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('contents_count')->counts('contents')->label('Contents')->badge(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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
            'index'  => Pages\ListContentTypes::route('/'),
            'create' => Pages\CreateContentType::route('/create'),
            'edit'   => Pages\EditContentType::route('/{record}/edit'),
        ];
    }
}
