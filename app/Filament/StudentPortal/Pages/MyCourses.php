<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamQuestion;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\LectureFlashcard;
use App\Models\Tenant\LectureQuizAttempt;
use App\Models\Tenant\StudyMaterial;
use App\Models\Tenant\Syllabus;
use App\Models\Tenant\SyllabusTopic;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * The student's course map: where each subject is up to, and where they are.
 *
 * The rest of the portal is organised by artefact — a list of lectures, a list
 * of assignments, a list of results — which tells a student what exists but
 * never what to do next. This page is organised by the course itself: the
 * school's published units in order, what has been taught, what is running
 * now, and which unit each recording and practice quiz belongs to.
 *
 * The standing figures are deliberately computed from work the student has
 * actually done — marked homework, sat exams, practice attempts — and a
 * subject with too little evidence says so rather than showing a confident
 * number derived from one mark.
 */
class MyCourses extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'My Courses';

    protected string $view = 'filament.student-portal.pages.my-courses';

    /** Subject whose unit list is expanded, kept in the URL so it is linkable. */
    #[Url(as: 'subject')]
    public ?string $openSubject = null;

    /** Below this many marked pieces of work, a standing is not worth showing. */
    private const MIN_EVIDENCE = 2;

    public function getHeading(): string
    {
        return 'My Courses';
    }

    public function getSubheading(): ?string
    {
        $student = $this->student();

        if (! $student) {
            return null;
        }

        $courses = $this->courses;

        return $courses->isEmpty()
            ? 'No published course plans yet for ' . ($student->schoolClass?->name ?? 'your class')
            : $courses->count() . ' courses in ' . ($student->schoolClass?->name ?? 'your class');
    }

    public function toggleSubject(string $syllabusId): void
    {
        $this->openSubject = $this->openSubject === $syllabusId ? null : $syllabusId;
    }

    /**
     * Every published course for the student's class, with its units, the
     * material attached to each, and the student's own standing in it.
     */
    #[Computed]
    public function courses(): Collection
    {
        $classId = $this->studentClassId();

        if (! $classId) {
            return collect();
        }

        $syllabi = Syllabus::query()
            ->where('class_id', $classId)
            ->where('status', 'published')
            ->with(['subject', 'teacher'])
            ->get();

        if ($syllabi->isEmpty()) {
            return collect();
        }

        $topics = SyllabusTopic::query()
            ->whereIn('syllabus_id', $syllabi->pluck('id'))
            ->orderBy('sort_order')
            ->get()
            ->groupBy('syllabus_id');

        $lectures = StudyMaterial::query()
            ->where('class_id', $classId)
            ->where('is_published', true)
            ->whereNotNull('syllabus_topic_id')
            ->get()
            ->groupBy('syllabus_topic_id');

        $lectureIds = $lectures->flatten()->pluck('id');

        $quizCounts = ExamQuestion::query()
            ->whereIn('study_material_id', $lectureIds)
            ->where('is_active', true)
            ->selectRaw('study_material_id, count(*) as c')
            ->groupBy('study_material_id')
            ->pluck('c', 'study_material_id');

        $cardCounts = LectureFlashcard::query()
            ->whereIn('study_material_id', $lectureIds)
            ->where('is_active', true)
            ->selectRaw('study_material_id, count(*) as c')
            ->groupBy('study_material_id')
            ->pluck('c', 'study_material_id');

        $bestAttempts = LectureQuizAttempt::query()
            ->where('student_id', $this->studentId())
            ->whereIn('study_material_id', $lectureIds)
            ->get()
            ->groupBy('study_material_id')
            ->map(fn (Collection $rows) => $rows->sortByDesc(fn ($a) => $a->total > 0 ? $a->score / $a->total : 0)->first());

        $standings = $this->standings();

        return $syllabi
            ->map(function (Syllabus $syllabus) use ($topics, $lectures, $quizCounts, $cardCounts, $bestAttempts, $standings) {
                $rows = $topics->get($syllabus->id) ?? collect();
                $total = $rows->count();
                $done = $rows->where('status', 'completed')->count();

                $units = $rows->map(function (SyllabusTopic $topic) use ($lectures, $quizCounts, $cardCounts, $bestAttempts) {
                    $material = ($lectures->get($topic->id) ?? collect())->map(fn (StudyMaterial $m) => [
                        'id' => $m->id,
                        'title' => $m->title,
                        'questions' => (int) ($quizCounts[$m->id] ?? 0),
                        'cards' => (int) ($cardCounts[$m->id] ?? 0),
                        'best' => $bestAttempts->get($m->id)?->percentage,
                    ])->all();

                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'description' => $topic->description,
                        'week' => $topic->week_number,
                        'status' => (string) ($topic->status?->value ?? $topic->status),
                        'material' => $material,
                    ];
                })->all();

                $subjectName = $syllabus->subject?->name ?? $syllabus->title;

                return [
                    'id' => $syllabus->id,
                    'subject' => $subjectName,
                    'title' => $syllabus->title,
                    'teacher' => $syllabus->teacher?->name,
                    'done' => $done,
                    'total' => $total,
                    'pct' => $total > 0 ? (int) round($done / $total * 100) : 0,
                    'current' => collect($units)->firstWhere('status', 'in_progress'),
                    'units' => $units,
                    'standing' => $standings[$subjectName] ?? null,
                ];
            })
            ->sortBy('subject')
            ->values();
    }

    /**
     * The student's average per subject, across every marked piece of work.
     *
     * Homework, exams and practice are pooled rather than reported separately
     * because a single number per subject is what makes "where am I weakest"
     * answerable at a glance. Practice is included but weighted lowest: it is
     * retryable, so a perfect practice score says less than a sat exam.
     *
     * @return array<string, array{pct:int, pieces:int, parts:array<string,int>}>
     */
    protected function standings(): array
    {
        $studentId = $this->studentId();
        $byExam = [];

        ExamMark::query()
            ->where('student_id', $studentId)
            ->with(['schedule.subject'])
            ->get()
            ->each(function (ExamMark $mark) use (&$byExam) {
                $subject = $mark->schedule?->subject?->name;
                $outOf = (float) ($mark->schedule?->full_marks ?? 0);

                if (! $subject || $outOf <= 0 || $mark->marks_obtained === null) {
                    return;
                }

                $byExam[$subject][] = (float) $mark->marks_obtained / $outOf * 100;
            });

        $byHomework = [];

        HomeworkSubmission::query()
            ->where('student_id', $studentId)
            ->whereNotNull('marks_obtained')
            ->with(['homework.subject'])
            ->get()
            ->each(function (HomeworkSubmission $row) use (&$byHomework) {
                $subject = $row->homework?->subject?->name;
                $outOf = (float) ($row->total_marks ?: $row->homework?->total_marks ?: 0);

                if (! $subject || $outOf <= 0) {
                    return;
                }

                $byHomework[$subject][] = (float) $row->marks_obtained / $outOf * 100;
            });

        $byPractice = [];

        LectureQuizAttempt::query()
            ->where('student_id', $studentId)
            ->with(['lecture.subject'])
            ->get()
            ->each(function (LectureQuizAttempt $attempt) use (&$byPractice) {
                $subject = $attempt->lecture?->subject?->name;

                if (! $subject || $attempt->total <= 0) {
                    return;
                }

                $byPractice[$subject][] = $attempt->score / $attempt->total * 100;
            });

        $subjects = array_unique(array_merge(
            array_keys($byExam),
            array_keys($byHomework),
            array_keys($byPractice),
        ));

        $out = [];

        foreach ($subjects as $subject) {
            $exam = $byExam[$subject] ?? [];
            $homework = $byHomework[$subject] ?? [];
            $practice = $byPractice[$subject] ?? [];
            $pieces = count($exam) + count($homework) + count($practice);

            if ($pieces < self::MIN_EVIDENCE) {
                continue;
            }

            $weighted = 0.0;
            $weight = 0.0;

            foreach ([[$exam, 1.0], [$homework, 0.8], [$practice, 0.4]] as [$set, $w]) {
                if ($set === []) {
                    continue;
                }

                $weighted += array_sum($set) / count($set) * $w;
                $weight += $w;
            }

            $out[$subject] = [
                'pct' => (int) round($weighted / $weight),
                'pieces' => $pieces,
                'parts' => [
                    'exams' => count($exam),
                    'homework' => count($homework),
                    'practice' => count($practice),
                ],
            ];
        }

        return $out;
    }

    /**
     * The subjects worth putting time into next.
     *
     * Ranked by standing, but only among subjects with enough marked work to
     * justify singling them out — telling a student their weakest subject on
     * the strength of one bad homework would be worse than saying nothing.
     *
     * @return list<array{subject:string, pct:int, pieces:int}>
     */
    #[Computed]
    public function focus(): array
    {
        $standings = $this->standings();

        $ranked = collect($standings)
            ->map(fn (array $row, string $subject) => [
                'subject' => $subject,
                'pct' => $row['pct'],
                'pieces' => $row['pieces'],
            ])
            ->sortBy('pct')
            ->values();

        return $ranked->take(3)->all();
    }

    /** Headline counts across the whole course map. */
    #[Computed]
    public function overview(): array
    {
        $courses = $this->courses;

        $units = $courses->sum('total');
        $taught = $courses->sum('done');

        $material = $courses->sum(fn (array $c) => collect($c['units'])->sum(fn (array $u) => count($u['material'])));

        $practised = LectureQuizAttempt::where('student_id', $this->studentId())
            ->distinct()
            ->count('study_material_id');

        return [
            'courses' => $courses->count(),
            'units' => $units,
            'taught' => $taught,
            'pct' => $units > 0 ? (int) round($taught / $units * 100) : 0,
            'material' => $material,
            'practised' => $practised,
        ];
    }
}
