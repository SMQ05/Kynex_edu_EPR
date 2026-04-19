<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_daily_activity_logs_table
 *
 * Part 5b — Tracks daily class activity scores per student.
 * Teachers record participation, homework completion, and behaviour
 * scores each day. These contribute to the weighted final result.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('subject_id')->nullable();
            $table->ulid('academic_year_id');
            $table->ulid('recorded_by');         // school_users.id (teacher)

            $table->date('log_date');

            // Individual activity scores (0-10 each)
            $table->unsignedTinyInteger('participation_score')
                  ->default(0)
                  ->comment('Class participation score (0-10)');

            $table->unsignedTinyInteger('homework_score')
                  ->default(0)
                  ->comment('Homework completion score (0-10)');

            $table->unsignedTinyInteger('behaviour_score')
                  ->default(10)
                  ->comment('Behaviour score (0-10, 10=excellent)');

            // Computed total (0-30 → normalised to 0-100 at result time)
            $table->unsignedTinyInteger('total_score')
                  ->storedAs('participation_score + homework_score + behaviour_score')
                  ->comment('Auto-computed total (0-30)');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'log_date'], 'daily_activity_unique');

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('school_users')->cascadeOnDelete();

            $table->index(['student_id', 'academic_year_id', 'log_date']);
            $table->index(['class_id', 'section_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
    }
};
