<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\SubjectType;
use App\Filament\SchoolAdmin\Resources\SubjectResource\Pages;
use App\Models\Tenant\Subject;
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

class SubjectResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_subjects';

    protected static ?string $model = Subject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Subjects';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 5;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Subject Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true) // re-validate on blur, not only on submit
                        // Subject name must be unique; prevents duplicate entries.
                        // Tenancy makes the tenant connection the default, so the
                        // 'subjects' table resolves correctly. Exclude soft-deleted
                        // rows so a name can be reused after deletion.
                        ->unique(
                            table: 'subjects',
                            column: 'name',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'),
                        )
                        ->validationMessages([
                            'unique' => 'A subject with this name already exists.',
                        ]),

                    Components\TextInput::make('code')
                        ->label('Subject Code')
                        ->maxLength(20)
                        ->placeholder('e.g. MATH, ENG')
                        ->nullable(),

                    Components\Select::make('subject_type')
                        ->label('Type')
                        ->options(SubjectType::class)
                        ->required(),

                    Components\TextInput::make('credit_hours')
                        ->label('Credit Hours')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10)
                        ->required()
                        ->default(1),

                    Components\ColorPicker::make('color')
                        ->label('Display Color')
                        ->nullable(),

                    Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
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

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_hours')
                    ->label('Credits')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Type')
                    ->options(SubjectType::class),

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
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
