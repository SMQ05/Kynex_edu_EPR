<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\IncomeResource\Pages;
use App\Models\Tenant\AccountHead;
use App\Models\Tenant\Income;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiClassifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncomeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_accounts';

    protected static ?string $model = Income::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Income';

    protected static ?int $navigationSort = 5;

    /** @return array<string,string> */
    protected static function paymentMethods(): array
    {
        return [
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'online'        => 'Online',
            'other'         => 'Other',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Income')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    Select::make('account_head_id')
                        ->label('Income head')
                        ->options(fn (): array => AccountHead::query()->whereIn('type', ['income', 'asset'])->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->native(false)->nullable(),
                    Select::make('bank_account_id')
                        ->label('Deposit to account')
                        ->relationship('bankAccount', 'name')
                        ->searchable()->preload()->nullable()
                        ->helperText('Credits the chosen bank/cash account balance.'),
                    TextInput::make('amount_paisas')
                        ->label('Amount (PKR)')
                        ->numeric()->required()->minValue(0)
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    DatePicker::make('income_date')->default(now())->required(),
                    Select::make('payment_method')->options(static::paymentMethods())->native(false)->nullable(),
                    TextInput::make('payer')->maxLength(255),
                    TextInput::make('reference_number')->maxLength(255),
                    FileUpload::make('receipt_path')->label('Receipt')->directory('income-receipts')->nullable(),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('income_date')->date('d M Y')->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('accountHead.name')->label('Head')->badge()->placeholder('—')->toggleable(),
                TextColumn::make('bankAccount.name')->label('Account')->placeholder('—')->toggleable(),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('payer')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('account_head_id')->relationship('accountHead', 'name')->label('Head'),
                SelectFilter::make('bank_account_id')->relationship('bankAccount', 'name')->label('Account'),
            ])
            ->defaultSort('income_date', 'desc')
            ->actions([
                Action::make('aiCategorize')
                    ->label('AI Categorize')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->action(fn (Income $record) => static::runCategorize($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    protected static function runCategorize(Income $record): void
    {
        try {
            $heads = AccountHead::query()->whereIn('type', ['income', 'asset'])->active()->orderBy('name')->pluck('name', 'id');
            if ($heads->isEmpty()) {
                Notification::make()->title('No income heads')->body('Create some Chart of Accounts heads first.')->warning()->send();

                return;
            }
            $text = trim($record->title . '. ' . (string) $record->description);
            $pick = app(AiClassifier::class)->classify($text, $heads->values()->all(), 'income_categorize', 'income head');
            $matchedId = array_search($pick['label'], $heads->all(), true);
            if ($matchedId !== false) {
                $record->update(['account_head_id' => $matchedId]);
                Notification::make()->title('Categorised as: ' . $pick['label'])->success()->send();
            } else {
                Notification::make()->title('No confident match')->warning()->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('AI categorize failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit'   => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
