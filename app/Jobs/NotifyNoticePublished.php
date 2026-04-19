<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SchoolUser;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Notice;
use App\Models\Tenant\Student;
use App\Services\DeepLinkService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * NotifyNoticePublished — Sends in-app notifications to target roles when
 * a notice is published, and WhatsApp/SMS to guardians of student users.
 *
 * Phase 9.6 — Dispatched from NoticeObserver when is_published transitions to true.
 *
 * - If target_roles is empty/null, notifies ALL active SchoolUsers.
 * - If target_roles is set, notifies only SchoolUsers whose active_role is in that list.
 * - For students: also notifies their primary guardian via WhatsApp/SMS (if enabled).
 */
class NotifyNoticePublished implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Notice $notice,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notice = $this->notice;

        $targetRoles = $notice->target_roles;
        $noticeUrl   = DeepLinkService::generate('notice.published', [
            'notice_id' => $notice->id,
        ]);

        // Build the user query based on target roles
        $query = SchoolUser::where('is_active', true);

        if (! empty($targetRoles)) {
            $query->whereIn('active_role', $targetRoles);
        }

        $users = $query->get();
        $total = 0;

        foreach ($users as $user) {
            try {
                // 1. In-app notification for every target user
                InAppNotification::create([
                    'user_id'    => $user->id,
                    'title'      => $notice->title,
                    'body'       => \Illuminate\Support\Str::limit(strip_tags($notice->content), 200),
                    'type'       => 'info',
                    'action_url' => $noticeUrl,
                ]);

                // 2. If user is a student, also notify their primary guardian via WhatsApp/SMS
                if ($user->active_role === 'STUDENT') {
                    $student = Student::with('guardians')
                        ->where('school_user_id', $user->id)
                        ->first();

                    if ($student) {
                        $guardian = $student->guardians()
                            ->where('is_primary_contact', true)
                            ->first()
                            ?? $student->guardians()->first();

                        if ($guardian) {
                            $notificationService->sendFromTemplate(
                                templateSlug: 'notice.published',
                                notifiable:   $guardian,
                                data: [
                                    'notice_title'   => $notice->title,
                                    'notice_content' => \Illuminate\Support\Str::limit(strip_tags($notice->content), 160),
                                    'notice_url'     => $noticeUrl ?? '',
                                    'student_name'   => $student->first_name . ' ' . $student->last_name,
                                    'school_name'    => config('app.name', 'School'),
                                ],
                                eventTrigger: 'notice.published',
                            );
                        }
                    }
                }

                $total++;
            } catch (\Throwable $e) {
                Log::error("NotifyNoticePublished: Failed for user {$user->id}: {$e->getMessage()}");
            }
        }

        Log::info("NotifyNoticePublished: Sent {$total} notification(s) for notice '{$notice->title}'.");
    }
}
