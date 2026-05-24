<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Question Groups (taxonomy for the question bank) ───────────
        Schema::create('question_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->ulid('subject_id')->nullable();
            $table->ulid('class_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'class_id']);

            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        // ── Question Bank ──────────────────────────────────────────────
        Schema::create('exam_questions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('question_group_id')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->string('type', 20)->default('mcq'); // mcq|true_false|short_answer|essay|math
            $table->string('difficulty', 20)->default('medium'); // easy|medium|hard
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->decimal('marks', 6, 2)->default(1.0);
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_group_id', 'type']);
            $table->index('subject_id');

            $table->foreign('question_group_id')->references('id')->on('question_groups')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        // ── Online Exams (for enrolled students) ───────────────────────
        Schema::create('online_exams', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id')->nullable();
            $table->ulid('class_id')->nullable();
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->ulid('exam_id')->nullable(); // optional link to a parent Exam
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('passing_marks')->default(0);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('show_score_to_student')->default(false);
            $table->boolean('ai_grade_enabled')->default(true); // AI auto-grade short/essay (allowed for exams)
            $table->string('status', 20)->default('draft'); // draft|published|closed
            $table->dateTime('window_opens_at')->nullable();
            $table->dateTime('window_closes_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['academic_year_id', 'class_id']);
            $table->index('status');

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            $table->foreign('exam_id')->references('id')->on('exams')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        // ── Online Exam ↔ Questions pivot ──────────────────────────────
        Schema::create('online_exam_questions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('online_exam_id');
            $table->ulid('exam_question_id');
            $table->decimal('marks', 6, 2)->nullable(); // override question default
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['online_exam_id', 'exam_question_id'], 'online_exam_question_unique');

            $table->foreign('online_exam_id')->references('id')->on('online_exams')->cascadeOnDelete();
            $table->foreign('exam_question_id')->references('id')->on('exam_questions')->cascadeOnDelete();
        });

        // ── Online Exam Attempts ───────────────────────────────────────
        Schema::create('online_exam_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('online_exam_id');
            $table->ulid('student_id');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending|started|submitted|graded|expired
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('obtained_marks', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('needs_manual_grading')->default(false);
            $table->ulid('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['online_exam_id', 'student_id'], 'online_exam_attempt_unique');
            $table->index('status');

            $table->foreign('online_exam_id')->references('id')->on('online_exams')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('school_users')->nullOnDelete();
        });

        // ── Online Exam Answers ────────────────────────────────────────
        Schema::create('online_exam_answers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('attempt_id');
            $table->ulid('question_id'); // references exam_questions
            $table->text('answer_text')->nullable();
            $table->string('selected_option')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 6, 2)->nullable();
            $table->text('ai_feedback')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id'], 'online_exam_answer_unique');

            $table->foreign('attempt_id')->references('id')->on('online_exam_attempts')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('exam_questions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_exam_answers');
        Schema::dropIfExists('online_exam_attempts');
        Schema::dropIfExists('online_exam_questions');
        Schema::dropIfExists('online_exams');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('question_groups');
    }
};
