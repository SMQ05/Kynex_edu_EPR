<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\ClassResource\Pages;
use App\Models\Tenant\SchoolClass;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Unique;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class ClassResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_classes';

    protected static ?string $model = SchoolClass::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Classes';

    protected static ?string $modelLabel = 'Class';

    protected static ?string $pluralModelLabel = 'Classes';

    protected static ?string $slug = 'classes';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 3;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Class Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Grade 1, Class X')
                        ->live(onBlur: true) // re-validate on blur, not only on submit
                        // Class name must be unique within the selected campus.
                        // The same name is allowed when the campus differs.
                        // Tenancy makes the tenant connection the default, so the
                        // 'classes' table resolves correctly. Soft-deleted rows are
                        // excluded so a name can be reused after deletion.
                        ->unique(
                            table: 'classes',
                            column: 'name',
                            ignoreRecord: true,
                            modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                                $campusId = $get('campus_id');

                                // Scope to the selected campus, or explicitly to the
                                // no-campus group when none is chosen.
                                $rule = $campusId
                                    ? $rule->where('campus_id', $campusId)
                                    : $rule->whereNull('campus_id');

                                // Exclude soft-deleted rows so a name frees up after deletion.
                                return $rule->whereNull('deleted_at');
                            },
                        )
                        ->validationMessages([
                            'unique' => 'The class name already exists for this campus context.',
                        ]),

                    Components\Select::make('campus_id')
                        ->label('Campus')
                        ->relationship('campus', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live(), // changing campus re-checks the class-name uniqueness

                    Components\TextInput::make('numeric_level')
                        ->label('Numeric Level')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->helperText('Numeric representation for sorting (e.g. 1, 2, 3…)'),

                    Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),

                    Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('campus.name')
                    ->label('Campus')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numeric_level')
                    ->label('Level')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClasses::route('/'),
            'create' => Pages\CreateClass::route('/create'),
            'edit' => Pages\EditClass::route('/{record}/edit'),
        ];
    }
}
