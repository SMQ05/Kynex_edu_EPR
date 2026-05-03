<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\ExpenseCategoryResource\Pages;
use App\Models\Tenant\ExpenseCategory;
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

class ExpenseCategoryResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_expenses';

    protected static ?string $model = ExpenseCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Expense Categories';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->options(fn (?ExpenseCategory $record) => ExpenseCategory::query()
                                ->whereNull('parent_id')
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('None (top-level)')
                            ->helperText('Max 2 levels: top-level or child of a top-level category.'),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->placeholder('— Top Level —')
                    ->sortable(),

                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Sub-categories'),

                TextColumn::make('expenses_count')
                    ->counts('expenses')
                    ->label('Expenses'),

                TextColumn::make('budgets_count')
                    ->counts('budgets')
                    ->label('Budgets'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Level')
                    ->options([
                        'top' => 'Top-level Only',
                        'child' => 'Sub-categories Only',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'top' => $query->whereNull('parent_id'),
                            'child' => $query->whereNotNull('parent_id'),
                            default => $query,
                        };
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseCategorys::route('/'),
            'create' => Pages\CreateExpenseCategory::route('/create'),
            'edit' => Pages\EditExpenseCategory::route('/{record}/edit'),
        ];
    }
}
