<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * AttendanceMode — How an attendance record was captured.
 *
 * Used on both student and staff attendance records to distinguish
 * between biometric sync, teacher manual roll-call, and fallback modes.
 */
enum AttendanceMode: string
{
    /** Teacher manually marked attendance for their class/period (always available). */
    case Manual   = 'manual';

    /** Synced from a physical biometric / RFID device. */
    case Biometric = 'biometric';

    /**
     * Teacher used manual roll-call because biometric device was offline
     * or not configured. System auto-enables this when biometric fails.
     */
    case ManualFallback = 'manual_fallback';

    /** Staff attendance captured via biometric. */
    case StaffBiometric = 'staff_biometric';

    /** Staff attendance captured manually by HR. */
    case StaffManual = 'staff_manual';

    /** Marked by Attendance Clerk (biometric or manual). */
    case Clerk = 'clerk';

    // ── Helpers ────────────────────────────────────────────────────

    public function label(): string
    {
        return match ($this) {
            self::Manual         => 'Manual (Teacher)',
            self::Biometric      => 'Biometric',
            self::ManualFallback => 'Manual Fallback (Biometric Offline)',
            self::StaffBiometric => 'Staff Biometric',
            self::StaffManual    => 'Staff Manual',
            self::Clerk          => 'Attendance Clerk',
        };
    }

    /** Returns true if this mode was captured manually (not via device). */
    public function isManual(): bool
    {
        return in_array($this, [self::Manual, self::ManualFallback, self::StaffManual], strict: true);
    }

    /** Returns true if the biometric device was the primary input. */
    public function isBiometric(): bool
    {
        return in_array($this, [self::Biometric, self::StaffBiometric], strict: true);
    }
}
