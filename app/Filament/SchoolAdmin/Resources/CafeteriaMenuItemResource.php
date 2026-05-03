<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\CafeteriaMenuItem;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class CafeteriaMenuItemResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_menu';

    protected static ?string $model = CafeteriaMenuItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cake';

    protected static string | \UnitEnum | null $navigationGroup = 'Cafeteria';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Menu Items';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Item Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('category')
                    ->options([
                        'breakfast' => 'Breakfast',
                        'lunch' => 'Lunch',
                        'snack' => 'Snack',
                        'beverage' => 'Beverage',
                        'general' => 'General',
                    ])
                    ->default('general')
                    ->required(),

                TextInput::make('price_paisas')
                    ->label('Price (PKR)')
                    ->numeric()
                    ->required()
                    ->prefix('PKR')
                    ->helperText('Enter price in paisas (e.g. 15000 = PKR 150.00)'),

                \App\Support\CampusField::make(required: false),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Nutritional & Preparation Info')->schema([
                TextInput::make('calories')
                    ->numeric()
                    ->suffix('kcal'),

                TextInput::make('preparation_time_minutes')
                    ->numeric()
                    ->suffix('min'),

                TextInput::make('allergens')
                    ->placeholder('e.g. nuts, dairy, gluten')
                    ->helperText('Comma-separated list'),

                Toggle::make('is_vegetarian')
                    ->label('Vegetarian'),
            ])->columns(2),

            Section::make('Display & Availability')->schema([
                FileUpload::make('image_path')
                    ->label('Item Image')
                    ->image()
                    ->directory('cafeteria-items')
                    ->maxSize(2048),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_available')
                    ->label('Available for Sale')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'breakfast' => 'warning',
                        'lunch' => 'success',
                        'snack' => 'info',
                        'beverage' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price_paisas')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('calories')
                    ->suffix(' kcal')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_vegetarian')
                    ->boolean()
                    ->label('Veg'),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available'),
                Tables\Columns\TextColumn::make('campus.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'breakfast' => 'Breakfast',
                        'lunch' => 'Lunch',
                        'snack' => 'Snack',
                        'beverage' => 'Beverage',
                        'general' => 'General',
                    ]),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Available'),
                Tables\Filters\TernaryFilter::make('is_vegetarian')
                    ->label('Vegetarian'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => CafeteriaMenuItemResource\Pages\ListCafeteriaMenuItems::route('/'),
            'create' => CafeteriaMenuItemResource\Pages\CreateCafeteriaMenuItem::route('/create'),
            'edit'   => CafeteriaMenuItemResource\Pages\EditCafeteriaMenuItem::route('/{record}/edit'),
        ];
    }
}
