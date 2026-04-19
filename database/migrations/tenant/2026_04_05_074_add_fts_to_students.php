<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add PostgreSQL full-text search (FTS) to the students table.
 * Creates a tsvector column with GIN index and auto-update trigger.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Add tsvector column
        DB::statement('ALTER TABLE students ADD COLUMN search_vector tsvector');

        // 2. Populate existing rows
        DB::statement("
            UPDATE students SET search_vector =
                to_tsvector('english',
                    COALESCE(first_name, '') || ' ' ||
                    COALESCE(last_name, '') || ' ' ||
                    COALESCE(admission_number, '') || ' ' ||
                    COALESCE(phone, '') || ' ' ||
                    COALESCE(email, '')
                )
        ");

        // 3. Create GIN index for fast FTS lookups
        DB::statement('
            CREATE INDEX idx_students_fts
                ON students USING GIN(search_vector)
        ');

        // 4. Create trigger function to auto-update on INSERT/UPDATE
        DB::statement("
            CREATE OR REPLACE FUNCTION update_student_search_vector()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.search_vector := to_tsvector('english',
                    COALESCE(NEW.first_name, '') || ' ' ||
                    COALESCE(NEW.last_name, '') || ' ' ||
                    COALESCE(NEW.admission_number, '') || ' ' ||
                    COALESCE(NEW.phone, '') || ' ' ||
                    COALESCE(NEW.email, '')
                );
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement('
            CREATE TRIGGER trg_student_search_vector
                BEFORE INSERT OR UPDATE OF
                    first_name, last_name, admission_number, phone, email
                ON students
                FOR EACH ROW
                EXECUTE FUNCTION update_student_search_vector()
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS trg_student_search_vector ON students');
        DB::statement('DROP FUNCTION IF EXISTS update_student_search_vector()');
        DB::statement('DROP INDEX IF EXISTS idx_students_fts');
        DB::statement('ALTER TABLE students DROP COLUMN IF EXISTS search_vector');
    }
};
