<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\Department;
use App\Models\SchoolUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class DepartmentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_staff';

    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?int $navigationSort = 8;

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('head_id')
                    ->label('Department Head')
                    ->relationship('head', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('head.name')
                    ->label('Head')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('designations_count')
                    ->counts('designations')
                    ->label('Designations')
                    ->sortable(),

                TextColumn::make('staff_profiles_count')
                    ->counts('staffProfiles')
                    ->label('Staff')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => DepartmentResource\Pages\ListDepartments::route('/'),
            'create' => DepartmentResource\Pages\CreateDepartment::route('/create'),
            'edit'   => DepartmentResource\Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
