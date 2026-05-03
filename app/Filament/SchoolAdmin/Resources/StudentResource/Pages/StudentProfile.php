<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentResource;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\BehaviorIncident;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\GeneratedCertificate;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\Student;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Student 360 — read-only consolidated profile combining demographics,
 * academic record, behavior, attendance, and issued certificates. Visible
 * to anyone with view_students permission; teachers see only students in
 * the classes they're assigned to (enforced at query level via record
 * resolution + policy on the parent resource).
 */
class StudentProfile extends Page
{
    protected static string $resource = StudentResource::class;

    protected string $view = 'filament.school-admin.resources.student-resource.pages.student-profile';

    protected static ?string $title = 'Student Profile';

    public Student $record;

    /**
     * Custom resource pages need their own access gate. Without this, Filament
     * falls through to Gate::check('view', $record) — which returns false when
     * no StudentPolicy exists, producing a misleading 404. Delegate to the
     * resource's read-permission check (view_students), same gate as the list.
     */
    public static function canAccess(array $parameters = []): bool
    {
        return StudentResource::canViewAny();
    }

    /**
     * Filament v4 route-binds {record} to a Student model automatically. Accept
     * the bound model and just hydrate the extra relations the view needs;
     * never re-cast the model to a string and re-find it (that path stringifies
     * the model to JSON and produces a misleading 404).
     */
    public function mount(Student|string|int $record): void
    {
        $student = $record instanceof Student
            ? $record
            : Student::query()->findOrFail($record);

        $this->record = $student->load([
            'schoolClass',
            'section',
            'campus',
            'category',
            'academicYear',
            'guardians',
            'schoolUser',
        ]);
    }

    public function getTitle(): string
    {
        return $this->record->full_name . ' — Profile';
    }

    public function getBreadcrumb(): string
    {
        return 'Profile';
    }

    // ── Aggregates ─────────────────────────────────────────────────

    #[Computed]
    public function examMarks(): Collection
    {
        return ExamMark::query()
            ->where('student_id', $this->record->id)
            ->with(['schedule.exam', 'schedule.subject'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function homeworkMarks(): Collection
    {
        return HomeworkSubmission::query()
            ->where('student_id', $this->record->id)
            ->whereNotNull('marks_obtained')
            ->with(['homework.subject'])
            ->orderByDesc('graded_at')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function examResults(): Collection
    {
        return ExamResult::query()
            ->where('student_id', $this->record->id)
            ->with(['exam'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function behaviorIncidents(): Collection
    {
        return BehaviorIncident::query()
            ->where('student_id', $this->record->id)
            ->orderByDesc('incident_date')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function attendanceSummary(): array
    {
        $rows = AttendanceRecord::query()
            ->where('student_id', $this->record->id)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $total = array_sum($rows);
        $present = (int) ($rows['present'] ?? 0);
        $absent = (int) ($rows['absent'] ?? 0);
        $late = (int) ($rows['late'] ?? 0);
        $excused = (int) ($rows['excused'] ?? 0);

        return [
            'total'     => $total,
            'present'   => $present,
            'absent'    => $absent,
            'late'      => $late,
            'excused'   => $excused,
            'percent'   => $total > 0 ? round(($present + $excused) * 100 / $total, 1) : null,
        ];
    }

    #[Computed]
    public function certificates(): Collection
    {
        return GeneratedCertificate::query()
            ->where('student_id', $this->record->id)
            ->with('template')
            ->orderByDesc('issued_date')
            ->get();
    }

    #[Computed]
    public function performanceByExam(): array
    {
        $marks = $this->examMarks;

        $byExam = [];
        foreach ($marks as $mark) {
            $exam = $mark->schedule?->exam;
            if (! $exam) {
                continue;
            }
            $key = $exam->id;
            if (! isset($byExam[$key])) {
                $byExam[$key] = [
                    'name'     => $exam->name,
                    'subjects' => [],
                    'obtained' => 0,
                    'total'    => 0,
                    'absent'   => 0,
                ];
            }
            $byExam[$key]['subjects'][] = [
                'subject'  => $mark->schedule->subject?->name ?? '—',
                'obtained' => $mark->is_absent ? null : (float) ($mark->marks_obtained ?? 0),
                'full'     => (int) ($mark->schedule->full_marks ?? 0),
                'is_absent' => (bool) $mark->is_absent,
            ];
            if (! $mark->is_absent && $mark->marks_obtained !== null) {
                $byExam[$key]['obtained'] += (float) $mark->marks_obtained;
                $byExam[$key]['total'] += (int) ($mark->schedule->full_marks ?? 0);
            }
            if ($mark->is_absent) {
                $byExam[$key]['absent']++;
            }
        }

        foreach ($byExam as &$exam) {
            $exam['percentage'] = $exam['total'] > 0
                ? round($exam['obtained'] * 100 / $exam['total'], 2)
                : null;
        }

        return array_values($byExam);
    }
}
