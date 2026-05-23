<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted           = 'submitted';
    case EntryTestScheduled  = 'entry_test_scheduled';
    case EntryTestTaken      = 'entry_test_taken';
    case InterviewScheduled  = 'interview_scheduled';
    case InterviewTaken      = 'interview_taken';
    case PendingApproval     = 'pending_approval';
    case Admitted            = 'admitted';
    case Rejected            = 'rejected';
    case Waitlisted          = 'waitlisted';

    public function label(): string
    {
        return match ($this) {
            self::Submitted          => 'Submitted',
            self::EntryTestScheduled => 'Entry Test Scheduled',
            self::EntryTestTaken     => 'Entry Test Taken',
            self::InterviewScheduled => 'Interview Scheduled',
            self::InterviewTaken     => 'Interview Taken',
            self::PendingApproval    => 'Pending Approval',
            self::Admitted           => 'Admitted',
            self::Rejected           => 'Rejected',
            self::Waitlisted         => 'Waitlisted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted          => 'info',
            self::EntryTestScheduled => 'primary',
            self::EntryTestTaken     => 'warning',
            self::InterviewScheduled => 'primary',
            self::InterviewTaken     => 'warning',
            self::PendingApproval    => 'warning',
            self::Admitted           => 'success',
            self::Rejected           => 'danger',
            self::Waitlisted         => 'gray',
        };
    }
}
