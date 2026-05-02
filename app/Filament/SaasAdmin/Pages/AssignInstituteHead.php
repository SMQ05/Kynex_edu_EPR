<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages;

use App\Models\SchoolUser;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Cross-tenant tool for SaaS admins to seat the top two roles in any
 * tenant: INSTITUTE_HEAD (one campus) and MULTI_INSTITUTE_HEAD (a chain
 * of campuses across the tenant). Both are SaaS-only roles per
 * RoleHierarchy::saasOnlyRoles, so they can never be assigned from
 * inside the tenant by a school admin.
 *
 * The flow:
 *   1. Pick a tenant.
 *   2. Either select an existing SchoolUser or enter a new email + name.
 *   3. Pick INSTITUTE_HEAD or MULTI_INSTITUTE_HEAD.
 *   4. Submit. New users receive a set-password email; existing users
 *      keep their password and just gain the role.
 */
class AssignInstituteHead extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Institute Heads';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Assign Institute / Multi-Institute Head';

    protected string $view = 'filament.saas-admin.pages.assign-institute-head';

    public ?string $tenant_id = null;
    public string $mode = 'new';     // 'new' | 'existing'
    public ?string $existing_user_id = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'INSTITUTE_HEAD'; // INSTITUTE_HEAD | MULTI_INSTITUTE_HEAD

    #[Computed]
    public function tenantOptions(): array
    {
        return Tenant::orderBy('school_name')
            ->get()
            ->mapWithKeys(fn (Tenant $t) => [$t->id => "{$t->school_name} ({$t->id})"])
            ->all();
    }

    #[Computed]
    public function existingUsers(): Collection
    {
        if (! $this->tenant_id) {
            return collect();
        }
        $rows = collect();
        Tenant::find($this->tenant_id)?->run(function () use (&$rows) {
            $rows = SchoolUser::orderBy('name')->get(['id', 'name', 'email']);
        });
        return $rows;
    }

    #[Computed]
    public function currentHolders(): array
    {
        if (! $this->tenant_id) {
            return ['INSTITUTE_HEAD' => [], 'MULTI_INSTITUTE_HEAD' => []];
        }

        $holders = ['INSTITUTE_HEAD' => [], 'MULTI_INSTITUTE_HEAD' => []];
        Tenant::find($this->tenant_id)?->run(function () use (&$holders) {
            foreach (['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'] as $role) {
                $holders[$role] = SchoolUser::role($role)
                    ->get(['id', 'name', 'email'])
                    ->map(fn ($u) => "{$u->name} <{$u->email}>")
                    ->all();
            }
        });
        return $holders;
    }

    public function assign(): void
    {
        if (! $this->tenant_id) {
            Notification::make()->title('Pick a tenant first')->danger()->send();
            return;
        }
        if (! in_array($this->role, ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'], true)) {
            Notification::make()->title('Invalid role')->danger()->send();
            return;
        }

        $tenant = Tenant::find($this->tenant_id);
        if (! $tenant) {
            Notification::make()->title('Tenant not found')->danger()->send();
            return;
        }

        $invite = null;

        $tenant->run(function () use (&$invite, $tenant) {
            if ($this->mode === 'existing') {
                if (! $this->existing_user_id) {
                    Notification::make()->title('Pick an existing user')->danger()->send();
                    return;
                }
                $user = SchoolUser::find($this->existing_user_id);
                if (! $user) {
                    Notification::make()->title('User not found in this tenant')->danger()->send();
                    return;
                }
                if (! $user->hasRole($this->role)) {
                    $user->assignRole($this->role);
                }
                if (! $user->is_active) {
                    $user->update(['is_active' => true]);
                }
                Notification::make()
                    ->title('Role assigned')
                    ->body("{$user->name} now holds {$this->role} in {$tenant->school_name}.")
                    ->success()
                    ->send();
                return;
            }

            // mode = 'new'
            if (! filled($this->name) || ! filled($this->email)) {
                Notification::make()->title('Name and email required for new account')->danger()->send();
                return;
            }

            $existing = SchoolUser::where('email', $this->email)->first();
            if ($existing) {
                if (! $existing->hasRole($this->role)) {
                    $existing->assignRole($this->role);
                }
                Notification::make()
                    ->title('User already existed — role added')
                    ->body("{$existing->name} now holds {$this->role}.")
                    ->success()
                    ->send();
                return;
            }

            $user = SchoolUser::create([
                'name'      => $this->name,
                'email'     => $this->email,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => false,
            ]);
            $user->assignRole($this->role);

            $invite = app(\App\Services\StudentAccountActivator::class)->activateGuardian(
                $user->name,
                $user->email,
                $tenant->id,
                ['role' => $this->role, 'school_user_id' => $user->id, 'subject' => 'institute_head'],
            );
        });

        if ($invite) {
            Notification::make()
                ->title('Account created and activation email sent')
                ->body("{$this->name} <{$this->email}> will receive a set-password link.")
                ->success()
                ->send();
        }

        $this->reset(['name', 'email', 'existing_user_id']);
    }
}
