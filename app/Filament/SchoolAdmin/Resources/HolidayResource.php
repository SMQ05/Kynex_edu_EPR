<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\HolidayResource\Pages;
use App\Models\Tenant\Holiday;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = Holiday::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Holidays';

    protected static ?int $navigationSort = 61;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Holiday')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),

                    DatePicker::make('start_date')
                        ->required()
                        ->default(now())
                        ->live(),

                    DatePicker::make('end_date')
                        ->required()
                        ->default(now())
                        ->minDate(fn (Get $get) => $get('start_date'))
                        ->rule(fn (Get $get): string => 'after_or_equal:' . ($get('start_date') ?: now()->toDateString())),

                    Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('start_date')->date('d M Y')->sortable(),
                TextColumn::make('end_date')->date('d M Y')->sortable(),
                TextColumn::make('days')->label('Days')->badge()->color('info'),
                TextColumn::make('description')->limit(60)->wrap()->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
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
            'index'  => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit'   => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
