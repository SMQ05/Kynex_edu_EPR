<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enforce unique category names and clean up existing duplicates.
 *
 * Duplicates (e.g. several "General" / "Scholarship" rows) are collapsed to a
 * single keeper per name — the row that actually has students assigned is kept,
 * and the empty duplicates are soft-deleted. As a safety net, any students that
 * happen to sit on a non-keeper row are re-pointed at the keeper first, so the
 * cleanup can never orphan a student.
 *
 * The constraint is a partial unique index on (name) WHERE deleted_at IS NULL,
 * which matches the resource form's ->unique(...)->withoutTrashed() validation:
 * a name only has to be unique among the live (non-trashed) rows, so the
 * soft-deleted duplicates above don't collide with the keeper.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Collapse duplicates first — otherwise the unique index can't be built.
        $duplicateNames = DB::table('student_categories')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            // Order candidates by how many students they hold, descending, so the
            // populated "General" row wins; created_at breaks ties deterministically.
            $rows = DB::table('student_categories')
                ->whereNull('deleted_at')
                ->where('name', $name)
                ->orderBy('created_at')
                ->pluck('id')
                ->map(fn ($id) => [
                    'id'       => $id,
                    'students' => DB::table('students')
                        ->whereNull('deleted_at')
                        ->where('category_id', $id)
                        ->count(),
                ])
                ->sortByDesc('students')
                ->values();

            $keeperId = $rows->first()['id'];

            foreach ($rows->slice(1) as $row) {
                // Safety net: re-home any students before removing the duplicate.
                if ($row['students'] > 0) {
                    DB::table('students')
                        ->where('category_id', $row['id'])
                        ->update(['category_id' => $keeperId]);
                }

                DB::table('student_categories')
                    ->where('id', $row['id'])
                    ->update(['deleted_at' => now()]);
            }
        }

        // 2. Add the unique constraint, scoped to live rows so soft-deletes are exempt.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('
                CREATE UNIQUE INDEX student_categories_name_unique
                    ON student_categories (name)
                    WHERE deleted_at IS NULL
            ');
        } else {
            // Fallback for drivers without partial indexes (e.g. MySQL).
            DB::statement('
                CREATE UNIQUE INDEX student_categories_name_unique
                    ON student_categories (name)
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS student_categories_name_unique');
    }
};
