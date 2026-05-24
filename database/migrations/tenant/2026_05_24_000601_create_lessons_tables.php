<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lesson Plan module (Infix "Lesson Plan"): a teachable-unit layer that sits
 * ALONGSIDE the existing Syllabus + SyllabusTopic (which track week/planned/
 * completed coverage). No duplication:
 *   - `lessons`       = a reusable teachable unit for a class/subject.
 *   - `lesson_plans`  = a date-scheduled plan (objectives/activities/resources)
 *                       that references a lesson and optionally a syllabus topic.
 * All ULID PKs, soft deletes, created_by FK to school_users.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Teachable units (a chapter/unit under a subject + class).
        Schema::create('lessons', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('class_id');
            $table->ulid('subject_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('academic_year_id')->nullable();
            $table->ulid('teacher_id')->nullable();
            $table->string('title');
            $table->string('code', 60)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['class_id', 'subject_id']);
        });

        // Date-scheduled lesson plans referencing a lesson (+ optional topic).
        Schema::create('lesson_plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('lesson_id');
            $table->ulid('syllabus_topic_id')->nullable();
            $table->ulid('teacher_id')->nullable();
            $table->string('title');
            $table->date('plan_date')->nullable();
            $table->unsignedInteger('week_number')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('objectives')->nullable();
            $table->text('activities')->nullable();
            $table->text('teaching_resources')->nullable();
            $table->text('assessment')->nullable();
            $table->text('homework')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('planned'); // planned|in_progress|completed
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('syllabus_topic_id')->references('id')->on('syllabus_topics')->nullOnDelete();
            $table->foreign('teacher_id')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['lesson_id', 'plan_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('lessons');
    }
};
