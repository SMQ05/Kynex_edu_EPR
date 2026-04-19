<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\GradeRule;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class GradeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_grading_scales';

    protected static ?string $model = GradeRule::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Grade Rules';

    protected static ?string $modelLabel = 'Grade Rule';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Grade Rule')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Standard Grading'),

                TextInput::make('grade')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('e.g. A+, A, B+'),

                TextInput::make('min_percentage')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%'),

                TextInput::make('max_percentage')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->gte('min_percentage'),

                TextInput::make('grade_point')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->step(0.1)
                    ->placeholder('e.g. 4.0'),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_percentage')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_percentage')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade_point')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->defaultSort('min_percentage');
    }

    public static function getPages(): array
    {
        return [
            'index'  => GradeResource\Pages\ListGrades::route('/'),
            'create' => GradeResource\Pages\CreateGrade::route('/create'),
            'edit'   => GradeResource\Pages\EditGrade::route('/{record}/edit'),
        ];
    }
}
