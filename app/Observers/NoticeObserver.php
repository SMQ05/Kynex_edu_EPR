<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\NotifyNoticePublished;
use App\Models\Tenant\Notice;

/**
 * NoticeObserver — Dispatches notifications when a notice is published.
 *
 * Phase 9.6 — Auto-notify target roles when is_published transitions from false to true.
 * Does NOT re-dispatch if other fields change on an already-published notice.
 */
class NoticeObserver
{
    public function updated(Notice $notice): void
    {
        if ($notice->wasChanged('is_published') && $notice->is_published === true) {
            NotifyNoticePublished::dispatch($notice);
        }
    }
}
