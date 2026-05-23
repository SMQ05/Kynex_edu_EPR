<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admission exam enhancements:
 *
 * admission_tests:
 *   - scheduled_date / window_opens_at / window_closes_at — exam window control
 *   - result_publication_mode — auto | manual | scheduled
 *   - results_published_at — when results go live (manual trigger or scheduled datetime)
 *
 * admission_test_attempts:
 *   - temp_username / temp_password_hash — per-attempt exam-day login credentials
 *   - temp_creds_sent_at — when credentials were emailed
 *   - violation_count / violation_log — proctoring events
 *   - proctoring_cancelled_at — set when exam is cancelled due to violations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('is_active');
            $table->dateTime('window_opens_at')->nullable()->after('scheduled_date');
            $table->dateTime('window_closes_at')->nullable()->after('window_opens_at');
            // auto = immediately on grading, manual = admin publishes, scheduled = on results_published_at
            $table->string('result_publication_mode', 20)->default('auto')->after('window_closes_at');
            $table->dateTime('results_published_at')->nullable()->after('result_publication_mode');
        });

        Schema::table('admission_test_attempts', function (Blueprint $table) {
            $table->string('temp_username', 80)->nullable()->unique()->after('attempt_token');
            $table->string('temp_password_hash')->nullable()->after('temp_username');
            $table->dateTime('temp_creds_sent_at')->nullable()->after('temp_password_hash');
            $table->unsignedSmallInteger('violation_count')->default(0)->after('graded_at');
            $table->json('violation_log')->nullable()->after('violation_count');
            $table->dateTime('proctoring_cancelled_at')->nullable()->after('violation_log');
        });
    }

    public function down(): void
    {
        Schema::table('admission_tests', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_date', 'window_opens_at', 'window_closes_at',
                'result_publication_mode', 'results_published_at',
            ]);
        });

        Schema::table('admission_test_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'temp_username', 'temp_password_hash', 'temp_creds_sent_at',
                'violation_count', 'violation_log', 'proctoring_cancelled_at',
            ]);
        });
    }
};
