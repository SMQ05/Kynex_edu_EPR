<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\InAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * CustomDomainVerified — Phase 15C.5
 *
 * Notifies SCHOOL_ADMIN users that a custom domain
 * has been verified and is now active.
 *
 * Uses the project's InAppNotification model directly
 * rather than Laravel's database channel, to stay consistent
 * with the existing notification system.
 */
class CustomDomainVerified extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $domain,
    ) {}

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Store as an InAppNotification record (project convention).
     *
     * We override `toDatabase` for the standard channel, but also
     * manually create an InAppNotification for the bell UI.
     */
    public function toDatabase(object $notifiable): array
    {
        // Create the InAppNotification for the bell icon
        InAppNotification::create([
            'user_id'    => $notifiable->getKey(),
            'title'      => 'Custom domain verified!',
            'body'       => "{$this->domain} is now active and serving your school.",
            'type'       => 'domain_verified',
            'action_url' => null,
        ]);

        return [
            'title'  => 'Custom domain verified!',
            'body'   => "{$this->domain} is now active and serving your school.",
            'domain' => $this->domain,
        ];
    }
}
