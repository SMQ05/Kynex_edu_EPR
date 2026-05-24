<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-Class Student: additional class enrolments beyond a student's
 * primary class_id/section_id (e.g. electives, remedial, cross-class
 * subjects). Does not replace the primary placement on `students`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_enrolments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('class_id');
            $table->ulid('section_id')->nullable();
            $table->ulid('academic_year_id')->nullable();
            $table->string('roll_number', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['student_id', 'class_id', 'academic_year_id'], 'student_class_year_unique');
            $table->index(['class_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_enrolments');
    }
};
