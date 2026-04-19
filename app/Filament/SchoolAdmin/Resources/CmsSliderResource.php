<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\CmsSliderResource\Pages;
use App\Models\Tenant\CmsSlider;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsSliderResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = CmsSlider::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Hero Sliders';

    protected static string | \UnitEnum | null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Slider Details')
                    ->schema([
                        TextInput::make('title')
                            ->maxLength(255)
                            ->helperText('Main heading on the slide.'),

                        TextInput::make('subtitle')
                            ->maxLength(500)
                            ->helperText('Sub-heading or description text.'),

                        FileUpload::make('image_path')
                            ->label('Slide Image')
                            ->image()
                            ->directory('cms/sliders')
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Recommended: 1920x520px. Max 5MB.'),

                        TextInput::make('button_text')
                            ->maxLength(50)
                            ->helperText('Text for the call-to-action button (optional).'),

                        TextInput::make('button_url')
                            ->label('Button URL')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Link when button is clicked.'),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular(false)
                    ->height(60),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('subtitle')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

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
            'index' => Pages\ListCmsSliders::route('/'),
            'create' => Pages\CreateCmsSlider::route('/create'),
            'edit' => Pages\EditCmsSlider::route('/{record}/edit'),
        ];
    }
}
