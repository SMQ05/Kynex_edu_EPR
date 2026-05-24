<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subject-wise (per-period) attendance — distinct from the per-day
 * attendance_records table. Lets a teacher mark attendance for a specific
 * subject/period on a date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_attendance_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->ulid('section_id');
            $table->ulid('subject_id');
            $table->date('date');
            $table->string('period', 30)->nullable(); // e.g. "1", "P3", "Morning"
            $table->string('status', 20)->default('present'); // present|absent|late|half_day|excused
            $table->text('remarks')->nullable();
            $table->ulid('marked_by')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('marked_by')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['student_id', 'subject_id', 'date', 'period'], 'subject_attendance_unique');
            $table->index(['class_id', 'section_id', 'subject_id', 'date'], 'subject_attendance_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_attendance_records');
    }
};
