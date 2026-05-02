<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentStatus: string
{
    case PendingAdmission = 'pending_admission';
    case Applicant        = 'applicant';
    case EntryTestPending = 'entry_test_pending';
    case Enrolled         = 'enrolled';
    case Left             = 'left';
    case Graduated        = 'graduated';
    case Expelled         = 'expelled';
    case Suspended        = 'suspended';
    case Rejected         = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingAdmission => 'Pending Admission',
            self::Applicant        => 'Applicant',
            self::EntryTestPending => 'Entry Test Pending',
            self::Enrolled         => 'Enrolled',
            self::Left             => 'Left',
            self::Graduated        => 'Graduated',
            self::Expelled         => 'Expelled',
            self::Suspended        => 'Suspended',
            self::Rejected         => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingAdmission => 'warning',
            self::Applicant        => 'gray',
            self::EntryTestPending => 'info',
            self::Enrolled         => 'success',
            self::Graduated        => 'info',
            self::Left             => 'gray',
            self::Expelled         => 'danger',
            self::Suspended        => 'warning',
            self::Rejected         => 'danger',
        };
    }
}
