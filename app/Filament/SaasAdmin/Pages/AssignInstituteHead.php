<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages;

use App\Models\SchoolUser;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Resend\Client as ResendClient;

/**
 * Cross-tenant tool for SaaS admins to seat the top two roles in any
 * tenant: INSTITUTE_HEAD (one campus) and MULTI_INSTITUTE_HEAD (a chain
 * of campuses across the tenant).
 *
 * Emails sent at every step:
 *   - New holder: "You have been assigned [role]" (always)
 *   - Existing holder(s) of same role: "A new [role] has been added"
 *   - SaaS admin (audit copy): "Assignment completed"
 *   - For brand-new accounts: also a set-password activation link
 */
class AssignInstituteHead extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Institute Heads';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Assign Institute / Multi-Institute Head';

    protected string $view = 'filament.saas-admin.pages.assign-institute-head';

    public ?string $tenant_id = null;
    public string $mode = 'new';
    public ?string $existing_user_id = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'INSTITUTE_HEAD';

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

        $result = [
            'user'                  => null,
            'wasNewAccount'         => false,
            'wasAlreadyAssigned'    => false,
            'previousHolderEmails'  => [],
            'activationEmailSent'   => false,
        ];

        $tenant->run(function () use (&$result, $tenant) {
            // Snapshot current holders BEFORE assignment so we can email them.
            $previousHolders = SchoolUser::role($this->role)
                ->get(['id', 'name', 'email'])
                ->filter(fn ($u) => filled($u->email))
                ->all();
            $result['previousHolderEmails'] = collect($previousHolders)
                ->map(fn ($u) => ['name' => $u->name, 'email' => $u->email])
                ->all();

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

                if ($user->hasRole($this->role)) {
                    $result['wasAlreadyAssigned'] = true;
                } else {
                    $user->assignRole($this->role);
                }
                if (! $user->is_active) {
                    $user->update(['is_active' => true]);
                }
                $result['user'] = $user;
                return;
            }

            // mode = 'new'
            if (! filled($this->name) || ! filled($this->email)) {
                Notification::make()->title('Name and email required for new account')->danger()->send();
                return;
            }

            $existing = SchoolUser::where('email', $this->email)->first();
            if ($existing) {
                if ($existing->hasRole($this->role)) {
                    $result['wasAlreadyAssigned'] = true;
                } else {
                    $existing->assignRole($this->role);
                }
                $result['user'] = $existing;
                return;
            }

            $user = SchoolUser::create([
                'name'      => $this->name,
                'email'     => $this->email,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => false,
            ]);
            $user->assignRole($this->role);

            try {
                app(\App\Services\StudentAccountActivator::class)->activateGuardian(
                    $user->name,
                    $user->email,
                    $tenant->id,
                    ['role' => $this->role, 'school_user_id' => $user->id, 'subject' => 'institute_head'],
                );
                $result['activationEmailSent'] = true;
            } catch (\Throwable $e) {
                Log::warning('AssignInstituteHead activation email failed', ['error' => $e->getMessage()]);
            }

            $result['user']          = $user;
            $result['wasNewAccount'] = true;
        });

        if (! $result['user']) {
            return; // a Notification was already sent inside the closure
        }

        // Even if "already assigned" → still send a confirmation email so the user
        // can verify the system works. The user explicitly asked for emails at every step.
        $emailReport = $this->sendAllAssignmentEmails($tenant, $result);

        // ── UI summary ──
        $body = $result['wasAlreadyAssigned']
            ? "{$result['user']->name} already had {$this->role}. Confirmation emails re-sent."
            : "{$result['user']->name} now holds {$this->role} in {$tenant->school_name}.";

        if (! empty($emailReport['sent'])) {
            $body .= ' Emails sent to: ' . implode(', ', $emailReport['sent']) . '.';
        }
        if (! empty($emailReport['failed'])) {
            $body .= ' Failed: ' . implode(', ', $emailReport['failed']) . ' — check logs.';
        }
        if ($result['activationEmailSent']) {
            $body .= ' Set-password activation link sent.';
        }

        Notification::make()
            ->title($result['wasAlreadyAssigned'] ? 'Already assigned — emails re-sent' : 'Role assigned — emails sent')
            ->body($body)
            ->success()
            ->duration(15000)
            ->send();

        $this->reset(['name', 'email', 'existing_user_id']);
    }

    /**
     * Send all assignment notification emails. Each send is wrapped in its own
     * try/catch so one failing recipient never blocks the others.
     *
     * @return array{sent: array<int,string>, failed: array<int,string>}
     */
    private function sendAllAssignmentEmails(Tenant $tenant, array $result): array
    {
        $user        = $result['user'];
        $schoolName  = $tenant->school_name ?? 'Your School';
        $roleName    = $this->role;
        $assignedBy  = auth()->user()?->name
                        ?? auth()->user()?->email
                        ?? 'KynexEdu Platform Team';
        $from        = config('mail.from.name', 'KynexEdu') . ' <' . config('mail.from.address', 'noreply@kynexsolutions.com') . '>';
        $resend      = app(ResendClient::class);
        $sent        = [];
        $failed      = [];

        // ── 1. Email to the new holder ──
        if ($user->email) {
            try {
                $html = view('emails.role-assigned-direct', [
                    'schoolName'     => $schoolName,
                    'roleName'       => $roleName,
                    'newHolderName'  => $user->name,
                    'newHolderEmail' => $user->email,
                    'assignedByName' => $assignedBy,
                    'recipientName'  => $user->name,
                    'audience'       => 'new_holder',
                    'activationNote' => 'A separate email with a set-password link was just sent to this address — use it to log in for the first time as ' . $roleName . '.',
                ])->render();
                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $user->email,
                    'subject' => "You Have Been Assigned as {$roleName} — {$schoolName}",
                    'html'    => $html,
                ]);
                $sent[] = $user->email;
            } catch (\Throwable $e) {
                Log::warning('AssignInstituteHead new-holder email failed', [
                    'to'    => $user->email,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $user->email;
            }

            // ── 1b. Set-password invite (mandatory for high-authority roles) ──
            // Skip when wasNewAccount is true — StudentAccountActivator already
            // sent its own activation email in that branch.
            if (! $result['wasNewAccount']) {
                $sent_pw = \App\Actions\Approvals\HandleSensitiveRoleAssignment::sendSetPasswordInvite($user, $tenant);
                if ($sent_pw) {
                    $sent[] = $user->email . ' (set-password)';
                } else {
                    $failed[] = $user->email . ' (set-password)';
                }
            }
        } else {
            $failed[] = '(new holder has no email)';
        }

        // ── 2. Emails to existing holders (notify them) ──
        foreach ($result['previousHolderEmails'] as $holder) {
            if ($holder['email'] === $user->email) {
                continue; // skip self
            }
            try {
                $html = view('emails.role-assigned-direct', [
                    'schoolName'     => $schoolName,
                    'roleName'       => $roleName,
                    'newHolderName'  => $user->name,
                    'newHolderEmail' => $user->email,
                    'assignedByName' => $assignedBy,
                    'recipientName'  => $holder['name'],
                    'audience'       => 'existing_holder',
                    'activationNote' => null,
                ])->render();
                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $holder['email'],
                    'subject' => "New {$roleName} Assigned at {$schoolName}",
                    'html'    => $html,
                ]);
                $sent[] = $holder['email'];
            } catch (\Throwable $e) {
                Log::warning('AssignInstituteHead existing-holder email failed', [
                    'to'    => $holder['email'],
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $holder['email'];
            }
        }

        // ── 3. Audit copy to SaaS admin ──
        $saasEmail = env('SAAS_ADMIN_EMAIL', 'admin@kynexedu.com');
        if ($saasEmail) {
            try {
                $html = view('emails.role-assigned-direct', [
                    'schoolName'     => $schoolName,
                    'roleName'       => $roleName,
                    'newHolderName'  => $user->name,
                    'newHolderEmail' => $user->email,
                    'assignedByName' => $assignedBy,
                    'recipientName'  => 'SaaS Admin',
                    'audience'       => 'audit',
                    'activationNote' => null,
                ])->render();
                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $saasEmail,
                    'subject' => "[Audit] {$roleName} assigned to {$user->name} at {$schoolName}",
                    'html'    => $html,
                ]);
                $sent[] = $saasEmail;
            } catch (\Throwable $e) {
                Log::warning('AssignInstituteHead audit email failed', [
                    'to'    => $saasEmail,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = $saasEmail;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
