<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // ── Academic info ────────────────────────────────────────
            $table->string('admission_number', 50)->unique();
            $table->string('roll_number', 50)->nullable();
            $table->date('admission_date');
            $table->ulid('academic_year_id');
            $table->ulid('class_id');
            $table->ulid('section_id');
            $table->ulid('category_id')->nullable();
            $table->ulid('campus_id')->nullable();

            // ── Personal info ───────────────────────────────────────
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->default('male');
            $table->string('blood_group', 3)->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality', 50)->default('Pakistani');
            $table->string('profile_photo_path')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // ── Billing-critical status ─────────────────────────────
            $table->string('status')->default('enrolled');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('status_change_reason')->nullable();

            // ── Portal & misc ───────────────────────────────────────
            $table->ulid('school_user_id')->nullable();
            $table->text('special_needs_notes')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('previous_school')->nullable();

            // ── Future modules ──────────────────────────────────────
            $table->ulid('transport_route_id')->nullable();
            $table->ulid('hostel_room_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'section_id', 'status', 'academic_year_id'], 'student_list_idx');
            $table->index(['status', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
