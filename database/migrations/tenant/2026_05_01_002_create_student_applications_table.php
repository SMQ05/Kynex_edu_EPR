<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public-facing student application lifecycle:
 *   submitted → entry_test_scheduled → entry_test_taken
 *             → pending_approval → admitted | rejected | waitlisted
 *
 * Admission only converts the application into a Student record once
 * status reaches admitted; that's when admission_number and
 * registration_number get issued.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id')->nullable();
            $table->ulid('class_id')->nullable();
            $table->ulid('campus_id')->nullable();

            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();

            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();

            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_email')->nullable();

            $table->string('previous_school')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 32)->default('submitted')->index();
            $table->dateTime('entry_test_scheduled_at')->nullable();
            $table->string('entry_test_room', 100)->nullable();
            $table->decimal('entry_test_score', 6, 2)->nullable();
            $table->text('entry_test_notes')->nullable();

            $table->ulid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('decision_notes')->nullable();

            $table->ulid('student_id')->nullable()->index();

            // Public tracking — applicant sees their status via this token.
            $table->string('public_token', 80)->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
