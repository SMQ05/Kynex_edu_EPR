<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant\UserActivityLog;
use Illuminate\Support\Facades\Request;

/**
 * UserActivity — tiny helper to write user activity log entries.
 *
 * Append-only. Never throws (logging must never break the request).
 *
 * Call sites are NOT auto-wired into middleware. Suggested places to call:
 *  - On login:  a Login listener (App\Listeners) →
 *        UserActivity::log('login', null, null, 'Logged in');
 *  - On logout: a Logout listener →
 *        UserActivity::log('logout', null, null, 'Logged out');
 *  - On model writes: from a model observer / Filament page action, e.g.
 *        UserActivity::log('updated', $student::class, $student->id);
 *
 * Usage:
 *   use App\Support\UserActivity;
 *   UserActivity::log('login');
 *   UserActivity::log('created', Student::class, $student->id, 'Added student', ['name' => $student->name]);
 *   UserActivity::for($user)->record('viewed', $report::class, $report->id);
 */
final class UserActivity
{
    private ?string $schoolUserId = null;

    private function __construct(?string $schoolUserId)
    {
        $this->schoolUserId = $schoolUserId;
    }

    /** Log an activity for the currently authenticated school user. */
    public static function log(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $description = null,
        array $properties = [],
    ): ?UserActivityLog {
        $user = auth()->guard('school_users')->user();

        return (new self($user?->getKey()))
            ->record($action, $subjectType, $subjectId, $description, $properties);
    }

    /** Log on behalf of an explicit school user (e.g. login listener). */
    public static function for(mixed $user): self
    {
        $id = is_object($user) ? $user->getKey() : (is_string($user) ? $user : null);

        return new self($id);
    }

    /** Write the entry. Silent on failure. */
    public function record(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $description = null,
        array $properties = [],
    ): ?UserActivityLog {
        try {
            return UserActivityLog::create([
                'school_user_id' => $this->schoolUserId,
                'action'         => $action,
                'subject_type'   => $subjectType,
                'subject_id'     => $subjectId,
                'description'    => $description,
                'ip'             => self::safeIp(),
                'user_agent'     => self::safeUserAgent(),
                'properties'     => $properties === [] ? null : $properties,
                'created_at'     => now(),
            ]);
        } catch (\Throwable) {
            // Logging must never break the request.
            return null;
        }
    }

    private static function safeIp(): ?string
    {
        try {
            return Request::ip();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function safeUserAgent(): ?string
    {
        try {
            $ua = Request::userAgent();

            return $ua === null ? null : mb_substr($ua, 0, 512);
        } catch (\Throwable) {
            return null;
        }
    }
}
