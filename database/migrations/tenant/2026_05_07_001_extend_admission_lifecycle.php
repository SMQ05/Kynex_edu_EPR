<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase: complete student admission lifecycle.
 *
 * Adds:
 *   - admission_criteria        — per academic-year/class weightages and minima.
 *   - admission_tests           — online entry test definitions per class/year.
 *   - admission_test_questions  — MCQ / short-answer / essay items.
 *   - admission_test_attempts   — applicant attempts with token-based access.
 *   - admission_test_answers    — per-question answers + auto/manual grading.
 *
 * Extends student_applications with:
 *   - section_id (was missing — already referenced in admit() service)
 *   - interview_*              — interview scheduling, panel, score, notes.
 *   - previous_school_score / previous_score_max — prior academic record.
 *   - final_score / weighted_components — computed result snapshot.
 *   - auto_rejected / auto_reject_reason — surface why min-marks gate triggered.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── student_applications: add interview + section + final score ──
        Schema::table('student_applications', function (Blueprint $table) {
            $table->ulid('section_id')->nullable()->after('class_id');

            $table->dateTime('interview_scheduled_at')->nullable()->after('entry_test_notes');
            $table->string('interview_room', 100)->nullable()->after('interview_scheduled_at');
            $table->string('interview_panel')->nullable()->after('interview_room');
            $table->decimal('interview_score', 6, 2)->nullable()->after('interview_panel');
            $table->text('interview_notes')->nullable()->after('interview_score');

            $table->decimal('previous_school_score', 8, 2)->nullable()->after('interview_notes');
            $table->decimal('previous_score_max', 8, 2)->nullable()->after('previous_school_score');

            $table->decimal('final_score', 6, 2)->nullable()->after('previous_score_max');
            $table->decimal('final_percentage', 5, 2)->nullable()->after('final_score');
            $table->json('weighted_components')->nullable()->after('final_percentage');

            $table->boolean('auto_rejected')->default(false)->after('weighted_components');
            $table->string('auto_reject_reason')->nullable()->after('auto_rejected');

            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
        });

        // ── admission_criteria ──
        Schema::create('admission_criteria', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id');
            $table->ulid('class_id')->nullable(); // null = applies to all classes

            $table->string('name')->nullable();

            // Weightages — must sum to 100. Each can be 0.
            $table->unsignedSmallInteger('test_weightage')->default(50);
            $table->unsignedSmallInteger('interview_weightage')->default(30);
            $table->unsignedSmallInteger('previous_score_weightage')->default(20);

            // Test/interview full marks (defaults shown to applicants).
            $table->unsignedInteger('test_full_marks')->default(100);
            $table->unsignedInteger('interview_full_marks')->default(100);

            // Auto-reject thresholds. Applicants below are auto-rejected
            // before reaching the institute-head approval queue.
            $table->decimal('min_test_score', 6, 2)->nullable();
            $table->decimal('min_interview_score', 6, 2)->nullable();

            // Selection threshold on the weighted final score (0..100 %).
            $table->decimal('min_final_percentage', 5, 2)->nullable();

            // Should test/interview minima auto-reject (vs just flagged)?
            $table->boolean('auto_reject_below_test')->default(true);
            $table->boolean('auto_reject_below_interview')->default(true);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->unique(['academic_year_id', 'class_id'], 'admission_criteria_year_class_unique');
        });

        // ── admission_tests ──
        Schema::create('admission_tests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id');
            $table->ulid('class_id')->nullable(); // null = applies to all classes

            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();

            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('total_marks')->default(100);
            $table->unsignedInteger('passing_marks')->default(40);

            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('show_score_to_applicant')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        // ── admission_test_questions ──
        Schema::create('admission_test_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('admission_test_id');

            // mcq | true_false | short_answer | essay
            $table->string('type', 20)->default('mcq');
            $table->text('question_text');
            $table->json('options')->nullable();           // for mcq/true_false
            $table->string('correct_answer')->nullable();  // option key for mcq, "true"/"false", or expected answer for short
            $table->decimal('marks', 6, 2)->default(1.0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('admission_test_id')->references('id')->on('admission_tests')->cascadeOnDelete();
            $table->index('admission_test_id');
        });

        // ── admission_test_attempts ──
        Schema::create('admission_test_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_application_id');
            $table->ulid('admission_test_id');

            $table->string('attempt_token', 80)->unique();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            // started | submitted | graded | expired
            $table->string('status', 20)->default('pending');

            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('obtained_marks', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();

            // Pending essay review etc.
            $table->boolean('needs_manual_grading')->default(false);

            $table->ulid('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();

            $table->timestamps();

            $table->foreign('student_application_id')->references('id')->on('student_applications')->cascadeOnDelete();
            $table->foreign('admission_test_id')->references('id')->on('admission_tests')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['student_application_id', 'admission_test_id'], 'admission_attempts_app_test_unique');
        });

        // ── admission_test_answers ──
        Schema::create('admission_test_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('attempt_id');
            $table->ulid('question_id');

            $table->text('answer_text')->nullable();
            $table->string('selected_option')->nullable();

            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 6, 2)->nullable();

            $table->timestamps();

            $table->foreign('attempt_id')->references('id')->on('admission_test_attempts')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('admission_test_questions')->cascadeOnDelete();
            $table->unique(['attempt_id', 'question_id'], 'admission_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_test_answers');
        Schema::dropIfExists('admission_test_attempts');
        Schema::dropIfExists('admission_test_questions');
        Schema::dropIfExists('admission_tests');
        Schema::dropIfExists('admission_criteria');

        Schema::table('student_applications', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn([
                'section_id',
                'interview_scheduled_at', 'interview_room', 'interview_panel',
                'interview_score', 'interview_notes',
                'previous_school_score', 'previous_score_max',
                'final_score', 'final_percentage', 'weighted_components',
                'auto_rejected', 'auto_reject_reason',
            ]);
        });
    }
};
