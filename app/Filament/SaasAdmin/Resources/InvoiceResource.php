<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\SaasAdmin\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoices';

    protected static string | \UnitEnum | null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Invoice Details ─────────────────────────────────
            Section::make('Invoice Details')
                ->columns(2)
                ->schema([
                    Components\Select::make('tenant_id')
                        ->label('School')
                        ->relationship('tenant', 'school_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $tenant = Tenant::find($state);
                                if ($tenant) {
                                    $set('active_student_count', $tenant->active_student_count);
                                }
                            }
                        }),

                    Components\TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('Format: KNX-YYYY-MM-slug'),

                    Components\DatePicker::make('period_start')
                        ->label('Period Start')
                        ->required(),

                    Components\DatePicker::make('period_end')
                        ->label('Period End')
                        ->required(),

                    Components\TextInput::make('active_student_count')
                        ->label('Active Students')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Components\Select::make('status')
                        ->options(InvoiceStatus::class)
                        ->required()
                        ->native(false)
                        ->default(InvoiceStatus::Draft),
                ]),

            // ── Amounts (PKR) ───────────────────────────────────
            Section::make('Amounts (PKR)')
                ->description('Enter all amounts in PKR. Stored internally as paisas.')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('base_amount_pkr')
                        ->label('Base Amount')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->base_amount_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('per_student_amount_pkr')
                        ->label('Per Student Amount')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->per_student_amount_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('sms_usage_pkr')
                        ->label('SMS Usage')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->sms_usage_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('whatsapp_usage_pkr')
                        ->label('WhatsApp Usage')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->whatsapp_usage_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('ai_usage_pkr')
                        ->label('AI Usage')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->ai_usage_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('storage_overage_pkr')
                        ->label('Storage Overage')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->storage_overage_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('discount_pkr')
                        ->label('Discount')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->live(debounce: 500)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->discount_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::calculateTotal($get, $set)),

                    Components\TextInput::make('total_pkr')
                        ->label('Total')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->total_paisas / 100, 2, '.', ''));
                            }
                        }),
                ]),

            // ── Notes ───────────────────────────────────────────
            Section::make('Notes')
                ->schema([
                    Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Auto-calculate Total ────────────────────────────────────

    public static function calculateTotal(Get $get, Set $set): void
    {
        $fields = [
            'base_amount_pkr',
            'per_student_amount_pkr',
            'sms_usage_pkr',
            'whatsapp_usage_pkr',
            'ai_usage_pkr',
            'storage_overage_pkr',
        ];

        $total = 0;
        foreach ($fields as $field) {
            $total += (float) ($get($field) ?? 0);
        }

        $discount = (float) ($get('discount_pkr') ?? 0);
        $total = max(0, $total - $discount);

        $set('total_pkr', number_format($total, 2, '.', ''));
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tenant.school_name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->format('M Y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('active_student_count')
                    ->label('Students')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paisas')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => $state->color()),

                Tables\Columns\IconColumn::make('sent_via_whatsapp_at')
                    ->label('WA')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->getStateUsing(fn (Invoice $record): bool => $record->sent_via_whatsapp_at !== null)
                    ->tooltip(fn (Invoice $record): ?string => $record->sent_via_whatsapp_at
                        ? 'Sent: ' . $record->sent_via_whatsapp_at->format('M d, Y H:i')
                        : 'Not sent'),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant')
                    ->relationship('tenant', 'school_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(InvoiceStatus::class),

                Tables\Filters\Filter::make('period')
                    ->form([
                        Components\Select::make('month')
                            ->options(collect(range(1, 12))->mapWithKeys(fn (int $m) => [
                                $m => Carbon::create()->month($m)->format('F'),
                            ]))
                            ->placeholder('All months'),
                        Components\Select::make('year')
                            ->options(collect(range(now()->year - 1, now()->year + 1))->mapWithKeys(fn (int $y) => [
                                $y => (string) $y,
                            ]))
                            ->placeholder('All years'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['month'], fn ($q, $v) => $q->whereMonth('period_start', $v))
                            ->when($data['year'], fn ($q, $v) => $q->whereYear('period_start', $v));
                    }),
            ])
            ->headerActions([
                // ── Generate Invoice (Header Action) ────────────
                Actions\Action::make('generateInvoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form([
                        Components\Select::make('tenant_id')
                            ->label('School')
                            ->options(Tenant::pluck('school_name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (! $state) return;

                                $tenant = Tenant::with('plan')->find($state);
                                if (! $tenant) return;

                                $set('active_student_count', $tenant->active_student_count);

                                if ($tenant->plan) {
                                    $set('base_amount_pkr', number_format($tenant->plan->base_price_paisas / 100, 2, '.', ''));
                                    $perStudent = ($tenant->plan->per_student_price_paisas / 100) * $tenant->active_student_count;
                                    $set('per_student_amount_pkr', number_format($perStudent, 2, '.', ''));
                                }
                            }),

                        Components\Select::make('month')
                            ->options(collect(range(1, 12))->mapWithKeys(fn (int $m) => [
                                $m => Carbon::create()->month($m)->format('F'),
                            ]))
                            ->default(now()->month)
                            ->required(),

                        Components\Select::make('year')
                            ->options(collect(range(now()->year - 1, now()->year + 1))->mapWithKeys(fn (int $y) => [
                                $y => (string) $y,
                            ]))
                            ->default(now()->year)
                            ->required(),

                        Components\TextInput::make('active_student_count')
                            ->numeric()
                            ->default(0),

                        Components\TextInput::make('base_amount_pkr')
                            ->label('Base Amount (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0),

                        Components\TextInput::make('per_student_amount_pkr')
                            ->label('Per Student Amount (PKR)')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0),
                    ])
                    ->action(function (array $data) {
                        $tenant = Tenant::find($data['tenant_id']);
                        if (! $tenant) return;

                        $periodStart = Carbon::create($data['year'], $data['month'], 1);
                        $periodEnd = $periodStart->copy()->endOfMonth();

                        $slug = \Illuminate\Support\Str::slug($tenant->school_name);
                        $invoiceNumber = sprintf(
                            'KNX-%d-%02d-%s',
                            $data['year'],
                            $data['month'],
                            $slug,
                        );

                        $basePaisas = (int) round((float) ($data['base_amount_pkr'] ?? 0) * 100);
                        $perStudentPaisas = (int) round((float) ($data['per_student_amount_pkr'] ?? 0) * 100);
                        $totalPaisas = $basePaisas + $perStudentPaisas;

                        Invoice::create([
                            'tenant_id'                 => $tenant->id,
                            'invoice_number'            => $invoiceNumber,
                            'period_start'              => $periodStart,
                            'period_end'                => $periodEnd,
                            'active_student_count'      => (int) ($data['active_student_count'] ?? 0),
                            'base_amount_paisas'        => $basePaisas,
                            'per_student_amount_paisas' => $perStudentPaisas,
                            'sms_usage_paisas'          => 0,
                            'whatsapp_usage_paisas'     => 0,
                            'ai_usage_paisas'           => 0,
                            'storage_overage_paisas'    => 0,
                            'discount_paisas'           => 0,
                            'total_paisas'              => $totalPaisas,
                            'status'                    => InvoiceStatus::Draft,
                        ]);

                        Notification::make()
                            ->title('Invoice Generated')
                            ->body("Invoice {$invoiceNumber} created for {$tenant->school_name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),

                // ── Mark as Paid ────────────────────────────────
                Actions\Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->status !== InvoiceStatus::Paid)
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->update([
                            'status'  => InvoiceStatus::Paid,
                            'paid_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Invoice Marked as Paid')
                            ->success()
                            ->send();
                    }),

                // ── Send via WhatsApp (Placeholder) ─────────────
                Actions\Action::make('sendWhatsApp')
                    ->label('Send WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->sent_via_whatsapp_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice via WhatsApp')
                    ->modalDescription('This will log the WhatsApp send timestamp. Actual WhatsApp integration coming soon.')
                    ->action(function (Invoice $record) {
                        // Placeholder: log timestamp, actual WA integration later
                        $record->update([
                            'sent_via_whatsapp_at' => now(),
                        ]);

                        Notification::make()
                            ->title('WhatsApp Send Logged')
                            ->body('Timestamp recorded. Actual delivery integration coming soon.')
                            ->success()
                            ->send();
                    }),

                // ── Download PDF ────────────────────────────────
                Actions\Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Invoice $record) {
                        $record->load('tenant.plan');

                        $pdf = Pdf::loadView('invoices.pdf', [
                            'invoice' => $record,
                            'tenant'  => $record->tenant,
                            'plan'    => $record->tenant?->plan,
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "{$record->invoice_number}.pdf",
                        );
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    // ── Mutators (PKR → Paisas) ─────────────────────────────────

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::convertPkrToPaisas($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return static::convertPkrToPaisas($data);
    }

    protected static function convertPkrToPaisas(array $data): array
    {
        $fields = [
            'base_amount_pkr'        => 'base_amount_paisas',
            'per_student_amount_pkr' => 'per_student_amount_paisas',
            'sms_usage_pkr'          => 'sms_usage_paisas',
            'whatsapp_usage_pkr'     => 'whatsapp_usage_paisas',
            'ai_usage_pkr'           => 'ai_usage_paisas',
            'storage_overage_pkr'    => 'storage_overage_paisas',
            'discount_pkr'           => 'discount_paisas',
        ];

        $totalPaisas = 0;

        foreach ($fields as $pkrField => $paisaField) {
            $pkrValue = (float) ($data[$pkrField] ?? 0);
            $data[$paisaField] = (int) round($pkrValue * 100);
            unset($data[$pkrField]);

            if ($paisaField !== 'discount_paisas') {
                $totalPaisas += $data[$paisaField];
            }
        }

        // Subtract discount
        $totalPaisas = max(0, $totalPaisas - ($data['discount_paisas'] ?? 0));
        $data['total_paisas'] = $totalPaisas;

        // Remove the virtual total_pkr field
        unset($data['total_pkr']);

        return $data;
    }
}
