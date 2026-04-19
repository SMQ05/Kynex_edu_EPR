<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\RoleManagementResource\Pages;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use App\Support\RoleHierarchy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class RoleManagementResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_roles';

    protected static ?string $model = Role::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    // ── Authorization ────────────────────────────────────────────

    /**
     * Only users with system.role_manage permission may access Roles & Permissions.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermissionTo('system.role_manage') ?? false;
    }

    /**
     * Editing a role's permissions is allowed only if the actor's level is
     * strictly above the role's level — prevents e.g. SCHOOL_ADMIN editing
     * INSTITUTE_HEAD permissions.
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $actor = auth()->user();
        if (! $actor || ! $actor->hasPermissionTo('system.role_manage')) {
            return false;
        }
        // bypass_approvals holders (INSTITUTE_HEAD, MULTI_INSTITUTE_HEAD) can edit all
        if ($actor->hasPermissionTo('bypass_approvals')) {
            return true;
        }
        $roleLevel = RoleHierarchy::levelOfRole($record->name);
        return RoleHierarchy::levelOf($actor) > $roleLevel;
    }

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Role Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(125)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record) => $record?->is_system ?? false)
                        ->helperText(fn (?Role $record) => ($record?->is_system ?? false)
                            ? 'System roles cannot be renamed.'
                            : null),

                    Components\Hidden::make('guard_name')
                        ->default('school_users'),

                    Components\Toggle::make('is_system')
                        ->label('System Role')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('System roles cannot be deleted.'),
                ]),

            Section::make('Permissions')
                ->schema([
                    Components\CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->columns(3)
                        ->gridDirection('row')
                        ->searchable()
                        ->bulkToggleable(),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Role::query()
                    ->where('guard_name', 'school_users')
                    ->withCount([
                        'users as users_count' => fn ($q) => $q->where(
                            'model_has_roles.model_type',
                            (new \App\Models\SchoolUser())->getMorphClass()
                        ),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Role $record): string => $record->is_system ? 'primary' : 'gray'),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('System Role'),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (Role $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Role $record): bool =>
                        ! $record->is_system && static::canEdit($record)
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
