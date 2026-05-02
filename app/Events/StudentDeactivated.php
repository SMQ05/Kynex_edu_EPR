<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StudentDeactivated — Fired when a student's status leaves 'enrolled'.
 *
 * Triggers active student count decrement in the central DB.
 */
class StudentDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly StudentStatus $previousStatus,
    ) {}
}
