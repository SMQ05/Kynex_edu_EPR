<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\BankPaymentRequestResource\Pages;
use App\Models\Tenant\BankPaymentRequest;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiExtractor;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Storage;

/**
 * Bank-Payment approval. A parent/admin submits a bank-transfer fee
 * payment with a slip; admin AI-verifies, then approves → records a real
 * FeePayment against the student's outstanding fees (oldest first).
 */
class BankPaymentRequestResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'record_fee_payment';

    protected static ?string $model = BankPaymentRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|\UnitEnum|null $navigationGroup = 'Fees';

    protected static ?string $navigationLabel = 'Bank Payments';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getModel()::where('status', 'pending')->count();
        } catch (\Throwable) {
            return null; // table not migrated yet — never break the panel nav
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Bank payment')
                ->columns(2)
                ->schema([
                    Select::make('student_id')
                        ->label('Student')
                        ->relationship('student', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn (Student $record): string => trim($record->first_name . ' ' . $record->last_name))
                        ->searchable(['first_name', 'last_name', 'admission_number'])
                        ->preload()
                        ->required()
                        ->live()
                        ->helperText(fn ($get): string => static::outstandingHint($get('student_id'))),
                    TextInput::make('amount_paisas')
                        ->label('Amount (PKR)')
                        ->numeric()->required()->minValue(1)
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    TextInput::make('bank_reference')
                        ->label('Bank reference / transaction ID')
                        ->maxLength(255),
                    DatePicker::make('paid_on')
                        ->label('Paid on')
                        ->default(now()),
                    FileUpload::make('slip_path')
                        ->label('Deposit slip')
                        ->directory('bank-payment-slips')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->date('d M Y')->sortable(),
                TextColumn::make('student')
                    ->label('Student')
                    ->getStateUsing(fn (BankPaymentRequest $r) => trim(($r->student->first_name ?? '') . ' ' . ($r->student->last_name ?? '')))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('bank_reference')->label('Reference')->placeholder('—'),
                TextColumn::make('paid_on')->date('d M Y')->placeholder('—'),
                TextColumn::make('receipt_number')->label('Receipt')->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BankPaymentRequest::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options(BankPaymentRequest::STATUSES)->default('pending'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('aiVerify')
                    ->label('AI Verify Slip')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (BankPaymentRequest $r): bool => AiAvailability::enabled() && filled($r->slip_path) && $r->status === 'pending')
                    ->action(fn (BankPaymentRequest $record) => static::verifySlip($record)),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Approving records a bank FeePayment applied to this student\'s outstanding fees (oldest first) and prints a receipt number.')
                    ->visible(fn (BankPaymentRequest $r): bool => $r->status === 'pending')
                    ->action(function (BankPaymentRequest $record): void {
                        try {
                            $record->approve();
                            Notification::make()
                                ->title('Approved — payment recorded')
                                ->body('Receipt ' . ($record->receipt_number ?? '—'))
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BankPaymentRequest $r): bool => $r->status === 'pending')
                    ->action(function (BankPaymentRequest $record): void {
                        $record->reject();
                        Notification::make()->title('Rejected')->success()->send();
                    }),
                EditAction::make()->visible(fn (BankPaymentRequest $r): bool => $r->status === 'pending'),
            ]);
    }

    protected static function outstandingHint(?string $studentId): string
    {
        if (! $studentId) {
            return 'Pick a student to see their outstanding balance.';
        }

        $outstanding = (int) StudentFee::query()
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('coalesce(sum(amount_paisas + fine_paisas - discount_paisas - paid_paisas), 0) as t')
            ->value('t');

        return 'Outstanding: PKR ' . number_format(max(0, $outstanding) / 100, 2);
    }

    protected static function verifySlip(BankPaymentRequest $record): void
    {
        try {
            $disk = config('filesystems.default', 'local');
            if (! Storage::disk($disk)->exists($record->slip_path)) {
                throw new \RuntimeException('Slip file not found.');
            }
            $data = app(AiExtractor::class)->extractFromFile(
                Storage::disk($disk)->path($record->slip_path),
                ['amount' => 'paid amount as a number', 'paid_on' => 'payment date YYYY-MM-DD', 'reference' => 'transaction/reference number'],
                'bank_payment_slip_verify',
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
            'index'  => Pages\ListBankPaymentRequests::route('/'),
            'create' => Pages\CreateBankPaymentRequest::route('/create'),
            'edit'   => Pages\EditBankPaymentRequest::route('/{record}/edit'),
        ];
    }
}
