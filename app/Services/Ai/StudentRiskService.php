<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\BehaviorIncident;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;

/**
 * Composite "at-risk student" detection. Combines four rule-based signals
 * — attendance, recent marks, fee status, behaviour — into a 0..100 risk
 * score (higher = more at risk) with a coarse band and the contributing
 * factors. The scoring is deterministic (no AI / no cost); the optional
 * explain() method adds a short AI narrative + suggestions via AiInsights.
 *
 * Designed to back dashboard widgets and automated alerts. Feed it a
 * Student; it reads only that student's permitted records.
 */
class StudentRiskService
{
    /** Lookback window (days) for attendance + behaviour. */
    public function __construct(
        private readonly int $windowDays = 60,
    ) {}

    /**
     * @return array{score:int,band:string,signals:array<string,mixed>,factors:list<string>}
     */
    public function forStudent(Student $student): array
    {
        $signals = $this->gatherSignals($student);

        return $this->score($signals);
    }

    /**
     * Gather raw metrics for a student.
     *
     * @return array<string,mixed>
     */
    public function gatherSignals(Student $student): array
    {
        $since = now()->subDays($this->windowDays)->toDateString();

        // ── Attendance ──
        $attTotal = AttendanceRecord::where('student_id', $student->id)
            ->where('date', '>=', $since)
            ->whereIn('status', [
                AttendanceStatus::Present->value,
                AttendanceStatus::Absent->value,
                AttendanceStatus::Late->value,
                AttendanceStatus::HalfDay->value,
                AttendanceStatus::Excused->value,
            ])
            ->count();
        $attAbsent = AttendanceRecord::where('student_id', $student->id)
            ->where('date', '>=', $since)
            ->where('status', AttendanceStatus::Absent->value)
            ->count();
        $attLate = AttendanceRecord::where('student_id', $student->id)
            ->where('date', '>=', $since)
            ->where('status', AttendanceStatus::Late->value)
            ->count();
        $absentRate = $attTotal > 0 ? $attAbsent / $attTotal : null;

        // ── Fees (compute balance from paisas; status strings vary) ──
        $outstandingPaisas = 0;
        $overdueCount = 0;
        $today = now()->toDateString();
        StudentFee::where('student_id', $student->id)
            ->get(['due_date', 'amount_paisas', 'discount_paisas', 'fine_paisas', 'paid_paisas'])
            ->each(function (StudentFee $f) use (&$outstandingPaisas, &$overdueCount, $today): void {
                $balance = (int) $f->amount_paisas + (int) $f->fine_paisas
                    - (int) $f->discount_paisas - (int) $f->paid_paisas;
                if ($balance > 0) {
                    $outstandingPaisas += $balance;
                    if ($f->due_date && $f->due_date->toDateString() < $today) {
                        $overdueCount++;
                    }
                }
            });

        // ── Behaviour ──
        $incidents = BehaviorIncident::where('student_id', $student->id)
            ->where('incident_date', '>=', $since)
            ->get(['severity', 'points']);
        $negativePoints = 0;
        $majorCount = 0;
        foreach ($incidents as $i) {
            $pts = (int) ($i->points ?? 0);
            if ($pts < 0) {
                $negativePoints += abs($pts);
            }
            if (in_array(strtolower((string) $i->severity), ['major', 'critical', 'severe'], true)) {
                $majorCount++;
            }
        }

        // ── Recent marks (avg %) ──
        $marks = ExamMark::where('student_id', $student->id)
            ->where('is_absent', false)
            ->whereNotNull('marks_obtained')
            ->with('schedule:id,full_marks')
            ->latest()
            ->limit(20)
            ->get();
        $pcts = [];
        foreach ($marks as $m) {
            $full = (float) ($m->schedule->full_marks ?? 0);
            if ($full > 0) {
                $pcts[] = ((float) $m->marks_obtained / $full) * 100;
            }
        }
        $avgPct = $pcts !== [] ? array_sum($pcts) / count($pcts) : null;

        return [
            'window_days'        => $this->windowDays,
            'attendance_total'   => $attTotal,
            'attendance_absent'  => $attAbsent,
            'attendance_late'    => $attLate,
            'absent_rate'        => $absentRate,
            'outstanding_paisas' => $outstandingPaisas,
            'overdue_fees'       => $overdueCount,
            'behaviour_negative_points' => $negativePoints,
            'behaviour_major_incidents' => $majorCount,
            'avg_marks_percent'  => $avgPct === null ? null : round($avgPct, 1),
        ];
    }

    /**
     * Turn raw signals into a weighted 0..100 risk score.
     *
     * @param  array<string,mixed>  $s
     * @return array{score:int,band:string,signals:array<string,mixed>,factors:list<string>}
     */
    public function score(array $s): array
    {
        $factors = [];
        $parts = []; // [weight, riskValue]

        // Attendance (weight 35)
        if ($s['absent_rate'] !== null) {
            $risk = min(100.0, ($s['absent_rate'] / 0.25) * 100);
            $parts[] = [35, $risk];
            if ($s['absent_rate'] >= 0.1) {
                $factors[] = sprintf('%.0f%% absence over last %d days (%d/%d)',
                    $s['absent_rate'] * 100, $s['window_days'], $s['attendance_absent'], $s['attendance_total']);
            }
        }

        // Marks (weight 30)
        if ($s['avg_marks_percent'] !== null) {
            $avg = (float) $s['avg_marks_percent'];
            $risk = max(0.0, min(100.0, (70 - $avg) / (70 - 33) * 100));
            $parts[] = [30, $risk];
            if ($avg < 60) {
                $factors[] = sprintf('Low recent average: %.0f%%', $avg);
            }
        }

        // Fees (weight 15)
        $feeRisk = $s['overdue_fees'] > 0 ? 60.0 : ($s['outstanding_paisas'] > 0 ? 20.0 : 0.0);
        $parts[] = [15, $feeRisk];
        if ($s['overdue_fees'] > 0) {
            $factors[] = sprintf('%d overdue fee item(s), %s PKR outstanding',
                $s['overdue_fees'], number_format($s['outstanding_paisas'] / 100));
        }

        // Behaviour (weight 20)
        $behRisk = min(100.0, $s['behaviour_major_incidents'] * 40 + min(60, $s['behaviour_negative_points'] * 2));
        $parts[] = [20, $behRisk];
        if ($s['behaviour_major_incidents'] > 0 || $s['behaviour_negative_points'] > 0) {
            $factors[] = sprintf('%d major incident(s), %d negative behaviour points',
                $s['behaviour_major_incidents'], $s['behaviour_negative_points']);
        }

        // Weighted average over signals we actually have.
        $totalWeight = array_sum(array_column($parts, 0));
        $weighted = 0.0;
        foreach ($parts as [$w, $r]) {
            $weighted += $w * $r;
        }
        $score = $totalWeight > 0 ? (int) round($weighted / $totalWeight) : 0;

        $band = $score >= 67 ? 'high' : ($score >= 34 ? 'medium' : 'low');

        if ($factors === []) {
            $factors[] = 'No significant risk signals.';
        }

        return ['score' => $score, 'band' => $band, 'signals' => $s, 'factors' => $factors];
    }

    /**
     * Optional AI narrative + suggested interventions for a student's risk
     * profile. Costs an AI call — only invoke when AI is enabled. Falls
     * back to the rule-based factor list on failure.
     */
    public function explain(Student $student): string
    {
        $result = $this->forStudent($student);

        if (! AiAvailability::enabled()) {
            return "Risk: {$result['band']} ({$result['score']}/100). " . implode('; ', $result['factors']);
        }

        try {
            return app(AiInsights::class)->summarize(
                data: $result,
                instruction: 'Summarise this student\'s at-risk profile in 2-3 sentences for a teacher, '
                    . 'then give up to 3 concrete, supportive next steps. Be specific to the signals; do not invent data.',
                feature: 'student_risk_explain',
                options: ['audience' => 'a class teacher', 'length' => 'a short paragraph then a 3-item list'],
            );
        } catch (\Throwable $e) {
            return "Risk: {$result['band']} ({$result['score']}/100). " . implode('; ', $result['factors']);
        }
    }
}
