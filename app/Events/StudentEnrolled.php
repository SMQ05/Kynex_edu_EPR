<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StudentEnrolled — Fired when a student's status transitions to 'enrolled'.
 *
 * Use this event to trigger billing counter updates, welcome notifications,
 * and enrollment audits.
 */
class StudentEnrolled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly ?StudentStatus $previousStatus = null,
    ) {}
}
