<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\BehaviorIncidentTypeResource\Pages;
use App\Models\Tenant\BehaviorIncidentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Behaviour incident CATALOG (Infix "Behaviour → Settings"). New resource —
 * does NOT touch the existing BehaviorIncidentResource. A future enhancement
 * could add an optional `incident_type_id` FK on `behavior_incidents` so a
 * logged incident inherits this catalog's default points/severity (reported,
 * not built — would require editing the shared BehaviorIncident model/resource).
 */
class BehaviorIncidentTypeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_behavior_records';

    protected static ?string $model = BehaviorIncidentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Health & Wellbeing';

    protected static ?string $navigationLabel = 'Incident Catalog';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Incident Type')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('nature')
                        ->options(BehaviorIncidentType::NATURES)
                        ->default('negative')->required()->native(false),
                    Select::make('severity')
                        ->options(BehaviorIncidentType::SEVERITIES)
                        ->default('minor')->required()->native(false),
                    TextInput::make('default_points')
                        ->label('Default points')
                        ->numeric()->default(0)
                        ->helperText('Positive for merits, negative for demerits.'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    TextInput::make('default_action')->label('Default action')->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->rows(2)->columnSpanFull(),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('nature')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BehaviorIncidentType::NATURES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('severity')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => BehaviorIncidentType::SEVERITIES[$state] ?? $state),
                TextColumn::make('default_points')->label('Points')->sortable()->badge(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                SelectFilter::make('nature')->options(BehaviorIncidentType::NATURES),
                SelectFilter::make('severity')->options(BehaviorIncidentType::SEVERITIES),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBehaviorIncidentTypes::route('/'),
            'create' => Pages\CreateBehaviorIncidentType::route('/create'),
            'edit'   => Pages\EditBehaviorIncidentType::route('/{record}/edit'),
        ];
    }
}
