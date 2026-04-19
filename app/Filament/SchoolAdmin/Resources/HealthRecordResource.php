<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\HealthRecordType;
use App\Models\Tenant\HealthRecord;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;

class HealthRecordResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_health_records';
    protected static string $rbacWritePermission = 'create_health_records';

    protected static ?string $model = HealthRecord::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static string | \UnitEnum | null $navigationGroup = 'Health & Wellbeing';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Health Records';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Student & Record Info')->schema([
                Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('record_type')
                    ->options(collect(HealthRecordType::cases())->mapWithKeys(
                        fn (HealthRecordType $t) => [$t->value => $t->label()]
                    ))
                    ->required()
                    ->reactive(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('record_date')
                    ->required()
                    ->default(now()),

                Select::make('campus_id')
                    ->relationship('campus', 'name')
                    ->searchable()
                    ->preload(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Clinic Visit Details')
                ->schema([
                    TextInput::make('symptoms')->maxLength(255),
                    TextInput::make('diagnosis')->maxLength(255),
                    TextInput::make('treatment')->maxLength(255),
                    TextInput::make('medication_given')->maxLength(255),
                    Select::make('action_taken')
                        ->options([
                            'sent_home' => 'Sent Home',
                            'returned_to_class' => 'Returned to Class',
                            'referred_hospital' => 'Referred to Hospital',
                            'first_aid' => 'First Aid Only',
                        ]),
                ])
                ->columns(2)
                ->visible(fn ($get) => $get('record_type') === 'clinic_visit'),

            Section::make('Vaccination Details')
                ->schema([
                    TextInput::make('vaccine_name')->maxLength(255),
                    DatePicker::make('next_dose_date'),
                ])
                ->columns(2)
                ->visible(fn ($get) => $get('record_type') === 'vaccination'),

            Section::make('Allergy / Condition Details')
                ->schema([
                    Select::make('severity')
                        ->options([
                            'mild' => 'Mild',
                            'moderate' => 'Moderate',
                            'severe' => 'Severe',
                            'life_threatening' => 'Life-Threatening',
                        ]),
                    Toggle::make('is_chronic')
                        ->label('Chronic Condition'),
                ])
                ->columns(2)
                ->visible(fn ($get) => in_array($get('record_type'), ['allergy', 'medical_condition'])),

            Section::make('Vitals (Optional)')->schema([
                TextInput::make('temperature')
                    ->numeric()
                    ->suffix('°F')
                    ->step(0.1),
                TextInput::make('blood_pressure')
                    ->placeholder('120/80'),
                TextInput::make('pulse_rate')
                    ->numeric()
                    ->suffix('bpm'),
                TextInput::make('weight_kg')
                    ->numeric()
                    ->suffix('kg')
                    ->step(0.01),
                TextInput::make('height_cm')
                    ->numeric()
                    ->suffix('cm')
                    ->step(0.1),
            ])->columns(3),

            Section::make('Additional Info')->schema([
                Toggle::make('parent_notified')
                    ->label('Parent Notified'),
                Toggle::make('is_active')
                    ->label('Active Record')
                    ->default(true),
                Toggle::make('is_confidential')
                    ->label('Mark as Confidential')
                    ->hint('Only Nurse, Counselor, and Admin can see confidential records')
                    ->visible(fn () => auth()->user()?->hasAnyRole([
                        'SCHOOL_ADMIN', 'NURSE', 'COUNSELOR',
                    ])),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('record_type')
                    ->badge()
                    ->formatStateUsing(fn (HealthRecordType $state) => $state->label())
                    ->color(fn (HealthRecordType $state) => $state->color()),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('record_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'mild' => 'gray',
                        'moderate' => 'warning',
                        'severe' => 'danger',
                        'life_threatening' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('parent_notified')
                    ->boolean(),
                IconColumn::make('is_confidential')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->label('')
                    ->visible(fn () => auth()->user()?->hasAnyRole([
                        'SCHOOL_ADMIN', 'NURSE', 'COUNSELOR',
                    ])),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('record_type')
                    ->options(collect(HealthRecordType::cases())->mapWithKeys(
                        fn (HealthRecordType $t) => [$t->value => $t->label()]
                    )),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('parent_notified'),
            ])
            ->actions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('record_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => HealthRecordResource\Pages\ListHealthRecords::route('/'),
            'create' => HealthRecordResource\Pages\CreateHealthRecord::route('/create'),
            'edit'   => HealthRecordResource\Pages\EditHealthRecord::route('/{record}/edit'),
        ];
    }
}
