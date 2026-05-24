<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\CustomFieldResource\Pages;
use App\Models\Tenant\CustomField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CustomFieldResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_custom_fields';

    protected static ?string $model = CustomField::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Custom Fields';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Field Definition')
                ->columns(2)
                ->schema([
                    Select::make('entity')
                        ->label('Applies to')
                        ->options(CustomField::ENTITIES)
                        ->default('student')
                        ->required()
                        ->native(false),

                    Select::make('type')
                        ->label('Field type')
                        ->options(CustomField::TYPES)
                        ->default('text')
                        ->required()
                        ->live()
                        ->native(false),

                    TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            // Auto-suggest a machine key from the label when key is empty.
                            if (blank($get('key')) && filled($state)) {
                                $set('key', Str::slug($state, '_'));
                            }
                        }),

                    TextInput::make('key')
                        ->label('Machine key')
                        ->required()
                        ->maxLength(120)
                        ->helperText('Unique per entity. Lowercase letters, numbers and underscores.')
                        ->rule('regex:/^[a-z0-9_]+$/'),

                    Toggle::make('required')->label('Required'),
                    Toggle::make('is_active')->label('Active')->default(true),

                    TextInput::make('sort')
                        ->numeric()
                        ->default(0)
                        ->label('Sort order'),

                    Textarea::make('help_text')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Dropdown Options')
                ->description('Only used when the field type is "Dropdown (select)".')
                ->visible(fn (Get $get): bool => $get('type') === 'select')
                ->schema([
                    Repeater::make('options')
                        ->label('Options')
                        ->schema([
                            TextInput::make('value')->required()->maxLength(120),
                            TextInput::make('label')->maxLength(120)
                                ->helperText('Leave blank to use the value as the label.'),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->addActionLabel('Add option'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('entity')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CustomField::ENTITIES[$state] ?? $state),
                TextColumn::make('key')->badge()->color('gray')->toggleable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => CustomField::TYPES[$state] ?? $state),
                IconColumn::make('required')->boolean(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('entity')->options(CustomField::ENTITIES),
                SelectFilter::make('type')->options(CustomField::TYPES),
            ])
            ->defaultSort('sort')
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
            'index'  => Pages\ListCustomFields::route('/'),
            'create' => Pages\CreateCustomField::route('/create'),
            'edit'   => Pages\EditCustomField::route('/{record}/edit'),
        ];
    }
}
