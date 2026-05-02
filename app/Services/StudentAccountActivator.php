<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant;
use App\Models\Tenant\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Client as ResendClient;

/**
 * Issues a "set your password" invitation when an admin creates a student
 * (or parent) account. The SchoolUser row stays inactive until the
 * invitation is consumed and a password is set, so spam-created students
 * cannot accidentally log in.
 */
class StudentAccountActivator
{
    public function activateStudent(Student $student, bool $force = false): ?SchoolInvitation
    {
        if (! filled($student->email)) {
            return null;
        }

        $tenant = tenancy()->tenant;
        if (! $tenant instanceof Tenant) {
            Log::warning('StudentAccountActivator called outside tenant context', [
                'student_id' => $student->id,
            ]);
            return null;
        }

        $user = $this->ensureSchoolUser($student, $force);

        if ($user === null) {
            return null;
        }

        if ($student->school_user_id !== $user->id) {
            $student->forceFill(['school_user_id' => $user->id])->saveQuietly();
        }

        $invitation = SchoolInvitation::create([
            'school_name'       => $tenant->school_name,
            'contact_name'      => $student->full_name,
            'email'             => $student->email,
            'type'              => 'admin_invite',
            'token'             => Str::random(64),
            'expires_at'        => Carbon::now()->addDays(7),
            'email_verified_at' => now(),
            'tenant_id'         => $tenant->id,
            'meta'              => [
                'subject'      => 'student',
                'student_id'   => $student->id,
                'school_user'  => $user->id,
            ],
        ]);

        $this->sendActivationEmail($tenant, $student, $invitation->token);

        return $invitation;
    }

    public function activateGuardian(string $guardianName, string $email, string $tenantId, array $meta = []): ?SchoolInvitation
    {
        if (! filled($email)) {
            return null;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return null;
        }

        $invitation = SchoolInvitation::create([
            'school_name'       => $tenant->school_name,
            'contact_name'      => $guardianName,
            'email'             => $email,
            'type'              => 'admin_invite',
            'token'             => Str::random(64),
            'expires_at'        => Carbon::now()->addDays(7),
            'email_verified_at' => now(),
            'tenant_id'         => $tenant->id,
            'meta'              => array_merge(['subject' => 'parent'], $meta),
        ]);

        $this->sendActivationEmail($tenant, (object) [
            'full_name' => $guardianName,
            'email'     => $email,
        ], $invitation->token, 'parent');

        return $invitation;
    }

    protected function ensureSchoolUser(Student $student, bool $force = false): ?SchoolUser
    {
        if ($student->school_user_id) {
            $existing = SchoolUser::find($student->school_user_id);
            // If admin explicitly asked to resend ($force), proceed even
            // for already-active accounts so they get a fresh link.
            if ($existing && $existing->is_active && ! $force) {
                return null;
            }
            if ($existing) {
                return $existing;
            }
        }

        $user = SchoolUser::where('email', $student->email)->first();
        if (! $user) {
            $user = SchoolUser::create([
                'name'      => $student->full_name,
                'email'     => $student->email,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => false,
            ]);
        }

        if (! $user->hasRole('STUDENT')) {
            $user->assignRole('STUDENT');
        }

        return $user;
    }

    protected function sendActivationEmail(
        Tenant $tenant,
        $recipient,
        string $token,
        string $kind = 'student',
    ): void {
        $setPasswordUrl = route('school.set-password', ['token' => $token]);

        try {
            app(ResendClient::class)->emails->send([
                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                'to'      => $recipient->email,
                'subject' => $kind === 'parent'
                    ? "Activate your parent account at {$tenant->school_name}"
                    : "Activate your student account at {$tenant->school_name}",
                'html'    => view('emails.account-activation', [
                    'tenant'         => $tenant,
                    'recipient'      => $recipient,
                    'kind'           => $kind,
                    'setPasswordUrl' => $setPasswordUrl,
                    'expiresAt'      => Carbon::now()->addDays(7)->format('d M Y, h:i A'),
                ])->render(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send account activation email', [
                'tenant_id' => $tenant->id,
                'email'     => $recipient->email,
                'kind'      => $kind,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
