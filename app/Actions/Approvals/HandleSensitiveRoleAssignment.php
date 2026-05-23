<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\ApprovalRequest;
use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant;
use App\Models\Tenant\InAppNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Client as ResendClient;
use Resend\Laravel\Facades\Resend;

/**
 * HandleSensitiveRoleAssignment — Executed when a sensitive_role_assignment
 * approval is approved or rejected.
 */
class HandleSensitiveRoleAssignment
{
    public function handle(ApprovalRequest $approval): void
    {
        $payload  = $approval->payload;
        $user     = SchoolUser::findOrFail($payload['school_user_id']);
        $roleName = $payload['role_name'];
        $action   = $payload['action']; // 'assign' or 'revoke'

        if ($action === 'assign') {
            $user->assignRole($roleName);

            if (! $user->active_role) {
                $user->update(['active_role' => $roleName]);
            }

            $verb = 'assigned to';
        } else {
            $user->removeRole($roleName);

            if ($user->active_role === $roleName) {
                $nextRole = $user->roles->where('name', '!=', $roleName)->first()?->name ?? 'STUDENT';
                $user->update(['active_role' => $nextRole]);
            }

            $verb = 'revoked from';
        }

        // In-app: notify requester
        InAppNotification::create([
            'user_id' => $approval->requested_by_id,
            'title'   => 'Role Change Approved',
            'body'    => "Role \"{$roleName}\" has been {$verb} {$user->name}.",
            'type'    => 'success',
        ]);

        // In-app: notify affected user
        InAppNotification::create([
            'user_id' => $user->id,
            'title'   => 'Role Updated',
            'body'    => "Your role \"{$roleName}\" has been {$verb} your account." .
                         ($action === 'revoke' ? " Active role: {$user->active_role}" : ''),
            'type'    => $action === 'assign' ? 'success' : 'warning',
        ]);

        // Emails (only for assign; revoke handled separately if needed)
        if ($action === 'assign') {
            $this->sendApprovalEmails($approval, $user, $roleName);

            // Set-password invite is mandatory for high-authority roles so the
            // newly seated holder can log in immediately under a fresh password.
            $tenant = $approval->tenant_id ? Tenant::find($approval->tenant_id) : null;
            self::sendSetPasswordInvite($user, $tenant);
        }
    }

    /**
     * Called by ApprovalService::reject() when this action type is rejected.
     * Tenancy is initialized by the caller before this runs.
     */
    public function handleRejection(ApprovalRequest $approval): void
    {
        $payload   = $approval->payload;
        $roleName  = $payload['role_name'];
        $user      = SchoolUser::find($payload['school_user_id']);
        $requester = SchoolUser::find($approval->requested_by_id);

        if ($requester) {
            InAppNotification::create([
                'user_id' => $requester->id,
                'title'   => 'Role Assignment Rejected',
                'body'    => "Request to assign \"{$roleName}\" to {$user?->name} was rejected." .
                             ($approval->review_notes ? " Note: {$approval->review_notes}" : ''),
                'type'    => 'danger',
            ]);
        }

        $this->sendRejectionEmail($approval, $user, $roleName, $requester);
    }

    /**
     * Create a SchoolInvitation (admin_invite) and email a set-password link
     * to the user. Re-usable from both the dual-approval flow and the
     * SaaS-direct AssignInstituteHead flow.
     *
     * Caller does NOT need to be inside tenant context — this method enters
     * the tenant context itself via $tenant->run() when one is supplied.
     */
    public static function sendSetPasswordInvite(SchoolUser $user, ?Tenant $tenant): bool
    {
        if (! $user->email) {
            Log::warning('sendSetPasswordInvite skipped — user has no email', ['user_id' => $user->id]);
            return false;
        }

        $work = function () use ($user, $tenant): bool {
            try {
                SchoolInvitation::where('email', $user->email)
                    ->where('type', 'admin_invite')
                    ->whereNull('consumed_at')
                    ->delete();

                $token = Str::random(64);
                SchoolInvitation::create([
                    'school_name'       => $tenant?->school_name ?? 'School',
                    'contact_name'      => $user->name,
                    'email'             => $user->email,
                    'type'              => 'admin_invite',
                    'token'             => $token,
                    'expires_at'        => Carbon::now()->addHours(3),
                    'email_verified_at' => now(),
                    'tenant_id'         => $tenant?->id,
                ]);

                $setPasswordUrl = route('school.set-password', ['token' => $token]);

                Resend::emails()->send([
                    'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                    'to'      => $user->email,
                    'subject' => 'Set your KynexEdu password — Institute Head access',
                    'html'    => view('emails.school-admin-invite', [
                        'tenant'         => $tenant,
                        'setPasswordUrl' => $setPasswordUrl,
                        'expiresAt'      => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                    ])->render(),
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::error('sendSetPasswordInvite failed', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);
                return false;
            }
        };

        // Need tenant context for SchoolInvitation (tenant model)
        if ($tenant && ! tenancy()->initialized) {
            return $tenant->run($work);
        }
        return $work();
    }

    // ── Private email helpers ──────────────────────────────────────────

    private function sendApprovalEmails(ApprovalRequest $approval, SchoolUser $user, string $roleName): void
    {
        $tenant     = Tenant::find($approval->tenant_id);
        $schoolName = $tenant?->school_name ?? 'Your School';
        $requester  = SchoolUser::find($approval->requested_by_id);
        $reviewNote = $approval->review_notes;
        $from       = $this->fromAddress();

        try {
            $resend = app(ResendClient::class);

            if ($user->email) {
                $html = view('emails.role-assignment-approved', [
                    'schoolName'    => $schoolName,
                    'roleName'      => $roleName,
                    'targetName'    => $user->name,
                    'requesterName' => $requester?->name ?? 'System',
                    'isNewHolder'   => true,
                    'reviewNote'    => $reviewNote,
                ])->render();

                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $user->email,
                    'subject' => "You Have Been Assigned as {$roleName} — {$schoolName}",
                    'html'    => $html,
                ]);
            }

            if ($requester && $requester->email && $requester->id !== $user->id) {
                $html = view('emails.role-assignment-approved', [
                    'schoolName'    => $schoolName,
                    'roleName'      => $roleName,
                    'targetName'    => $user->name,
                    'requesterName' => $requester->name,
                    'isNewHolder'   => false,
                    'reviewNote'    => $reviewNote,
                ])->render();

                $resend->emails->send([
                    'from'    => $from,
                    'to'      => $requester->email,
                    'subject' => "Approved: {$roleName} assigned to {$user->name} — {$schoolName}",
                    'html'    => $html,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('HandleSensitiveRoleAssignment: approval email failed', [
                'approval_id' => $approval->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function sendRejectionEmail(ApprovalRequest $approval, ?SchoolUser $user, string $roleName, ?SchoolUser $requester): void
    {
        if (! $requester?->email) {
            return;
        }

        $tenant     = Tenant::find($approval->tenant_id);
        $schoolName = $tenant?->school_name ?? 'Your School';

        try {
            $html = view('emails.role-assignment-rejected', [
                'schoolName'    => $schoolName,
                'roleName'      => $roleName,
                'targetName'    => $user?->name ?? 'the user',
                'requesterName' => $requester->name,
                'adminNote'     => $approval->review_notes,
            ])->render();

            app(ResendClient::class)->emails->send([
                'from'    => $this->fromAddress(),
                'to'      => $requester->email,
                'subject' => "Role Assignment Request Rejected — {$schoolName}",
                'html'    => $html,
            ]);
        } catch (\Throwable $e) {
            Log::warning('HandleSensitiveRoleAssignment: rejection email failed', [
                'approval_id' => $approval->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function fromAddress(): string
    {
        return config('mail.from.name', 'KynexEdu') . ' <' . config('mail.from.address', 'noreply@kynexsolutions.com') . '>';
    }
}
