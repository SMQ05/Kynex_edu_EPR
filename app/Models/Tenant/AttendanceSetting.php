<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttendanceSetting — Per-campus (or per-class/section, or school-wide) attendance configuration.
 *
 * Priority: class+section specific > campus-level > school-wide default (campus_id NULL).
 *
 * @property string      $id
 * @property string|null $campus_id
 * @property string|null $class_id
 * @property string|null $section_id
 * @property string      $school_start_time    (H:i:s)
 * @property string      $school_end_time      (H:i:s)
 * @property string      $late_arrival_cutoff  (H:i:s)
 * @property int         $grace_period_minutes
 * @property bool        $notify_on_late_arrival
 * @property string|null $half_day_cutoff      (H:i:s)
 * @property string|null $early_departure_cutoff (H:i:s)
 */
class AttendanceSetting extends Model
{
    use HasUlids;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'campus_id',
        'class_id',
        'section_id',
        'school_start_time',
        'school_end_time',
        'late_arrival_cutoff',
        'grace_period_minutes',
        'notify_on_late_arrival',
        'half_day_cutoff',
        'early_departure_cutoff',
    ];

    protected function casts(): array
    {
        return [
            'notify_on_late_arrival' => 'boolean',
            'grace_period_minutes'   => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Get the effective settings for a given campus, with optional class/section override.
     *
     * Priority: class+section specific > campus-level > school-wide default.
     */
    public static function forCampus(?string $campusId, ?string $classId = null, ?string $sectionId = null): static
    {
        // 1. Try class+section specific setting
        if ($classId && $sectionId) {
            $settings = static::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->first();
            if ($settings) {
                return $settings;
            }
        }

        // 2. Try class-level setting (any section)
        if ($classId) {
            $settings = static::where('class_id', $classId)
                ->whereNull('section_id')
                ->first();
            if ($settings) {
                return $settings;
            }
        }

        // 3. Try campus-level setting
        if ($campusId) {
            $settings = static::where('campus_id', $campusId)
                ->whereNull('class_id')
                ->whereNull('section_id')
                ->first();
            if ($settings) {
                return $settings;
            }
        }

        // 4. Return school-wide default or create one
        return static::firstOrCreate(
            ['campus_id' => null, 'class_id' => null, 'section_id' => null],
            [
                'school_start_time'      => '07:30:00',
                'school_end_time'        => '14:00:00',
                'late_arrival_cutoff'    => '08:00:00',
                'grace_period_minutes'   => 0,
                'notify_on_late_arrival' => true,
            ]
        );
    }

    /**
     * Check if a given time string (H:i:s) represents a late arrival.
     */
    public function isLateArrival(string $timeStr): bool
    {
        $arrival = strtotime($timeStr);
        $cutoff  = strtotime($this->late_arrival_cutoff);

        if ($this->grace_period_minutes > 0) {
            $cutoff += $this->grace_period_minutes * 60;
        }

        return $arrival > $cutoff;
    }
}
