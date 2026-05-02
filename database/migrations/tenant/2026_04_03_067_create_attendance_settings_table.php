<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_attendance_settings_table
 *
 * Part 6a — Stores per-campus attendance configuration including
 * the late arrival cutoff time used by ProcessBiometricLogs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // NULL campus_id means "school-wide default"
            $table->ulid('campus_id')->nullable()->unique();

            // School day timing
            $table->time('school_start_time')
                  ->default('07:30:00')
                  ->comment('Official school start time');

            $table->time('school_end_time')
                  ->default('14:00:00')
                  ->comment('Official school end time');

            // Late arrival: students/staff arriving after this are marked late
            $table->time('late_arrival_cutoff')
                  ->default('08:00:00')
                  ->comment('Arrivals after this time are marked as late');

            // Grace period in minutes before late_arrival_cutoff kicks in
            $table->unsignedSmallInteger('grace_period_minutes')
                  ->default(0)
                  ->comment('Minutes of grace after school_start_time before marking late');

            // Notify parents/staff when a late arrival is recorded
            $table->boolean('notify_on_late_arrival')
                  ->default(true);

            // Half-day cutoff: present before this time → half-day
            $table->time('half_day_cutoff')
                  ->nullable()
                  ->comment('Arrivals after this are counted as half-day attendance');

            // Early checkout: leaving before this time is flagged
            $table->time('early_departure_cutoff')
                  ->nullable()
                  ->comment('Departures before this time trigger an early-departure flag');

            $table->timestamps();

            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
