<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add GIN indexes on JSONB columns for fast containment queries.
 * GIN indexes accelerate @>, ?, ?|, ?& operators on JSONB data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // notices.target_roles — queried when filtering notifications by role
        DB::statement('
            CREATE INDEX idx_notices_target_roles_gin
                ON notices USING GIN((target_roles::jsonb))
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_notices_target_roles_gin');
    }
};
