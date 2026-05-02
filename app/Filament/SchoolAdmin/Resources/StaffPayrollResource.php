<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\PayrollStatus;
use App\Filament\SchoolAdmin\Resources\StaffPayrollResource\Pages;
use App\Models\Tenant\SalaryComponent;
use App\Models\Tenant\StaffPayroll;
use App\Models\Tenant\StaffProfile;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffPayrollResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_payroll';

    protected static ?string $model = StaffPayroll::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Payroll';

    protected static ?int $navigationSort = 5;

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Payroll Details')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Select::make('staff_profile_id')
                        ->label('Staff Member')
                        ->relationship('staffProfile', 'employee_id')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => ($record->schoolUser?->name ?? '—') . ' (' . $record->employee_id . ')'
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) {
                                return;
                            }
                            $profile = StaffProfile::find($state);
                            if ($profile) {
                                $basic = $profile->basic_salary_paisas / 100;
                                $set('basic_salary', $basic);
                                // Recalculate net
                                $set('net_salary', $basic);
                            }
                        }),

                    Select::make('month')
                        ->options(
                            collect(range(1, 12))
                                ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->format('F')])
                        )
                        ->required()
                        ->default(now()->month)
                        ,

                    TextInput::make('year')
                        ->numeric()
                        ->default(now()->year)
                        ->required()
                        ->minValue(2020)
                        ->maxValue(2050),

                    TextInput::make('working_days')
                        ->numeric()
                        ->required()
                        ->default(fn () => self::calcWorkingDays(now()->month, now()->year))
                        ->suffix('days'),

                    TextInput::make('present_days')
                        ->numeric()
                        ->required()
                        ->suffix('days'),
                ])->columns(3),

            Section::make('Salary Breakdown')
                ->icon('heroicon-o-calculator')
                ->schema([
                    TextInput::make('basic_salary')
                        ->label('Basic Salary')
                        ->numeric()
                        ->prefix('PKR')
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcNet($get, $set))
                        ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                        ->formatStateUsing(fn ($state, $record) => $record ? $record->basic_salary_paisas / 100 : 0),

                    TextInput::make('allowances')
                        ->label('Total Allowances')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcNet($get, $set))
                        ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                        ->formatStateUsing(fn ($state, $record) => $record ? $record->allowances_paisas / 100 : 0),

                    TextInput::make('deductions')
                        ->label('Total Deductions')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->live(debounce: 500)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcNet($get, $set))
                        ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                        ->formatStateUsing(fn ($state, $record) => $record ? $record->deductions_paisas / 100 : 0),

                    TextInput::make('net_salary')
                        ->label('Net Salary')
                        ->numeric()
                        ->prefix('PKR')
                        ->readOnly()
                        ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                        ->formatStateUsing(fn ($state, $record) => $record ? $record->net_salary_paisas / 100 : 0)
                        ->helperText('Auto-calculated: Basic + Allowances − Deductions'),

                    Select::make('status')
                        ->options(PayrollStatus::class)
                        ->default(PayrollStatus::Draft->value)
                        ->required()
                        ,
                ])->columns(3),
        ]);
    }

    private static function recalcNet(Get $get, Set $set): void
    {
        $basic      = (float) ($get('basic_salary') ?? 0);
        $allowances = (float) ($get('allowances') ?? 0);
        $deductions = (float) ($get('deductions') ?? 0);
        $set('net_salary', max(0, $basic + $allowances - $deductions));
    }

    private static function calcWorkingDays(int $month, int $year): int
    {
        $start   = Carbon::create($year, $month, 1);
        $end     = $start->copy()->endOfMonth();
        $current = $start->copy();
        $days    = 0;
        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staffProfile.schoolUser.name')
                    ->label('Staff Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('staffProfile.employee_id')
                    ->label('Emp ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('month')
                    ->formatStateUsing(fn ($state) => Carbon::create()->month($state)->format('M'))
                    ->sortable(),

                TextColumn::make('year')
                    ->sortable(),

                TextColumn::make('present_days')
                    ->label('Days')
                    ->formatStateUsing(fn ($state, $record) => $state . '/' . ($record->working_days ?? '—'))
                    ->tooltip('Present / Working Days'),

                TextColumn::make('basic_salary_paisas')
                    ->label('Basic')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 0))
                    ->sortable(),

                TextColumn::make('allowances_paisas')
                    ->label('Allowances')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+PKR ' . number_format($state / 100, 0) : '—')
                    ->color('success'),

                TextColumn::make('deductions_paisas')
                    ->label('Deductions')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '-PKR ' . number_format($state / 100, 0) : '—')
                    ->color('danger'),

                TextColumn::make('net_salary_paisas')
                    ->label('Net Salary')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 0))
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->label('Paid On')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->options(
                        collect(range(1, 12))
                            ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->format('F')])
                    ),

                SelectFilter::make('year')
                    ->options(
                        collect(range(now()->year - 2, now()->year + 1))
                            ->mapWithKeys(fn ($y) => [$y => $y])
                    ),

                SelectFilter::make('status')
                    ->options(PayrollStatus::class),
            ])
            ->headerActions([
                Action::make('generateMonthlyPayroll')
                    ->label('Generate Payroll')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->form([
                        Select::make('month')
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->format('F')])
                            )
                            ->default(now()->month)
                            ->required()
                            ,

                        TextInput::make('year')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $month       = (int) $data['month'];
                        $year        = (int) $data['year'];
                        $workingDays = self::calcWorkingDays($month, $year);

                        $allowances = SalaryComponent::active()->allowances()->get();
                        $deductions = SalaryComponent::active()->deductions()->get();

                        $staffProfiles = StaffProfile::whereNotNull('basic_salary_paisas')
                            ->where('basic_salary_paisas', '>', 0)
                            ->get();

                        $created  = 0;
                        $skipped  = 0;
                        foreach ($staffProfiles as $staff) {
                            if (StaffPayroll::where('staff_profile_id', $staff->id)
                                ->where('month', $month)
                                ->where('year', $year)
                                ->exists()) {
                                $skipped++;
                                continue;
                            }

                            $basicSalary     = $staff->basic_salary_paisas;
                            $totalAllowances = 0;
                            foreach ($allowances as $component) {
                                $totalAllowances += $component->calculation_type->value === 'percentage'
                                    ? (int) ($basicSalary * $component->default_value_paisas / 10000)
                                    : $component->default_value_paisas;
                            }

                            $totalDeductions = 0;
                            foreach ($deductions as $component) {
                                $totalDeductions += $component->calculation_type->value === 'percentage'
                                    ? (int) ($basicSalary * $component->default_value_paisas / 10000)
                                    : $component->default_value_paisas;
                            }

                            $netSalary = max(0, $basicSalary + $totalAllowances - $totalDeductions);

                            StaffPayroll::create([
                                'staff_profile_id'    => $staff->id,
                                'school_user_id'      => $staff->school_user_id,
                                'month'               => $month,
                                'year'                => $year,
                                'working_days'        => $workingDays,
                                'present_days'        => $workingDays,
                                'basic_salary_paisas' => $basicSalary,
                                'allowances_paisas'   => $totalAllowances,
                                'deductions_paisas'   => $totalDeductions,
                                'net_salary_paisas'   => $netSalary,
                                'status'              => PayrollStatus::Draft,
                            ]);

                            $created++;
                        }

                        $monthName = Carbon::create()->month($month)->format('F');
                        $msg       = "{$created} payroll records created for {$monthName} {$year}.";
                        if ($skipped) {
                            $msg .= " {$skipped} already existed and were skipped.";
                        }

                        Notification::make()->title($msg)->success()->send();
                    }),

                Action::make('processAll')
                    ->label('Process All Drafts')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->form([
                        Select::make('month')
                            ->options(
                                collect(range(1, 12))
                                    ->mapWithKeys(fn ($m) => [$m => Carbon::create()->month($m)->format('F')])
                            )
                            ->default(now()->month)
                            ->required()
                            ,

                        TextInput::make('year')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data) {
                        $count = StaffPayroll::where('month', $data['month'])
                            ->where('year', $data['year'])
                            ->where('status', PayrollStatus::Draft)
                            ->update([
                                'status'       => PayrollStatus::Processed,
                                'processed_by' => auth()->guard('school_users')->id(),
                            ]);

                        $monthName = Carbon::create()->month((int) $data['month'])->format('F');
                        Notification::make()
                            ->title("{$count} payroll records processed for {$monthName} {$data['year']}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),

                Action::make('markPaid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (StaffPayroll $record) => $record->status === PayrollStatus::Processed)
                    ->requiresConfirmation()
                    ->action(function (StaffPayroll $record) {
                        $record->update([
                            'status'  => PayrollStatus::Paid,
                            'paid_at' => now(),
                        ]);
                        Notification::make()->title('Payroll marked as paid.')->success()->send();
                    }),

                Action::make('downloadPayslip')
                    ->label('Payslip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (StaffPayroll $record) => route('payslip.download', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('bulkMarkPaid')
                        ->label('Mark Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === PayrollStatus::Processed) {
                                    $record->update([
                                        'status'  => PayrollStatus::Paid,
                                        'paid_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} payrolls marked as paid.")->success()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaffPayrolls::route('/'),
            'create' => Pages\CreateStaffPayroll::route('/create'),
            'edit'   => Pages\EditStaffPayroll::route('/{record}/edit'),
        ];
    }
}
