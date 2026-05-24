<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\FrontOfficeReferenceResource\Pages;
use App\Models\Tenant\FrontOfficeReference;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
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

/**
 * Front Office Setup — manage the shared dropdown lists (complaint types,
 * enquiry sources, references, postal types, visit/call purposes) used
 * across the Front Office module.
 */
class FrontOfficeReferenceResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_front_office_setup';

    protected static ?string $model = FrontOfficeReference::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Front Office';

    protected static ?string $navigationLabel = 'Front Office Setup';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Reference')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options(FrontOfficeReference::TYPES)
                        ->required()
                        ->native(false),
                    TextInput::make('name')->required()->maxLength(255),
                    ColorPicker::make('color')->nullable(),
                    TextInput::make('sort_order')->numeric()->default(0),
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
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FrontOfficeReference::TYPES[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('sort_order')->sortable()->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options(FrontOfficeReference::TYPES),
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
            'index'  => Pages\ListFrontOfficeReferences::route('/'),
            'create' => Pages\CreateFrontOfficeReference::route('/create'),
            'edit'   => Pages\EditFrontOfficeReference::route('/{record}/edit'),
        ];
    }
}
