<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\ExpenseApprovalStatus;
use App\Enums\PaymentMethod;
use App\Filament\SchoolAdmin\Resources\ExpenseResource\Pages;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Expense;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_expenses';

    protected static ?string $model = Expense::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees & Finance';

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('budget_id', null))
                            ->label('Expense Category'),

                        Select::make('budget_id')
                            ->label('Budget')
                            ->options(function (callable $get) {
                                $categoryId = $get('category_id');
                                if (! $categoryId) {
                                    return [];
                                }
                                return Budget::where('category_id', $categoryId)
                                    ->pluck('title', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select budget (filtered by category)'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),

                        TextInput::make('amount_pkr')
                            ->label('Amount (PKR)')
                            ->numeric()
                            ->required()
                            ->prefix('PKR')
                            ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                            ->formatStateUsing(fn ($state, $record) => $record ? $record->amount_paisas / 100 : 0)
                            ->dehydrated(false)
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                if ($record) {
                                    $component->state($record->amount_paisas / 100);
                                }
                            }),

                        DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),

                        Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->required(),

                        TextInput::make('reference_number')
                            ->maxLength(100)
                            ->placeholder('Cheque #, transaction ID, etc.'),

                        FileUpload::make('receipt_path')
                            ->label('Receipt')
                            ->directory('expense-receipts')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->badge()
                    ->sortable(),

                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (ExpenseApprovalStatus $state): string => match ($state) {
                        ExpenseApprovalStatus::Approved => 'success',
                        ExpenseApprovalStatus::Pending => 'warning',
                        ExpenseApprovalStatus::Rejected => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('recorder.name')
                    ->label('Recorded By')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),

                SelectFilter::make('approval_status')
                    ->options(ExpenseApprovalStatus::class),

                SelectFilter::make('payment_method')
                    ->options(PaymentMethod::class),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('expense_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('expense_date', '<=', $date));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
