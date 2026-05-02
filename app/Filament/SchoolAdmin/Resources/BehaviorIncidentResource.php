<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\BehaviorIncidentStatus;
use App\Enums\BehaviorIncidentType;
use App\Models\Tenant\BehaviorIncident;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;

class BehaviorIncidentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_behavior_records';

    protected static ?string $model = BehaviorIncident::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string | \UnitEnum | null $navigationGroup = 'Health & Wellbeing';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Behavior Incidents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incident Details')->schema([
                Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('incident_type')
                    ->options(collect(BehaviorIncidentType::cases())->mapWithKeys(
                        fn (BehaviorIncidentType $t) => [$t->value => $t->label()]
                    ))
                    ->required()
                    ->reactive(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Select::make('category')
                    ->options([
                        'bullying' => 'Bullying',
                        'tardiness' => 'Tardiness',
                        'fighting' => 'Fighting',
                        'academic_honesty' => 'Academic Honesty',
                        'dress_code' => 'Dress Code Violation',
                        'disruptive' => 'Disruptive Behavior',
                        'vandalism' => 'Vandalism',
                        'leadership' => 'Leadership',
                        'helpfulness' => 'Helpfulness',
                        'academic_excellence' => 'Academic Excellence',
                        'sportsmanship' => 'Sportsmanship',
                        'community_service' => 'Community Service',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->searchable(),

                DatePicker::make('incident_date')
                    ->required()
                    ->default(now()),

                TimePicker::make('incident_time'),

                TextInput::make('location')
                    ->maxLength(255),

                Select::make('severity')
                    ->options([
                        'minor' => 'Minor',
                        'moderate' => 'Moderate',
                        'major' => 'Major',
                        'critical' => 'Critical',
                    ])
                    ->default('minor')
                    ->required(),

                TextInput::make('points')
                    ->numeric()
                    ->default(0)
                    ->helperText('Positive for rewards, negative for demerits'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Context')->schema([
                Select::make('class_id')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('section_id')
                    ->relationship('section', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('campus_id')
                    ->relationship('campus', 'name')
                    ->searchable()
                    ->preload(),
            ])->columns(2),

            Section::make('Action & Resolution')->schema([
                Select::make('action_taken')
                    ->options([
                        'verbal_warning' => 'Verbal Warning',
                        'written_warning' => 'Written Warning',
                        'detention' => 'Detention',
                        'suspension' => 'Suspension',
                        'expulsion' => 'Expulsion',
                        'parent_meeting' => 'Parent Meeting',
                        'counseling' => 'Counseling Referral',
                        'reward' => 'Reward',
                        'certificate' => 'Certificate',
                        'merit_points' => 'Merit Points',
                    ]),

                Textarea::make('action_details')
                    ->rows(2),

                DatePicker::make('action_date'),

                DatePicker::make('follow_up_date'),

                Select::make('status')
                    ->options(collect(BehaviorIncidentStatus::cases())->mapWithKeys(
                        fn (BehaviorIncidentStatus $s) => [$s->value => $s->label()]
                    ))
                    ->default('reported')
                    ->required(),

                Textarea::make('resolution_notes')
                    ->rows(2),
            ])->columns(2),

            Section::make('Parent Communication')->schema([
                Toggle::make('parent_notified'),
                DatePicker::make('parent_notified_date'),
                Textarea::make('parent_response')
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
                Tables\Columns\TextColumn::make('incident_type')
                    ->badge()
                    ->formatStateUsing(fn (BehaviorIncidentType $state) => $state->label())
                    ->color(fn (BehaviorIncidentType $state) => $state->color()),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(35),
                Tables\Columns\TextColumn::make('incident_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'minor' => 'gray',
                        'moderate' => 'warning',
                        'major' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->color(fn (int $state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BehaviorIncidentStatus $state) => $state->label())
                    ->color(fn (BehaviorIncidentStatus $state) => $state->color()),
                Tables\Columns\IconColumn::make('parent_notified')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('incident_type')
                    ->options(collect(BehaviorIncidentType::cases())->mapWithKeys(
                        fn (BehaviorIncidentType $t) => [$t->value => $t->label()]
                    )),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(BehaviorIncidentStatus::cases())->mapWithKeys(
                        fn (BehaviorIncidentStatus $s) => [$s->value => $s->label()]
                    )),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'minor' => 'Minor',
                        'moderate' => 'Moderate',
                        'major' => 'Major',
                        'critical' => 'Critical',
                    ]),
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
            ->defaultSort('incident_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => BehaviorIncidentResource\Pages\ListBehaviorIncidents::route('/'),
            'create' => BehaviorIncidentResource\Pages\CreateBehaviorIncident::route('/create'),
            'edit'   => BehaviorIncidentResource\Pages\EditBehaviorIncident::route('/{record}/edit'),
        ];
    }
}
