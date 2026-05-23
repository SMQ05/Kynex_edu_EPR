<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track when an applicant was checked in on exam day.
        Schema::table('admission_test_attempts', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('temp_creds_sent_at');
        });

        // Track when admin pressed "Start Exam" for a session.
        Schema::table('admission_sessions', function (Blueprint $table) {
            $table->timestamp('exam_started_at')->nullable()->after('credentials_dispatched_at');
        });
    }

    public function down(): void
    {
        Schema::table('admission_test_attempts', function (Blueprint $table) {
            $table->dropColumn('checked_in_at');
        });

        Schema::table('admission_sessions', function (Blueprint $table) {
            $table->dropColumn('exam_started_at');
        });
    }
};
