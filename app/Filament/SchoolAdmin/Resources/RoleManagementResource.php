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
        $actor = auth('school_users')->user() ?? auth()->user();

        return static::actorHasPermission($actor, 'system.role_manage');
    }

    /**
     * Editing a role's permissions requires the actor's *active* role to
     * strictly outrank the target role. SaaS-only roles (INSTITUTE_HEAD,
     * MULTI_INSTITUTE_HEAD) are managed by the SaaS admin and cannot be
     * edited from the school side at all — even an INSTITUTE_HEAD
     * shouldn't be able to demote themselves by stripping their own
     * role's permissions.
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $actor = auth('school_users')->user() ?? auth()->user();
        if (! $actor || ! static::actorHasPermission($actor, 'system.role_manage')) {
            return false;
        }

        // SaaS-only roles are off-limits to everyone in the school panel.
        if (RoleHierarchy::isSaasOnly($record->name)) {
            return false;
        }

        // Use *active* role for the rank check so a user with stacked
        // roles can only act as the role they're currently logged in as.
        $activeRole = (string) ($actor->active_role ?? $actor->roles->first()?->name ?? '');
        $actorLevel = $activeRole !== '' ? RoleHierarchy::levelOfRole($activeRole) : 0;
        $roleLevel  = RoleHierarchy::levelOfRole($record->name);

        return $actorLevel > $roleLevel;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Reuse the same rank gate; deletion is at least as sensitive as edit.
        return static::canEdit($record);
    }

    private static function actorHasPermission(?object $actor, string $permission): bool
    {
        if (! $actor || ! method_exists($actor, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $actor->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
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
        $actor = auth('school_users')->user() ?? auth()->user();
        $activeRole = (string) ($actor?->active_role ?? $actor?->roles?->first()?->name ?? '');
        $isInstituteLevel = in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);

        return $table
            ->query(
                // Count school users per role via the morph table directly. Spatie's
                // default `users` relation hits the legacy `users` table which doesn't
                // exist in tenant DBs (we use `school_users` instead).
                Role::query()
                    ->where('guard_name', 'school_users')
                    // Hide SaaS-only roles for actors below institute level.
                    // SCHOOL_ADMIN doesn't see INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD
                    // in the list at all, so they cannot inspect or modify them.
                    ->when(! $isInstituteLevel, fn ($q) => $q->whereNotIn('name', ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']))
                    ->select('roles.*')
                    ->selectSub(
                        function ($q) {
                            $q->from('model_has_roles')
                                ->whereColumn('model_has_roles.role_id', 'roles.id')
                                ->where(
                                    'model_has_roles.model_type',
                                    (new \App\Models\SchoolUser())->getMorphClass(),
                                )
                                ->selectRaw('count(*)');
                        },
                        'users_count',
                    )
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
