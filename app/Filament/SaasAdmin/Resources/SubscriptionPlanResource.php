<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Filament\SaasAdmin\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $modelLabel = 'Plan';

    protected static ?string $pluralModelLabel = 'Plans';

    protected static string | \UnitEnum | null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Plan Details ────────────────────────────────────
            Section::make('Plan Details')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $set('slug', Str::slug($state ?? ''));
                        }),

                    Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Auto-generated from name'),

                    Components\Select::make('billing_type')
                        ->options([
                            'per_student' => 'Per Student',
                            'per_school'  => 'Per School (Flat)',
                            'hybrid'      => 'Hybrid (Base + Per Student)',
                        ])
                        ->required()
                        ,

                    Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Components\Toggle::make('is_featured')
                        ->label('Featured')
                        ->helperText('Show on pricing page'),

                    Components\Toggle::make('is_custom')
                        ->label('Custom Plan')
                        ->helperText('Hidden from public pricing'),

                    Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Components\TextInput::make('trial_days')
                        ->numeric()
                        ->default(14)
                        ->minValue(0)
                        ->suffix('days'),
                ]),

            // ── Pricing (PKR) ───────────────────────────────────
            Section::make('Pricing (PKR)')
                ->description('Enter amounts in PKR. Stored internally as paisas.')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('base_price_pkr')
                        ->label('Base Price (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->base_price_paisas / 100, 2, '.', ''));
                            }
                        }),

                    Components\TextInput::make('per_student_price_pkr')
                        ->label('Per Student Price (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->per_student_price_paisas / 100, 2, '.', ''));
                            }
                        }),

                    Components\TextInput::make('setup_fee_pkr')
                        ->label('Setup Fee (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->setup_fee_paisas / 100, 2, '.', ''));
                            }
                        }),
                ]),

            // ── Limits ──────────────────────────────────────────
            Section::make('Limits')
                ->columns(4)
                ->schema([
                    Components\TextInput::make('max_students')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('0 = Unlimited'),

                    Components\TextInput::make('max_staff')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('0 = Unlimited'),

                    Components\TextInput::make('max_campuses')
                        ->numeric()
                        ->default(1)
                        ->minValue(1),

                    Components\TextInput::make('storage_limit_gb')
                        ->label('Storage (GB)')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->suffix('GB'),
                ]),

            // ── Enabled Modules ─────────────────────────────────
            Section::make('Enabled Modules')
                ->description('Select which modules are included in this plan')
                ->schema([
                    Components\CheckboxList::make('enabled_modules')
                        ->label('')
                        ->columns(3)
                        ->options([
                            // Core
                            'student_management'    => '👥 Student Management',
                            'staff_management'      => '👨‍🏫 Staff Management',
                            'campus_management'     => '🏫 Campus Management',
                            'class_management'      => '📚 Class & Section Management',
                            'attendance'            => '✅ Attendance',
                            'timetable'             => '📅 Timetable',

                            // Communication
                            'whatsapp_notifications' => '💬 WhatsApp Notifications',
                            'sms_notifications'      => '📱 SMS Notifications',
                            'email_notifications'    => '📧 Email Notifications',
                            'parent_portal'          => '👨‍👩‍👧 Parent Portal',

                            // Academic
                            'exam_management'       => '📝 Exam Management',
                            'report_cards'          => '📊 Report Cards',
                            'homework'              => '📖 Homework',
                            'online_classes'        => '🎥 Online Classes (Basic)',
                            'online_classes_pro'    => '🎬 Online Classes Pro (Live, Recordings, Whiteboard)',
                            'library'               => '📕 Library Management',

                            // Operations
                            'transport'             => '🚌 Transport Management',
                            'hostel'                => '🏠 Hostel Management',
                            'inventory'             => '📦 Inventory',
                            'visitor_management'    => '🚪 Visitor Management',

                            // Financial
                            'fee_management'        => '💰 Fee Management',
                            'payroll'               => '💵 Payroll (HR)',
                            'expense_tracking'      => '📈 Expense Tracking',
                            'budgeting'             => '📊 Budgeting & Financial Planning',

                            // Advanced
                            'ai_assistant'          => '🤖 AI Assistant (OpenRouter)',
                            'advanced_analytics'    => '📊 Advanced Analytics',
                            'custom_reports'        => '📋 Custom Reports',
                            'api_access'            => '🔌 API Access',
                        ])
                        ->descriptions([
                            'student_management'     => 'Core',
                            'staff_management'       => 'Core',
                            'campus_management'      => 'Core',
                            'class_management'       => 'Core',
                            'attendance'             => 'Core',
                            'timetable'              => 'Core',
                            'whatsapp_notifications' => 'Communication — Evolution API / Meta / Twilio',
                            'sms_notifications'      => 'Communication — Android SMS Gateway / Twilio / Telcos',
                            'email_notifications'    => 'Communication',
                            'parent_portal'          => 'Communication',
                            'exam_management'        => 'Academic',
                            'report_cards'           => 'Academic',
                            'homework'               => 'Academic',
                            'online_classes'         => 'Academic — Schedule & join video classes',
                            'online_classes_pro'     => 'Academic — Live streaming, recordings, interactive whiteboard, breakout rooms',
                            'library'                => 'Academic',
                            'transport'              => 'Operations',
                            'hostel'                 => 'Operations',
                            'inventory'              => 'Operations',
                            'visitor_management'     => 'Operations',
                            'fee_management'         => 'Financial',
                            'payroll'                => 'Financial / HR — Staff salary, deductions, payslips',
                            'expense_tracking'       => 'Financial — Track & categorize school expenses',
                            'budgeting'              => 'Financial — Annual budgets, department allocations, variance reports',
                            'ai_assistant'           => 'Advanced — Requires OpenRouter API key in Settings',
                            'advanced_analytics'     => 'Advanced',
                            'custom_reports'         => 'Advanced',
                            'api_access'             => 'Advanced',
                        ]),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('billing_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'per_student' => 'info',
                        'per_school'  => 'success',
                        'hybrid'      => 'purple',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'per_student' => 'Per Student',
                        'per_school'  => 'Per School',
                        'hybrid'      => 'Hybrid',
                        default       => $state,
                    }),

                Tables\Columns\TextColumn::make('base_price_paisas')
                    ->label('Base Price')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('per_student_price_paisas')
                    ->label('Per Student')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_students')
                    ->label('Max Students')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Unlimited' : number_format($state))
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Actions\EditAction::make(),

                Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (SubscriptionPlan $record) {
                        $clone = $record->replicate();
                        $clone->name = $record->name . ' (Copy)';
                        $clone->slug = Str::slug($clone->name);
                        $clone->is_active = false;
                        $clone->save();
                    }),

                Actions\Action::make('toggleActive')
                    ->label(fn (SubscriptionPlan $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (SubscriptionPlan $record): string => $record->is_active
                        ? 'heroicon-o-x-circle'
                        : 'heroicon-o-check-circle')
                    ->color(fn (SubscriptionPlan $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (SubscriptionPlan $record) {
                        $record->update(['is_active' => ! $record->is_active]);
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
            'index'  => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit'   => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }

    // ── Mutators (PKR → Paisas) ─────────────────────────────────

    /**
     * Convert PKR form inputs to paisas before saving.
     */
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
            'base_price_pkr'        => 'base_price_paisas',
            'per_student_price_pkr' => 'per_student_price_paisas',
            'setup_fee_pkr'         => 'setup_fee_paisas',
        ];

        foreach ($fields as $pkrField => $paisaField) {
            if (isset($data[$pkrField])) {
                $data[$paisaField] = (int) round((float) $data[$pkrField] * 100);
                unset($data[$pkrField]);
            }
        }

        return $data;
    }
}
