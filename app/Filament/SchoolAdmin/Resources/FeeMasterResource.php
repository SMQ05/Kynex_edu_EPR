<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\FeeMasterResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\FeeMaster;
use App\Models\Tenant\FeeType;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section as TenantSection;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeeMasterResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_fee_structures';

    protected static ?string $model = FeeMaster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Fee Structure';

    protected static ?string $modelLabel = 'Fee Structure Row';

    protected static ?string $pluralModelLabel = 'Fee Structure';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fee Structure Row')
                ->description('Defines the price for one fee type, in one class, for one academic year. The Generate Fees page reads from these rows.')
                ->columns(2)
                ->schema([
                    Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn ($set) => $set('section_id', null)),

                    Select::make('section_id')
                        ->label('Section override (optional)')
                        ->options(fn ($get) => $get('class_id')
                            ? TenantSection::where('class_id', $get('class_id'))->orderBy('name')->pluck('name', 'id')
                            : [])
                        ->searchable()
                        ->preload()
                        ->placeholder('Class default — applies to every section')
                        ->helperText('Leave blank to set the class-wide default. Pick a section here only when that section pays a different amount than the class default.')
                        ->nullable()
                        ->reactive(),

                    Select::make('fee_type_id')
                        ->label('Fee Type')
                        ->options(fn () => FeeType::with('feeGroup')->orderBy('name')->get()
                            ->mapWithKeys(fn ($t) => [
                                $t->id => $t->name . ' (' . ($t->feeGroup?->name ?? 'Ungrouped') . ($t->is_recurring ? ' · Monthly' : ' · One-time') . ')',
                            ])
                            ->toArray())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->unique(
                            table: 'fee_masters',
                            column: 'fee_type_id',
                            ignoreRecord: true,
                            modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                $rule = $rule
                                    ->where('class_id', $get('class_id'))
                                    ->where('academic_year_id', $get('academic_year_id'));

                                if ($get('section_id')) {
                                    return $rule->where('section_id', $get('section_id'));
                                }

                                return $rule->whereNull('section_id');
                            },
                        )
                        ->validationMessages([
                            'unique' => 'A fee structure row already exists for this class, fee type, academic year and section scope.',
                        ]),

                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn () => AcademicYear::query()->where('is_current', true)->value('id'))
                        ->reactive(),

                    TextInput::make('amount_pkr')
                        ->label('Amount (PKR)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required()
                        ->prefix('PKR')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (TextInput $component, $state, ?FeeMaster $record) {
                            if ($record) {
                                $component->state(number_format($record->amount_paisas / 100, 2, '.', ''));
                            }
                        }),

                    TextInput::make('due_day')
                        ->label('Due day of month')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(28)
                        ->default(10)
                        ->required()
                        ->helperText('e.g. 10 = bills due by the 10th of each month'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive rows are skipped by Generate Fees.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(FeeMaster::query()->with(['schoolClass', 'section', 'feeType.feeGroup', 'academicYear']))
            ->columns([
                TextColumn::make('schoolClass.name')->label('Class')->sortable()->searchable(),
                TextColumn::make('section.name')
                    ->label('Section')
                    ->placeholder('— class default —')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('feeType.name')->label('Fee Type')->sortable()->searchable(),
                TextColumn::make('feeType.feeGroup.name')->label('Group')->sortable()->toggleable(),
                IconColumn::make('feeType.is_recurring')->label('Monthly')->boolean(),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state) => 'PKR ' . number_format(($state ?? 0) / 100, 2))
                    ->sortable(),
                TextColumn::make('due_day')->label('Due day')->sortable(),
                TextColumn::make('academicYear.name')->label('Year')->sortable()->toggleable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                SelectFilter::make('class_id')->label('Class')
                    ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('academic_year_id')->label('Academic Year')
                    ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')),
                TernaryFilter::make('section_id')
                    ->label('Row scope')
                    ->placeholder('All rows')
                    ->trueLabel('Section overrides only')
                    ->falseLabel('Class defaults only')
                    ->queries(
                        true:  fn ($q) => $q->whereNotNull('section_id'),
                        false: fn ($q) => $q->whereNull('section_id'),
                        blank: fn ($q) => $q,
                    ),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->defaultSort('class_id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeeMasters::route('/'),
            'create' => Pages\CreateFeeMaster::route('/create'),
            'edit'   => Pages\EditFeeMaster::route('/{record}/edit'),
        ];
    }
}
