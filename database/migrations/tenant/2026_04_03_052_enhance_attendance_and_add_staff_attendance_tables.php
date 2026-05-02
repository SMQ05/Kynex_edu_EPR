<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Enhance attendance_records table ──────────────────────────
        // Drop indexes first, then drop column, each in
        // separate Schema::table() calls.  Guard every step so the
        // migration is re-runnable after a partial failure.

        // Step 1: Drop the index that references 'status' (if it still exists)
        if ($this->indexExists('attendance_records', 'attendance_records_date_status_index')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex(['date', 'status']);
            });
        }

        // Step 2: Add academic_year_id (if not already present from a prior partial run)
        if (! Schema::hasColumn('attendance_records', 'academic_year_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->ulid('academic_year_id')->nullable()->after('section_id');
            });
        }

        // Step 3: Drop the old string 'status' column
        if (Schema::hasColumn('attendance_records', 'status')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        // Step 4: Re-add 'status' as enum + add late_minutes + composite index
        Schema::table('attendance_records', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_records', 'status')) {
                $table->string('status')->default('absent')->after('date');
            }

            if (! Schema::hasColumn('attendance_records', 'late_minutes')) {
                $table->tinyInteger('late_minutes')->nullable()->after('remarks');
            }
        });

        // Step 5: Add composite index
        if (! $this->indexExists('attendance_records', 'att_class_section_date_idx')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['class_id', 'section_id', 'date'], 'att_class_section_date_idx');
            });
        }

        // ── Staff Attendance Records ─────────────────────────────────
        if (! Schema::hasTable('staff_attendance_records')) {
            Schema::create('staff_attendance_records', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('school_user_id');
                $table->date('date');
                $table->time('check_in_time')->nullable();
                $table->time('check_out_time')->nullable();
                $table->string('status')->default('absent');
                $table->ulid('marked_by')->nullable();
                $table->unsignedInteger('overtime_minutes')->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['school_user_id', 'date'], 'staff_att_user_date_unique');

                $table->foreign('school_user_id')
                    ->references('id')
                    ->on('school_users')
                    ->cascadeOnDelete();

                $table->foreign('marked_by')
                    ->references('id')
                    ->on('school_users')
                    ->nullOnDelete();
            });
        }

        // ── Attendance Devices ───────────────────────────────────────
        if (! Schema::hasTable('attendance_devices')) {
            Schema::create('attendance_devices', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->string('device_type')->default('manual');
                $table->string('serial_number')->nullable();
                $table->string('ip_address')->nullable();
                $table->unsignedSmallInteger('port')->default(4370);
                $table->string('location')->nullable();
                $table->ulid('campus_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();

                $table->foreign('campus_id')
                    ->references('id')
                    ->on('campuses')
                    ->nullOnDelete();
            });
        }

        // ── Biometric Logs ───────────────────────────────────────────
        if (! Schema::hasTable('biometric_logs')) {
            Schema::create('biometric_logs', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('device_id');
                $table->string('device_user_id'); // biometric device's internal ID
                $table->ulid('school_user_id')->nullable();
                $table->ulid('student_id')->nullable();
                $table->timestamp('punch_time');
                $table->string('punch_type')->default('unknown');
                $table->boolean('is_processed')->default(false);
                $table->timestamps();

                $table->index(['punch_time', 'is_processed'], 'bio_punch_processed_idx');

                $table->foreign('device_id')
                    ->references('id')
                    ->on('attendance_devices')
                    ->cascadeOnDelete();

                $table->foreign('school_user_id')
                    ->references('id')
                    ->on('school_users')
                    ->nullOnDelete();

                $table->foreign('student_id')
                    ->references('id')
                    ->on('students')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_logs');
        Schema::dropIfExists('attendance_devices');
        Schema::dropIfExists('staff_attendance_records');

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['late_minutes']);
            $table->dropIndex('att_class_section_date_idx');
            $table->dropColumn('status');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('status')->after('date');
            $table->dropColumn('academic_year_id');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]))->isNotEmpty();
    }
};
