<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add is_confidential to health_records ─────────────────
        Schema::table('health_records', function (Blueprint $table) {
            $table->boolean('is_confidential')->default(false)->after('is_active');
        });

        // ── Add is_confidential to behavior_incidents ─────────────
        Schema::table('behavior_incidents', function (Blueprint $table) {
            $table->boolean('is_confidential')->default(false)->after('attachments');
        });

        // ── Enable PostgreSQL Row Level Security on health_records ─
        // Only applies when running on PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE health_records ENABLE ROW LEVEL SECURITY');

            DB::statement("
                CREATE POLICY health_confidential_policy
                ON health_records
                USING (
                    is_confidential = false
                    OR current_setting('app.user_role', true)
                       IN ('SCHOOL_ADMIN', 'NURSE', 'COUNSELOR')
                )
            ");

            DB::statement('ALTER TABLE behavior_incidents ENABLE ROW LEVEL SECURITY');

            DB::statement("
                CREATE POLICY behavior_confidential_policy
                ON behavior_incidents
                USING (
                    is_confidential = false
                    OR current_setting('app.user_role', true)
                       IN ('SCHOOL_ADMIN', 'COUNSELOR')
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS behavior_confidential_policy ON behavior_incidents');
            DB::statement('ALTER TABLE behavior_incidents DISABLE ROW LEVEL SECURITY');
            DB::statement('DROP POLICY IF EXISTS health_confidential_policy ON health_records');
            DB::statement('ALTER TABLE health_records DISABLE ROW LEVEL SECURITY');
        }

        Schema::table('behavior_incidents', function (Blueprint $table) {
            $table->dropColumn('is_confidential');
        });

        Schema::table('health_records', function (Blueprint $table) {
            $table->dropColumn('is_confidential');
        });
    }
};
