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

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

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
                            ->minValue(0.01)
                            ->step('0.01')
                            ->required()
                            ->prefix('PKR')
                            ->helperText('Enter the rupee amount — will be stored as paisas internally.')
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                if ($record) {
                                    $component->state(number_format($record->amount_paisas / 100, 2, '.', ''));
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
                    ->formatStateUsing(fn ($state): string => ucfirst(
                        $state instanceof \BackedEnum ? $state->value : (string) $state,
                    ))
                    ->color(function ($state): string {
                        $v = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        return match ($v) {
                            'approved' => 'success',
                            'pending'  => 'warning',
                            'rejected' => 'danger',
                            default    => 'gray',
                        };
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
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),

                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $u = auth('school_users')->user() ?? auth()->user();
                        if (! $u) return false;
                        $role = (string) ($u->active_role ?? $u->roles?->first()?->name ?? '');
                        return in_array($role, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true)
                            && (($record->approval_status instanceof \BackedEnum ? $record->approval_status->value : (string) $record->approval_status) === 'pending');
                    })
                    ->requiresConfirmation()
                    ->modalDescription('Approve this expense and reflect it in finance reports?')
                    ->action(function ($record) {
                        $u = auth('school_users')->user() ?? auth()->user();
                        $record->update([
                            'approval_status' => \App\Enums\ExpenseApprovalStatus::Approved->value,
                            'approved_by'     => $u?->id,
                        ]);
                        if ($record->budget_id) {
                            if ($budget = \App\Models\Tenant\Budget::find($record->budget_id)) {
                                $budget->increment('spent_amount_paisas', $record->amount_paisas);
                            }
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Expense approved')->success()->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record): bool {
                        $u = auth('school_users')->user() ?? auth()->user();
                        if (! $u) return false;
                        $role = (string) ($u->active_role ?? $u->roles?->first()?->name ?? '');
                        return in_array($role, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true)
                            && (($record->approval_status instanceof \BackedEnum ? $record->approval_status->value : (string) $record->approval_status) === 'pending');
                    })
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->required()->minLength(5)->rows(2)
                            ->label('Reason for rejection'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'approval_status' => \App\Enums\ExpenseApprovalStatus::Rejected->value,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Expense rejected')->danger()->send();
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
