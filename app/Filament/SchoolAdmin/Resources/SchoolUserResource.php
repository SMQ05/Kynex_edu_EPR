<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Actions\Approvals\HandleRoleRevocation;
use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use App\Services\ApprovalService;
use App\Support\RoleHierarchy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;
use Spatie\Permission\Models\Role;

class SchoolUserResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_staff';

    protected static ?string $model = SchoolUser::class;

    protected static ?string $modelLabel = 'School User';

    protected static ?string $pluralModelLabel = 'School Users';

    protected static ?string $slug = 'school-users';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'Staff & HR';

    protected static ?int $navigationSort = 2;

    // ── Authorization ────────────────────────────────────────────────

    protected static function currentSchoolUser(): ?SchoolUser
    {
        $user = auth()->guard('school_users')->user();

        if ($user instanceof SchoolUser) {
            return $user;
        }

        $user = auth()->user();

        return $user instanceof SchoolUser ? $user : null;
    }

    /**
     * Approval-bypass gate, *active-role aware*. Per spec, only the
     * institute or multi-institute head — and only while acting in that
     * role — may bypass the approval queue. SCHOOL_ADMIN holds the
     * `bypass_approvals` permission for legacy reasons but must still
     * route through approval for hire/suspend/role-assignment actions.
     */
    public static function actorActsAsInstituteHead(?SchoolUser $actor): bool
    {
        if (! $actor) {
            return false;
        }
        $activeRole = $actor->active_role ?? $actor->roles->first()?->name;
        return in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);
    }

    /**
     * Only users with system.user_manage may see the School Users menu item.
     */
    public static function canViewAny(): bool
    {
        return static::currentSchoolUser()?->hasPermissionTo('system.user_manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::currentSchoolUser()?->hasPermissionTo('system.user_manage') ?? false;
    }

    /**
     * Edit allowed only if the actor outranks the target (or has bypass_approvals).
     * Editing your own profile is always allowed.
     */
    public static function canEdit(Model $record): bool
    {
        $actor = static::currentSchoolUser();
        if (! $actor) {
            return false;
        }
        if ($actor->is($record)) {
            return true;
        }
        return RoleHierarchy::canAct($actor, $record);
    }

    /**
     * Delete allowed only if actor outranks the target AND target has no SaaS-only role.
     * You cannot delete your own account.
     */
    public static function canDelete(Model $record): bool
    {
        $actor = static::currentSchoolUser();
        if (! $actor || $actor->is($record)) {
            return false;
        }
        // INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD must be removed by SaaS admin only
        foreach ($record->roles as $role) {
            if (RoleHierarchy::isSaasOnly($role->name)) {
                return false;
            }
        }
        return RoleHierarchy::canAct($actor, $record);
    }

    /* ──────────────────────────────────────────── Form ── */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                // Password is set ONCE at creation only. Existing users
                // change/reset their own password via the public flows;
                // admins can trigger that with the row actions
                // "Send Set-Password Link" / "Send Password Reset".
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Set once at creation. To change later, use the "Send Password Reset" action on the user row.')
                    ->maxLength(255),

                Select::make('campus_id')
                    ->relationship('campus', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                // Primary role determines which dashboard the user sees on
                // login and what they can do. Required on create. Edit
                // changes the user's *single* primary role — extra roles
                // (stacking) are managed separately via the row "Manage
                // Roles" action.
                Select::make('primary_role')
                    ->label('Primary role (drives login dashboard)')
                    ->options(static::primaryRoleOptions())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->dehydrated(false) // we sync to roles + active_role manually
                    ->helperText('TEACHER → teacher dashboard. SCHOOL_ADMIN → admin dashboard. Pick exactly one. Extra roles can be added later via the user list.')
                    ->afterStateHydrated(function (Select $component, ?SchoolUser $record) {
                        if (! $record) {
                            return;
                        }
                        $component->state(
                            $record->active_role ?? $record->roles->first()?->name
                        );
                    }),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    /**
     * Roles selectable on the create form, with INSTITUTE_HEAD /
     * MULTI_INSTITUTE_HEAD removed — those are SaaS-only and assigned
     * cross-tenant by the SaaS admin (see /saas/assign-institute-head).
     */
    public static function primaryRoleOptions(): array
    {
        return collect(\Spatie\Permission\Models\Role::where('guard_name', 'school_users')->pluck('name'))
            ->reject(fn (string $name) => RoleHierarchy::isSaasOnly($name))
            ->mapWithKeys(fn (string $name) => [$name => $name])
            ->all();
    }

    /* ──────────────────────────────────────────── Table ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('campus.name')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('active_role')
                    ->label('Active Role')
                    ->badge()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('campus_id')
                    ->relationship('campus', 'name')
                    ->label('Campus')
                    ->preload(),

                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn (SchoolUser $record): bool => static::canEdit($record)),

                // ── Assign Role ──────────────────────────────────────────
                Action::make('assignRole')
                    ->label('Assign Role')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (SchoolUser $record): bool =>
                        static::currentSchoolUser()?->hasPermissionTo('system.user_manage') &&
                        RoleHierarchy::canAct(static::currentSchoolUser(), $record)
                    )
                    ->modalHeading('Assign Role')
                    ->modalSubmitActionLabel('Assign')
                    ->form([
                        Select::make('role_names')
                            ->label('Roles to Assign')
                            ->options(function (): array {
                                $actor = static::currentSchoolUser();

                                // SaaS-only roles (INSTITUTE_HEAD and MULTI_INSTITUTE_HEAD)
                                // are excluded entirely except when the actor already
                                // holds that role themselves — i.e. the institute head
                                // can transfer / re-assign their own role to a successor.
                                $actorLevel = RoleHierarchy::levelOf($actor);

                                return Role::where('guard_name', 'school_users')
                                    ->get()
                                    ->filter(function (Role $role) use ($actorLevel) {
                                        if (! RoleHierarchy::isSaasOnly($role->name)) {
                                            return true;
                                        }
                                        // Only an actor at or above this protected
                                        // role's level may assign it from inside the
                                        // school panel (self-replacement / handover).
                                        return $actorLevel >= RoleHierarchy::levelOfRole($role->name);
                                    })
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->helperText(function (): string {
                                return static::actorActsAsInstituteHead(static::currentSchoolUser())
                                    ? 'You are acting as an institute head — assignments save directly.'
                                    : 'You can select one or more roles. Protected roles require Institute Head approval.';
                            }),
                    ])
                    ->action(function (SchoolUser $record, array $data) {
                        $actor     = static::currentSchoolUser();
                        $roleNames = (array) ($data['role_names'] ?? []);

                        $assigned = [];
                        $pending  = [];
                        $skipped  = [];

                        foreach ($roleNames as $roleName) {
                            // ── INSTITUTE_HEAD: only INSTITUTE_HEAD or higher may
                            // assign / transfer this role from the school side ─────────
                            if ($roleName === 'INSTITUTE_HEAD') {
                                if (RoleHierarchy::levelOf($actor) < RoleHierarchy::levelOfRole('INSTITUTE_HEAD')) {
                                    $skipped[] = "{$roleName} (only INSTITUTE_HEAD or SaaS admin can assign)";
                                    continue;
                                }

                                if ($record->hasRole('INSTITUTE_HEAD')) {
                                    $skipped[] = "{$roleName} (already assigned)";
                                    continue;
                                }

                                if (static::actorActsAsInstituteHead($actor)) {
                                    $record->assignRole('INSTITUTE_HEAD');
                                    // Newly assigned role becomes the user's active dashboard.
                                    // Admin can override later via the user edit form's
                                    // "Active role" select.
                                    $record->update(['active_role' => 'INSTITUTE_HEAD']);
                                    $assigned[] = $roleName;
                                    continue;
                                }

                                $existingHead = SchoolUser::role('INSTITUTE_HEAD')->first();

                                if ($existingHead === null) {
                                    $record->assignRole('INSTITUTE_HEAD');
                                    // Newly assigned role becomes the user's active dashboard.
                                    // Admin can override later via the user edit form's
                                    // "Active role" select.
                                    $record->update(['active_role' => 'INSTITUTE_HEAD']);
                                    $assigned[] = $roleName;
                                    continue;
                                }

                                app(ApprovalService::class)->submit(
                                    requestedBy: $actor,
                                    actionType: 'sensitive_role_assignment',
                                    subject: $record,
                                    payload: [
                                        'school_user_id' => $record->id,
                                        'role_name'      => 'INSTITUTE_HEAD',
                                        'action'         => 'assign',
                                    ],
                                );

                                InAppNotification::create([
                                    'user_id' => $existingHead->id,
                                    'title'   => 'Institute Head Assignment Request',
                                    'body'    => "{$actor->name} has requested to assign INSTITUTE_HEAD to {$record->name}. Please review your Approval Queue.",
                                    'type'    => 'info',
                                ]);

                                $pending[] = $roleName;
                                continue;
                            }

                            // ── SaaS-only roles — hard block ──
                            if (RoleHierarchy::isSaasOnly($roleName)) {
                                $skipped[] = "{$roleName} (SaaS-only)";
                                continue;
                            }

                            // Already has this role?
                            if ($record->hasRole($roleName)) {
                                $skipped[] = "{$roleName} (already assigned)";
                                continue;
                            }

                            // Bypass actors: assign immediately
                            if (static::actorActsAsInstituteHead($actor)) {
                                $record->assignRole($roleName);
                                $record->update(['active_role' => $roleName]);
                                $assigned[] = $roleName;
                                continue;
                            }

                            // Protected roles: route through approval
                            if (RoleHierarchy::isProtected($roleName)) {
                                app(ApprovalService::class)->submit(
                                    requestedBy: $actor,
                                    actionType: 'sensitive_role_assignment',
                                    subject: $record,
                                    payload: [
                                        'school_user_id' => $record->id,
                                        'role_name'      => $roleName,
                                        'action'         => 'assign',
                                    ],
                                );
                                $pending[] = $roleName;
                                continue;
                            }

                            // Non-protected roles: assign immediately
                            $record->assignRole($roleName);
                            $record->update(['active_role' => $roleName]);
                            $assigned[] = $roleName;
                        }

                        // ── Summary notifications ──
                        if (! empty($assigned)) {
                            Notification::make()
                                ->title('Roles Assigned')
                                ->body('Assigned to ' . $record->name . ': ' . implode(', ', $assigned))
                                ->success()
                                ->send();
                        }

                        if (! empty($pending)) {
                            Notification::make()
                                ->title('Approval Required')
                                ->body('Submitted for approval: ' . implode(', ', $pending))
                                ->warning()
                                ->send();
                        }

                        if (! empty($skipped)) {
                            Notification::make()
                                ->title('Some Roles Skipped')
                                ->body('Skipped: ' . implode(', ', $skipped))
                                ->danger()
                                ->send();
                        }
                    }),

                // ── Suspend / Deactivate Staff ───────────────────────────
                Action::make('suspendStaff')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (SchoolUser $record): bool =>
                        $record->is_active &&
                        ! static::currentSchoolUser()?->is($record) &&
                        RoleHierarchy::canAct(static::currentSchoolUser(), $record)
                    )
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for suspension')
                            ->required()
                            ->minLength(10)
                            ->rows(3),
                    ])
                    ->action(function (SchoolUser $record, array $data) {
                        $actor = static::currentSchoolUser();

                        if (! static::actorActsAsInstituteHead($actor)) {
                            app(ApprovalService::class)->submit(
                                requestedBy: $actor,
                                actionType: 'staff_status_change',
                                subject: $record,
                                payload: [
                                    'action' => 'suspend',
                                    'reason' => $data['reason'],
                                ],
                            );

                            Notification::make()
                                ->title('Suspension submitted for approval')
                                ->body("Suspending {$record->name} requires Institute Head approval.")
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->title('Staff Suspended')
                            ->body("{$record->name} has been suspended.")
                            ->success()
                            ->send();
                    }),

                // ── Revoke Role ──────────────────────────────────────────
                Action::make('revokeRole')
                    ->label('Revoke Role')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->visible(fn (SchoolUser $record): bool =>
                        ! static::currentSchoolUser()?->is($record) &&
                        RoleHierarchy::canAct(static::currentSchoolUser(), $record)
                    )
                    ->form([
                        Select::make('role_name')
                            ->label('Role to Revoke')
                            ->options(function (SchoolUser $record): array {
                                // Only show roles the actor is allowed to touch
                                $actor = static::currentSchoolUser();
                                return $record->roles
                                    ->filter(fn (Role $role) =>
                                        // SaaS-only roles are never revocable here
                                        ! RoleHierarchy::isSaasOnly($role->name)
                                    )
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->required(),
                    ])
                    ->action(function (SchoolUser $record, array $data) {
                        $actor = static::currentSchoolUser();
                        $roleName = $data['role_name'];

                        // SaaS-only roles: block unconditionally
                        if (RoleHierarchy::isSaasOnly($roleName)) {
                            Notification::make()
                                ->title('Not allowed')
                                ->body("The {$roleName} role can only be removed by the KynexEdu platform team.")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Protected roles: require approval unless actor is institute head
                        if (
                            RoleHierarchy::isProtected($roleName) &&
                            ! static::actorActsAsInstituteHead($actor)
                        ) {
                            app(ApprovalService::class)->submit(
                                requestedBy: $actor,
                                actionType: 'sensitive_role_assignment',
                                subject: $record,
                                payload: [
                                    'school_user_id' => $record->id,
                                    'role_name'      => $roleName,
                                    'action'         => 'revoke',
                                ],
                            );

                            Notification::make()
                                ->title('Role revocation submitted for approval')
                                ->body("Revoking {$roleName} from {$record->name} requires Institute Head approval.")
                                ->warning()
                                ->send();

                            return;
                        }

                        // Non-protected or bypass: execute immediately
                        HandleRoleRevocation::revokeRole($record, $roleName);

                        Notification::make()
                            ->title('Role Revoked')
                            ->body("The {$roleName} role has been removed from {$record->name}.")
                            ->success()
                            ->send();
                    }),

                // ── Send Set-Password / Invite Link ──────────────────────
                Action::make('sendSetPasswordLink')
                    ->label('Send Set-Password Link')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->tooltip('Send a set-password invitation link to this user')
                    ->requiresConfirmation()
                    ->modalHeading('Send Set-Password Link')
                    ->modalDescription(fn (SchoolUser $record) => "This will email a set-password link (valid 3 hours) to {$record->email}.")
                    ->visible(fn (SchoolUser $record): bool =>
                        static::currentSchoolUser()?->hasPermissionTo('system.user_manage') &&
                        RoleHierarchy::canAct(static::currentSchoolUser(), $record)
                    )
                    ->action(function (SchoolUser $record) {
                        $tenant = tenant();

                        SchoolInvitation::where('email', $record->email)
                            ->where('type', 'admin_invite')
                            ->whereNull('consumed_at')
                            ->delete();

                        $token = Str::random(64);
                        SchoolInvitation::create([
                            'school_name'        => $tenant?->school_name ?? 'School',
                            'contact_name'       => $record->name,
                            'email'              => $record->email,
                            'type'               => 'admin_invite',
                            'token'              => $token,
                            'expires_at'         => Carbon::now()->addHours(3),
                            'email_verified_at'  => now(),
                            'tenant_id'          => $tenant?->id,
                        ]);

                        $setPasswordUrl = route('school.set-password', ['token' => $token]);

                        try {
                            Resend::emails()->send([
                                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                                'to'      => $record->email,
                                'subject' => 'Set your KynexEdu password',
                                'html'    => view('emails.school-admin-invite', [
                                    'tenant'         => $tenant,
                                    'setPasswordUrl' => $setPasswordUrl,
                                    'expiresAt'      => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                                ])->render(),
                            ]);

                            Notification::make()
                                ->title('Set-password link sent')
                                ->body("Link emailed to {$record->email}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('Failed to send set-password link', [
                                'email' => $record->email,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Email delivery failed')
                                ->body('Link created but email could not be sent. Check logs.')
                                ->danger()
                                ->send();
                        }
                    }),

                // ── Send Password Reset Link ─────────────────────────────
                Action::make('sendPasswordResetLink')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->tooltip('Send a password reset link to this user')
                    ->requiresConfirmation()
                    ->modalHeading('Send Password Reset Link')
                    ->modalDescription(fn (SchoolUser $record) => "This will email a password reset link (valid 3 hours) to {$record->email}.")
                    ->visible(fn (SchoolUser $record): bool =>
                        static::currentSchoolUser()?->hasPermissionTo('system.user_manage') &&
                        RoleHierarchy::canAct(static::currentSchoolUser(), $record)
                    )
                    ->action(function (SchoolUser $record) {
                        $tenant = tenant();

                        SchoolInvitation::where('email', $record->email)
                            ->where('type', 'password_reset')
                            ->whereNull('consumed_at')
                            ->delete();

                        $token = Str::random(64);
                        SchoolInvitation::create([
                            'school_name'        => $tenant?->school_name ?? 'School',
                            'contact_name'       => $record->name,
                            'email'              => $record->email,
                            'type'               => 'password_reset',
                            'token'              => $token,
                            'expires_at'         => Carbon::now()->addHours(3),
                            'email_verified_at'  => now(),
                            'tenant_id'          => $tenant?->id,
                        ]);

                        $resetUrl = route('school.reset-password', ['token' => $token]);

                        try {
                            Resend::emails()->send([
                                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                                'to'      => $record->email,
                                'subject' => 'Reset your KynexEdu password',
                                'html'    => view('emails.school-reset-password', [
                                    'name'      => $record->name,
                                    'resetUrl'  => $resetUrl,
                                    'expiresAt' => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                                ])->render(),
                            ]);

                            Notification::make()
                                ->title('Password reset link sent')
                                ->body("Reset link emailed to {$record->email}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('Failed to send password reset link', [
                                'email' => $record->email,
                                'error' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Email delivery failed')
                                ->body('Link created but email could not be sent. Check logs.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $actor = static::currentSchoolUser();
                            $activeRole = $actor?->active_role ?? $actor?->roles->first()?->name;
                            $bypass = in_array($activeRole, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true);

                            if ($bypass) {
                                $records->each->delete();
                                Notification::make()
                                    ->title('Staff removed')
                                    ->body("{$records->count()} accounts deleted.")
                                    ->success()
                                    ->send();
                                return;
                            }

                            $count = 0;
                            foreach ($records as $record) {
                                if ($actor && $actor->is($record)) {
                                    continue;
                                }
                                app(ApprovalService::class)->submit(
                                    requestedBy: $actor,
                                    actionType: 'staff_delete',
                                    subject: $record,
                                    payload: [
                                        'school_user_id' => $record->id,
                                        'name'           => $record->name,
                                        'reason'         => 'Bulk staff removal via Users list',
                                    ],
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} staff removals sent for approval")
                                ->body('The institute head will review them in the Approval Queue.')
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }

    /* ──────────────────────────────────────────── Pages ── */

    public static function getPages(): array
    {
        return [
            'index'  => SchoolUserResource\Pages\ListSchoolUsers::route('/'),
            'create' => SchoolUserResource\Pages\CreateSchoolUser::route('/create'),
            'edit'   => SchoolUserResource\Pages\EditSchoolUser::route('/{record}/edit'),
        ];
    }
}
