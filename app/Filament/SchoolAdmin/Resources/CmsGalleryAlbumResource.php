<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CmsGalleryAlbumResource\Pages;
use App\Models\Tenant\CmsGalleryAlbum;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsGalleryAlbumResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsGalleryAlbum::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationLabel = 'Gallery Albums';

    protected static string | \UnitEnum | null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Album Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(2),

                        FileUpload::make('cover_image_path')
                            ->label('Cover Image')
                            ->image()
                            ->directory('cms/gallery/covers')
                            ->maxSize(3072)
                            ->helperText('Album cover shown on the gallery page.'),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),
                    ]),

                Section::make('Photos')
                    ->description('Upload photos for this album.')
                    ->schema([
                        Repeater::make('photos')
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->maxLength(255)
                                    ->placeholder('Photo caption (optional)'),

                                FileUpload::make('image_path')
                                    ->label('Photo')
                                    ->image()
                                    ->directory('cms/gallery/photos')
                                    ->maxSize(5120)
                                    ->required(),

                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->reorderable('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Photo')
                            ->addActionLabel('Add Photo'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_path')
                    ->label('Cover')
                    ->circular(false)
                    ->height(50),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('photos_count')
                    ->counts('photos')
                    ->label('Photos')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),

                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsGalleryAlbums::route('/'),
            'create' => Pages\CreateCmsGalleryAlbum::route('/create'),
            'edit' => Pages\EditCmsGalleryAlbum::route('/{record}/edit'),
        ];
    }
}
