<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\AiConversation;
use App\Models\Tenant\AiMessage;
use App\Models\Tenant\ExamQuestion;
use App\Models\Tenant\LectureFlashcard;
use App\Models\Tenant\LectureQuizAttempt;
use App\Models\Tenant\StudyMaterial;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\AiAvailability;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Lecture library plus a per-lecture AI tutor.
 *
 * The tutor is deliberately grounded: the system prompt carries the lecture's
 * own notes and instructs the model to say when something is not covered
 * rather than improvise. An education demo where the AI confidently invents
 * material would be worse than one with no AI at all.
 */
class MyLectures extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-play-circle';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'My Lectures';

    protected string $view = 'filament.student-portal.pages.my-lectures';

    /** Currently open lecture, kept in the URL so links are shareable. */
    #[Url(as: 'lecture')]
    public ?string $lectureId = null;

    /** The question box. */
    public string $question = '';

    // ── Practice quiz state ─────────────────────────────────────────
    /** questionId => the option the student picked */
    public array $answers = [];
    public bool $quizChecked = false;

    public bool $thinking = false;

    public function getHeading(): string
    {
        return 'My Lectures';
    }

    public function getSubheading(): ?string
    {
        $student = $this->student();

        return $student
            ? 'Published material for ' . ($student->schoolClass?->name ?? 'your class')
            : null;
    }

    /** Every published lecture for this student's class, newest first. */
    #[Computed]
    public function lectures(): Collection
    {
        return StudyMaterial::query()
            ->with(['subject', 'teacher'])
            ->where('is_published', true)
            ->where('class_id', $this->studentClassId())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * The open lecture, or the newest one if none is selected.
     *
     * Re-queries by id AND class so a hand-edited ?lecture= cannot open
     * material belonging to another class.
     */
    #[Computed]
    public function lecture(): ?StudyMaterial
    {
        if ($this->lectureId !== null) {
            return StudyMaterial::query()
                ->with(['subject', 'teacher'])
                ->where('is_published', true)
                ->where('class_id', $this->studentClassId())
                ->find($this->lectureId);
        }

        return $this->lectures->first();
    }

    /** Turn a YouTube watch/short URL into an embeddable one. */
    #[Computed]
    public function embedUrl(): ?string
    {
        $url = $this->lecture?->external_url;
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
        }

        return null;
    }

    public function selectLecture(string $id): void
    {
        $this->lectureId = $id;
        $this->question = '';
        $this->resetQuiz();
        unset($this->lecture, $this->embedUrl, $this->messages,
              $this->practiceQuestions, $this->flashcards, $this->bestAttempt);
    }

    /** Persisted chat for this student + lecture pair. */
    #[Computed]
    public function messages(): Collection
    {
        $conversation = $this->conversation(create: false);

        return $conversation
            ? AiMessage::where('conversation_id', $conversation->id)->orderBy('created_at')->get()
            : collect();
    }

    public function aiAvailable(): bool
    {
        return AiAvailability::enabled();
    }

    public function aiUnavailableReason(): ?string
    {
        return AiAvailability::reason();
    }

    /**
     * Ask the tutor about the open lecture.
     *
     * Failures surface as a notification and are not persisted, so a budget
     * or network error cannot leave a half-written exchange in the history.
     */
    public function ask(): void
    {
        $question = trim($this->question);
        $lecture = $this->lecture;

        if ($question === '' || ! $lecture) {
            return;
        }

        if (! AiAvailability::enabled()) {
            Notification::make()
                ->title('AI is unavailable')
                ->body(AiAvailability::reason() ?? 'Try again later.')
                ->warning()
                ->send();

            return;
        }

        $this->thinking = true;

        try {
            $conversation = $this->conversation(create: true);

            $history = AiMessage::where('conversation_id', $conversation->id)
                ->orderBy('created_at')
                ->get()
                ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $history[] = ['role' => 'user', 'content' => $question];

            $answer = AiAssistant::forCurrentTenant()->chat(
                $this->systemPromptFor($lecture),
                $history,
                feature: 'student_lecture_tutor',
            );

            // AiMessage sets $timestamps = false and lists created_at as
            // fillable, so it must be passed explicitly — otherwise the
            // column stays null and the ordered history collapses.
            $now = now();

            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $question,
                'created_at' => $now,
            ]);

            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $answer,
                'created_at' => $now->copy()->addMillisecond(),
            ]);

            $this->question = '';
            unset($this->messages);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('The tutor could not answer just now')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->thinking = false;
        }
    }

    /**
     * Ground the tutor in this lecture only.
     *
     * The notes come from the lecture's own description, which the school
     * writes. Instructing the model to defer when the notes do not cover a
     * question is what keeps answers trustworthy in front of a class.
     */
    protected function systemPromptFor(StudyMaterial $lecture): string
    {
        $student = $this->student();
        $grade = $student?->schoolClass?->name ?? 'this grade';
        $subject = $lecture->subject?->name ?? 'this subject';
        $notes = trim((string) $lecture->description);

        return <<<PROMPT
        You are a patient subject tutor at a K-12 school, helping a {$grade}
        student understand one specific lecture.

        Lecture title: {$lecture->title}
        Subject: {$subject}

        Lesson notes for this lecture:
        ---
        {$notes}
        ---

        How to answer:
        - Explain at a level appropriate for {$grade}. Short paragraphs, plain words.
        - Base your answer on the lesson notes above. You may add ordinary
          background knowledge of {$subject} to clarify, but do not invent
          specifics (figures, dates, formulas, names) that are not in the notes.
        - If the notes do not cover what was asked, say so plainly and suggest
          the student ask their teacher, rather than guessing.
        - Never do the student's graded work for them. If asked to answer an
          assignment or exam question outright, explain the underlying method
          instead and let them apply it.
        - Keep answers under about 200 words unless asked to go deeper.
        PROMPT;
    }

    /** Find (or start) the conversation for this student + lecture. */
    protected function conversation(bool $create): ?AiConversation
    {
        $user = auth()->guard('school_users')->user();
        $lecture = $this->lecture;

        if (! $user || ! $lecture) {
            return null;
        }

        // One thread per lecture, titled so it is identifiable in any
        // admin-side AI history view.
        $title = 'Lecture: ' . Str::limit($lecture->title, 60);

        $existing = AiConversation::where('school_user_id', $user->id)
            ->where('title', $title)
            ->first();

        if ($existing || ! $create) {
            return $existing;
        }

        return AiConversation::create([
            'id' => (string) Str::ulid(),
            'school_user_id' => $user->id,
            'role_when_created' => 'STUDENT',
            'title' => $title,
        ]);
    }

    // ── Practice quiz ───────────────────────────────────────────────

    /** Self-marking practice questions for the open lecture. */
    #[Computed]
    public function practiceQuestions(): Collection
    {
        $lecture = $this->lecture;

        return $lecture
            ? ExamQuestion::where('study_material_id', $lecture->id)
                ->where('is_active', true)
                ->orderBy('created_at')
                ->get()
            : collect();
    }

    /**
     * Mark the quiz.
     *
     * Deliberately self-marking with unlimited retries and no effect on any
     * grade. The moment a practice score counts for something, students stop
     * using it to find out what they do not know.
     */
    public function checkQuiz(): void
    {
        if ($this->practiceQuestions->isEmpty()) {
            return;
        }

        $this->quizChecked = true;

        $score = $this->quizScore();
        $lecture = $this->lecture;

        if ($lecture && $this->studentId() !== '__no_student__') {
            LectureQuizAttempt::create([
                'study_material_id' => $lecture->id,
                'student_id' => $this->studentId(),
                'score' => $score,
                'total' => $this->practiceQuestions->count(),
                'completed_at' => now(),
            ]);
        }
    }

    public function resetQuiz(): void
    {
        $this->answers = [];
        $this->quizChecked = false;
    }

    /** How many answers are correct right now. */
    public function quizScore(): int
    {
        $score = 0;
        foreach ($this->practiceQuestions as $q) {
            if ($this->isCorrect($q)) {
                $score++;
            }
        }

        return $score;
    }

    public function isCorrect(ExamQuestion $question): bool
    {
        $given = $this->answers[$question->id] ?? null;

        return $given !== null
            && mb_strtolower(trim((string) $given)) === mb_strtolower(trim((string) $question->correct_answer));
    }

    /** Options to render: real options for MCQ, true/false otherwise. */
    public function optionsFor(ExamQuestion $question): array
    {
        if (is_array($question->options) && $question->options !== []) {
            return $question->options;
        }

        return ['true', 'false'];
    }

    /** Best previous score, so improvement is visible. */
    #[Computed]
    public function bestAttempt(): ?LectureQuizAttempt
    {
        $lecture = $this->lecture;

        return $lecture
            ? LectureQuizAttempt::where('study_material_id', $lecture->id)
                ->where('student_id', $this->studentId())
                ->orderByDesc('score')
                ->first()
            : null;
    }

    // ── Flashcards ──────────────────────────────────────────────────

    #[Computed]
    public function flashcards(): Collection
    {
        $lecture = $this->lecture;

        return $lecture
            ? LectureFlashcard::where('study_material_id', $lecture->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
            : collect();
    }
}
