<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enforce unique class names per campus and clean up existing duplicates.
 *
 * This backs the ClassResource form's
 *   ->unique(table: 'classes', column: 'name', ignoreRecord: true,
 *            modifyRuleUsing: ... ->where('campus_id', ...)->whereNull('deleted_at'))
 * validation with a database-level guarantee. Form validation alone is
 * race-prone (two concurrent submits can both pass) and is bypassed entirely
 * by the CSV bulk importer and any direct DB writes, so the constraint has to
 * live in the schema too — mirroring the student_categories unique migration.
 *
 * Duplicates are collapsed to a single keeper per (name, campus_id): the row
 * that actually holds students wins, and the empty duplicates are soft-deleted.
 * As a safety net, any students sitting on a non-keeper row are re-pointed at
 * the keeper first, so the cleanup can never orphan a student.
 *
 * The constraint is a partial unique index on (name, campus_id)
 * WHERE deleted_at IS NULL, so soft-deleted duplicates don't collide with the
 * keeper. Because SQL treats NULLs as distinct in a unique index, rows with a
 * NULL campus_id are never constrained — which matches the form rule, where
 * `->where('campus_id', null)` likewise never matches an existing row. Cleanup
 * is therefore scoped to campus_id IS NOT NULL to stay consistent with what
 * the index actually enforces.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Collapse duplicates first — otherwise the unique index can't be built.
        //    Only (name, campus_id) pairs with a real campus can violate the
        //    partial index, so NULL-campus rows are left untouched.
        $duplicateGroups = DB::table('classes')
            ->whereNull('deleted_at')
            ->whereNotNull('campus_id')
            ->select('name', 'campus_id')
            ->groupBy('name', 'campus_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            // Order candidates by how many students they hold, descending, so the
            // populated row wins; created_at breaks ties deterministically.
            $rows = DB::table('classes')
                ->whereNull('deleted_at')
                ->where('name', $group->name)
                ->where('campus_id', $group->campus_id)
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

        // 2. Add the unique constraint, scoped to live rows so soft-deletes are exempt.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('
                CREATE UNIQUE INDEX classes_name_campus_unique
                    ON classes (name, campus_id)
                    WHERE deleted_at IS NULL
            ');
        } else {
            // Fallback for drivers without partial indexes (e.g. MySQL).
            DB::statement('
                CREATE UNIQUE INDEX classes_name_campus_unique
                    ON classes (name, campus_id)
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS classes_name_campus_unique');
    }
};
