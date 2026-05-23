<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slot-based admission scheduling.
 *
 * admission_sessions — one row per scheduled batch (e.g. "Test batch 1,
 * Class 5, 15 May 9 AM, max 30 students"). Multiple applicants are
 * assigned to the same session; the school sets capacity so they know
 * exactly how many seats are available per slot.
 *
 * student_applications gains:
 *   test_session_id      — which test slot this applicant is in
 *   interview_session_id — which interview slot this applicant is in
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('academic_year_id');
            $table->ulid('class_id')->nullable(); // null = cross-class session

            // 'test' | 'interview'
            $table->string('type', 20)->default('test');

            $table->dateTime('scheduled_at');
            $table->string('room', 100)->nullable();
            $table->string('panel')->nullable();       // interview panel/name
            $table->unsignedInteger('max_capacity')->default(30);
            $table->text('notes')->nullable();

            // When batch credential emails were dispatched for this session.
            $table->dateTime('credentials_dispatched_at')->nullable();

            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        Schema::table('student_applications', function (Blueprint $table) {
            $table->ulid('test_session_id')->nullable()->after('entry_test_notes');
            $table->ulid('interview_session_id')->nullable()->after('interview_notes');

            $table->foreign('test_session_id')
                ->references('id')->on('admission_sessions')->nullOnDelete();
            $table->foreign('interview_session_id')
                ->references('id')->on('admission_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->dropForeign(['test_session_id']);
            $table->dropForeign(['interview_session_id']);
            $table->dropColumn(['test_session_id', 'interview_session_id']);
        });

        Schema::dropIfExists('admission_sessions');
    }
};
