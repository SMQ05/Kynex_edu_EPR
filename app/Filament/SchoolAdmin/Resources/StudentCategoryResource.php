<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\StudentCategoryResource\Pages;
use App\Models\Tenant\StudentCategory;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Illuminate\Validation\Rules\Unique;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class StudentCategoryResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';

    protected static ?string $model = StudentCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Student Categories';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 1;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Category Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true) // re-validate on blur, not only on submit
                        // Tenancy makes the tenant connection the default, so the
                        // 'student_categories' table resolves correctly.
                        ->unique(
                            table: 'student_categories',
                            column: 'name',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'),
                        )
                        ->validationMessages([
                            'unique' => 'This entry already exists.',
                        ]),

                    Components\ColorPicker::make('color')
                        ->label('Display Color')
                        ->nullable(),

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

                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListStudentCategories::route('/'),
            'create' => Pages\CreateStudentCategory::route('/create'),
            'edit' => Pages\EditStudentCategory::route('/{record}/edit'),
        ];
    }
}
