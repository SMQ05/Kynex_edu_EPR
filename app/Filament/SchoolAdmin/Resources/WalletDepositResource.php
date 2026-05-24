<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\WalletDepositResource\Pages;
use App\Models\Tenant\WalletDeposit;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiExtractor;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Facades\Storage;

class WalletDepositResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_wallet';

    protected static ?string $model = WalletDeposit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-on-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Wallet Deposits';

    protected static ?int $navigationSort = 11;

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::where('status', 'pending')->count();
        } catch (\Throwable) {
            return null; // table not migrated yet — never break the panel nav
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Deposit')
                ->columns(2)
                ->schema([
                    Select::make('student_id')
                        ->label('Student')
                        ->relationship('student', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name . ' ' . $record->last_name))
                        ->searchable(['first_name', 'last_name', 'admission_number'])
                        ->preload()->required(),
                    TextInput::make('amount_paisas')
                        ->label('Amount (PKR)')
                        ->numeric()->required()->minValue(1)
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    Select::make('method')
                        ->options(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online' => 'Online'])
                        ->native(false)->nullable(),
                    TextInput::make('reference')->maxLength(255),
                    FileUpload::make('slip_path')
                        ->label('Deposit slip')
                        ->directory('wallet-slips')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->nullable(),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->date('d M Y')->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->getStateUsing(fn (WalletDeposit $r) => trim(($r->student->first_name ?? '') . ' ' . ($r->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2)),
                TextColumn::make('method')->badge()->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WalletDeposit::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(WalletDeposit::STATUSES)->default('pending'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('aiVerify')
                    ->label('AI Verify Slip')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (WalletDeposit $r): bool => AiAvailability::enabled() && filled($r->slip_path) && $r->status === 'pending')
                    ->action(fn (WalletDeposit $record) => static::verifySlip($record)),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (WalletDeposit $r): bool => $r->status === 'pending')
                    ->action(function (WalletDeposit $record): void {
                        try {
                            $record->approve();
                            Notification::make()->title('Deposit approved — wallet credited')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (WalletDeposit $r): bool => $r->status === 'pending')
                    ->action(function (WalletDeposit $record): void {
                        $record->reject();
                        Notification::make()->title('Deposit rejected')->success()->send();
                    }),
                EditAction::make()->visible(fn (WalletDeposit $r): bool => $r->status === 'pending'),
            ]);
    }

    protected static function verifySlip(WalletDeposit $record): void
    {
        try {
            $disk = config('filesystems.default', 'local');
            if (! Storage::disk($disk)->exists($record->slip_path)) {
                throw new \RuntimeException('Slip file not found.');
            }
            $data = app(AiExtractor::class)->extractFromFile(
                Storage::disk($disk)->path($record->slip_path),
                ['amount' => 'paid amount as a number', 'paid_on' => 'payment date YYYY-MM-DD', 'reference' => 'transaction/reference number'],
                'wallet_slip_verify',
            );

            $slipAmount = isset($data['amount']) ? (int) round((float) $data['amount'] * 100) : null;
            $match = $slipAmount !== null && abs($slipAmount - (int) $record->amount_paisas) <= 100; // within PKR 1

            Notification::make()
                ->title($match ? 'Slip matches the entered amount' : 'Review: slip may not match')
                ->body(sprintf(
                    'Slip: amount %s, date %s, ref %s.',
                    $slipAmount !== null ? 'PKR ' . number_format($slipAmount / 100, 2) : '—',
                    $data['paid_on'] ?? '—',
                    $data['reference'] ?? '—',
                ))
                ->color($match ? 'success' : 'warning')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI verify failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWalletDeposits::route('/'),
            'create' => Pages\CreateWalletDeposit::route('/create'),
            'edit'   => Pages\EditWalletDeposit::route('/{record}/edit'),
        ];
    }
}
