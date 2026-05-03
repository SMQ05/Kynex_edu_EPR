<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds section-level overrides on the fee_masters price book.
 *
 * Existing rows (section_id IS NULL) remain class-level defaults.
 * A row with a non-null section_id targets only that section and
 * overrides the class-level default during fee generation.
 *
 * Uniqueness is enforced via two partial indexes — Postgres treats
 * NULLs as distinct, so a plain composite unique would let multiple
 * "class default" rows slip in for the same class+type+year.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the column (nullable). Don't reference the sections
        //    table with a foreign key here; it would fail if rows were
        //    added without a matching section, and the FK is also
        //    application-enforced via the form Select.
        Schema::table('fee_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_masters', 'section_id')) {
                $table->ulid('section_id')->nullable()->after('class_id');
                $table->index('section_id');
            }
        });

        // 2. Drop the old unique constraint that didn't account for
        //    section overrides (raw SQL — Schema::dropUnique is fragile
        //    if the index name has been mangled by Postgres).
        DB::statement('ALTER TABLE fee_masters DROP CONSTRAINT IF EXISTS fee_master_unique');
        DB::statement('DROP INDEX IF EXISTS fee_master_unique');

        // 3. Partial unique 1: at most one class-level default per
        //    (class, type, year) when section_id IS NULL.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS fee_masters_class_default_uidx
            ON fee_masters (class_id, fee_type_id, academic_year_id)
            WHERE section_id IS NULL AND deleted_at IS NULL
        SQL);

        // 4. Partial unique 2: at most one section override per
        //    (class, type, year, section).
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS fee_masters_section_override_uidx
            ON fee_masters (class_id, fee_type_id, academic_year_id, section_id)
            WHERE section_id IS NOT NULL AND deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS fee_masters_section_override_uidx');
        DB::statement('DROP INDEX IF EXISTS fee_masters_class_default_uidx');

        // Restore the simpler composite uniqueness (best-effort).
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS fee_master_unique
            ON fee_masters (class_id, fee_type_id, academic_year_id)
            WHERE deleted_at IS NULL
        SQL);

        Schema::table('fee_masters', function (Blueprint $table) {
            if (Schema::hasColumn('fee_masters', 'section_id')) {
                $table->dropIndex(['section_id']);
                $table->dropColumn('section_id');
            }
        });
    }
};
