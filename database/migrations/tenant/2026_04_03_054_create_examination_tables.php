<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Exams ─────────────────────────────────────────────────────
        Schema::create('exams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id');
            $table->string('name'); // e.g. "First Term Exam", "Mid Term"
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('publish_results')->default(false);
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });

        // ── Exam Schedules ────────────────────────────────────────────
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('exam_id');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id');
            $table->date('exam_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('room')->nullable();
            $table->unsignedInteger('full_marks');
            $table->unsignedInteger('pass_marks');
            $table->timestamps();

            $table->unique(['exam_id', 'class_id', 'section_id', 'subject_id'], 'exam_schedule_unique');

            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnDelete();

            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->cascadeOnDelete();

            $table->foreign('section_id')
                ->references('id')
                ->on('sections')
                ->nullOnDelete();

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects')
                ->cascadeOnDelete();
        });

        // ── Marks ─────────────────────────────────────────────────────
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('exam_schedule_id');
            $table->ulid('student_id');
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->text('remarks')->nullable();
            $table->ulid('entered_by')->nullable();
            $table->timestamps();

            $table->unique(['exam_schedule_id', 'student_id'], 'exam_marks_unique');

            $table->foreign('exam_schedule_id')
                ->references('id')
                ->on('exam_schedules')
                ->cascadeOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('entered_by')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });

        // ── Grade Rules ───────────────────────────────────────────────
        Schema::create('grade_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name'); // e.g. "Standard Grading"
            $table->string('grade');       // A+, A, B+, B, C, D, F
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->decimal('grade_point', 3, 1)->nullable(); // 4.0, 3.5, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'min_percentage']);
        });

        // ── Exam Results (aggregated per student per exam) ────────────
        Schema::create('exam_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('exam_id');
            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('marks_obtained', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade')->nullable();
            $table->decimal('grade_point', 3, 1)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->string('status')->default('pass');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id'], 'exam_result_unique');

            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('grade_rules');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exams');
    }
};
