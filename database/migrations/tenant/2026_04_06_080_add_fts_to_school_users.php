<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add PostgreSQL full-text search (FTS) to school_users table.
 * Enables fast staff search by name, email, and employee_id (via staff_profiles).
 *
 * Uses name + email from school_users only (employee_id lives on staff_profiles
 * and would require a cross-table trigger — out of scope here).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Add tsvector column
        DB::statement('ALTER TABLE school_users ADD COLUMN search_vector tsvector');

        // 2. Populate existing rows
        DB::statement("
            UPDATE school_users SET search_vector =
                to_tsvector('english',
                    COALESCE(name, '') || ' ' ||
                    COALESCE(email, '') || ' ' ||
                    COALESCE(phone, '')
                )
        ");

        // 3. Create GIN index
        DB::statement('
            CREATE INDEX idx_school_users_fts
                ON school_users USING GIN(search_vector)
        ');

        // 4. Create trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION update_school_user_search_vector()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.search_vector := to_tsvector('english',
                    COALESCE(NEW.name, '') || ' ' ||
                    COALESCE(NEW.email, '') || ' ' ||
                    COALESCE(NEW.phone, '')
                );
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement('
            CREATE TRIGGER trg_school_user_search_vector
                BEFORE INSERT OR UPDATE OF name, email, phone
                ON school_users
                FOR EACH ROW
                EXECUTE FUNCTION update_school_user_search_vector()
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS trg_school_user_search_vector ON school_users');
        DB::statement('DROP FUNCTION IF EXISTS update_school_user_search_vector()');
        DB::statement('DROP INDEX IF EXISTS idx_school_users_fts');
        DB::statement('ALTER TABLE school_users DROP COLUMN IF EXISTS search_vector');
    }
};
