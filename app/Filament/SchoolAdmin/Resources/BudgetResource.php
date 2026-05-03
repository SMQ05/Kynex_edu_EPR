<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\BudgetResource\Pages;
use App\Filament\SchoolAdmin\Resources\BudgetResource\Widgets\BudgetOverviewWidget;
use App\Models\Tenant\Budget;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_budget';

    protected static ?string $model = Budget::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Budgets';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Budget Details')
                    ->schema([
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Expense Category'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('budgeted_amount_paisas')
                            ->label('Budgeted Amount (PKR)')
                            ->numeric()
                            ->required()
                            ->prefix('PKR')
                            ->step('0.01')
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) ($state ?? 0)) * 100))
                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null),

                        Textarea::make('notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('budgeted_amount_paisas')
                    ->label('Budgeted')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('spent_amount_paisas')
                    ->label('Spent')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('remaining_paisas')
                    ->label('Remaining')
                    ->getStateUsing(fn (Budget $record): string => 'PKR ' . number_format($record->remaining_paisas / 100, 2))
                    ->color(fn (Budget $record): string => $record->remaining_paisas < 0 ? 'danger' : 'success'),

                TextColumn::make('utilization_percent')
                    ->label('Utilization %')
                    ->getStateUsing(fn (Budget $record): string => number_format($record->utilization_percent, 1) . '%')
                    ->badge()
                    ->color(fn (Budget $record): string => match (true) {
                        $record->utilization_percent > 90 => 'danger',
                        $record->utilization_percent >= 70 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->label('Academic Year'),

                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            BudgetOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
