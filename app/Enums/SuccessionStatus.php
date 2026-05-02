<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * SuccessionStatus — Lifecycle states of a role succession request.
 */
enum SuccessionStatus: string
{
    /** Succession request created; waiting for Institute Head (or SaaS Admin) approval. */
    case Pending   = 'pending';

    /** Approved; system is in the process of transferring records. */
    case Approved  = 'approved';

    /** All records transferred to incoming user; succession complete. */
    case Completed = 'completed';

    /** Cancelled before completion (admin withdrew or incoming user declined). */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending Approval',
            self::Approved  => 'Approved — Transferring',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'warning',
            self::Approved  => 'info',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
