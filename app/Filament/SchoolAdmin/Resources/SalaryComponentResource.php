<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentType;
use App\Filament\SchoolAdmin\Resources\SalaryComponentResource\Pages;
use App\Models\Tenant\SalaryComponent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalaryComponentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_payroll';

    protected static ?string $model = SalaryComponent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Salary Components';

    protected static ?int $navigationSort = 4;

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Component Details')
                ->icon('heroicon-o-calculator')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. House Rent Allowance'),

                    Select::make('component_type')
                        ->options(SalaryComponentType::class)
                        ->required()
                        ->native(false)
                        ->helperText('Allowances add to salary; Deductions subtract'),

                    Select::make('calculation_type')
                        ->options(SalaryCalculationType::class)
                        ->required()
                        ->native(false)
                        ->live()
                        ->helperText('Fixed = flat amount; Percentage = % of basic salary'),

                    TextInput::make('default_value')
                        ->label(fn (Get $get) => $get('calculation_type') === 'percentage'
                            ? 'Value (%)'
                            : 'Value (PKR)')
                        ->numeric()
                        ->required()
                        ->prefix(fn (Get $get) => $get('calculation_type') === 'percentage' ? null : 'PKR')
                        ->suffix(fn (Get $get) => $get('calculation_type') === 'percentage' ? '%' : null)
                        ->minValue(0)
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            // For percentage, store as integer % * 100 (e.g. 10% → 1000 paisas used as basis points)
                            // For fixed, store as paisas (amount * 100)
                            if ($get('calculation_type') === 'percentage') {
                                return (int) (($state ?? 0) * 100);
                            }

                            return (int) (($state ?? 0) * 100);
                        })
                        ->formatStateUsing(function ($state, $record) {
                            if (! $record) {
                                return 0;
                            }
                            // For percentage: stored as basis points (e.g. 10% = 1000), display as 10
                            // For fixed: stored as paisas, display as PKR
                            return $record->default_value_paisas / 100;
                        }),

                    Toggle::make('is_taxable')
                        ->label('Taxable')
                        ->default(false),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive components are excluded from payroll generation'),
                ]),
        ]);
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('component_type')
                    ->badge()
                    ->color(fn (SalaryComponentType $state) => match ($state) {
                        SalaryComponentType::Allowance => 'success',
                        SalaryComponentType::Deduction => 'danger',
                    }),

                TextColumn::make('calculation_type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('default_value_paisas')
                    ->label('Default Value')
                    ->formatStateUsing(function ($state, SalaryComponent $record) {
                        if ($record->calculation_type === SalaryCalculationType::Percentage) {
                            return number_format($state / 100, 1) . '%';
                        }

                        return 'PKR ' . number_format($state / 100, 0);
                    })
                    ->sortable(),

                IconColumn::make('is_taxable')
                    ->label('Taxable')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('component_type')
                    ->options(SalaryComponentType::class),

                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Action::make('toggleActive')
                    ->label(fn (SalaryComponent $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (SalaryComponent $record) => $record->is_active
                        ? 'heroicon-o-pause-circle'
                        : 'heroicon-o-play-circle')
                    ->color(fn (SalaryComponent $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (SalaryComponent $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        $label = $record->is_active ? 'activated' : 'deactivated';
                        Notification::make()
                            ->title("Component {$label}.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalaryComponents::route('/'),
            'create' => Pages\CreateSalaryComponent::route('/create'),
            'edit'   => Pages\EditSalaryComponent::route('/{record}/edit'),
        ];
    }
}
