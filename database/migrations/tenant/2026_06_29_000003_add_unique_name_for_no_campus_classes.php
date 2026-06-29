<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Close the NULL-campus gap left by 2026_06_29_000002.
 *
 * That migration added a partial unique index on (name, campus_id)
 * WHERE deleted_at IS NULL. Because SQL treats NULLs as distinct inside a
 * unique index, rows with a NULL campus_id are NOT constrained by it — so two
 * live classes named "Grade 1" with no campus selected slip past the database.
 *
 * The ClassResource form, however, DOES block that case: its rule uses
 *   ->where('campus_id', $get('campus_id'))
 * and Laravel rewrites `where('campus_id', null)` into `whereNull('campus_id')`,
 * which matches existing no-campus rows. So the form and the DB disagreed.
 *
 * This migration makes the database match the form by adding a second partial
 * unique index covering only the no-campus rows: unique on (name)
 * WHERE deleted_at IS NULL AND campus_id IS NULL. Combined with the existing
 * classes_name_campus_unique index, the full guarantee is:
 *   - same name + same campus      → rejected
 *   - same name + both no campus    → rejected
 *   - same name + different campus  → allowed
 * Soft-deleted rows are exempt in both indexes, so a name is reusable after
 * deletion.
 *
 * Existing no-campus duplicates are collapsed first (otherwise the index can't
 * be built): the row holding the most students wins, students on the losers are
 * re-homed to the keeper, and the empty losers are soft-deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Collapse live, no-campus duplicates by name before indexing.
        $duplicateNames = DB::table('classes')
            ->whereNull('deleted_at')
            ->whereNull('campus_id')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            // Populated row wins; created_at breaks ties deterministically.
            $rows = DB::table('classes')
                ->whereNull('deleted_at')
                ->whereNull('campus_id')
                ->where('name', $name)
                ->orderBy('created_at')
                ->pluck('id')
                ->map(fn ($id) => [
                    'id'       => $id,
                    'students' => DB::table('students')
                        ->whereNull('deleted_at')
                        ->where('class_id', $id)
                        ->count(),
                ])
                ->sortByDesc('students')
                ->values();

            $keeperId = $rows->first()['id'];

            foreach ($rows->slice(1) as $row) {
                // Safety net: re-home any students before removing the duplicate.
                if ($row['students'] > 0) {
                    DB::table('students')
                        ->where('class_id', $row['id'])
                        ->update(['class_id' => $keeperId]);
                }

                DB::table('classes')
                    ->where('id', $row['id'])
                    ->update(['deleted_at' => now()]);
            }
        }

        // 2. Add the partial unique index for the no-campus case.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('
                CREATE UNIQUE INDEX classes_name_no_campus_unique
                    ON classes (name)
                    WHERE deleted_at IS NULL AND campus_id IS NULL
            ');
        }
        // Drivers without partial indexes (e.g. MySQL) keep form-level
        // protection only for this edge; the tenant manager is pgsql.
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS classes_name_no_campus_unique');
    }
};
