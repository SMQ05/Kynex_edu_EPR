<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Restructure admission_criteria to support three scopes:
 *   - Whole school (applies_to_all_classes = true)
 *   - Single class  (one row in admission_criteria_class pivot)
 *   - Multiple classes (N rows in admission_criteria_class pivot)
 *
 * Existing class_id values (if any) are migrated into the pivot before
 * the column is dropped. Existing rows where class_id was NULL are
 * marked applies_to_all_classes = true, preserving the prior semantic.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the new flag, defaulting to false. We'll backfill below.
        Schema::table('admission_criteria', function (Blueprint $table) {
            $table->boolean('applies_to_all_classes')->default(false)->after('name');
        });

        // 2. Old NULL class_id meant "whole school" — preserve that semantic.
        DB::table('admission_criteria')
            ->whereNull('class_id')
            ->update(['applies_to_all_classes' => true]);

        // 3. Pivot table linking criteria to multiple classes.
        Schema::create('admission_criteria_class', function (Blueprint $table) {
            $table->ulid('admission_criteria_id');
            $table->ulid('class_id');

            $table->primary(['admission_criteria_id', 'class_id'], 'admission_criteria_class_pk');

            $table->foreign('admission_criteria_id')
                ->references('id')->on('admission_criteria')->cascadeOnDelete();
            $table->foreign('class_id')
                ->references('id')->on('classes')->cascadeOnDelete();

            $table->index('class_id');
        });

        // 4. Migrate any existing class_id values into the pivot.
        $rows = DB::table('admission_criteria')
            ->whereNotNull('class_id')
            ->get(['id', 'class_id']);

        foreach ($rows as $r) {
            DB::table('admission_criteria_class')->insert([
                'admission_criteria_id' => $r->id,
                'class_id'              => $r->class_id,
            ]);
        }

        // 5. Drop the old single-class column + its unique constraint.
        Schema::table('admission_criteria', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropUnique('admission_criteria_year_class_unique');
            $table->dropColumn('class_id');
        });
    }

    public function down(): void
    {
        Schema::table('admission_criteria', function (Blueprint $table) {
            $table->ulid('class_id')->nullable()->after('academic_year_id');
        });

        // Best-effort restore: pick the first pivot class per criteria
        // (down migrations are not lossless when collapsing N→1).
        $pivot = DB::table('admission_criteria_class')->get();
        $seen  = [];
        foreach ($pivot as $row) {
            if (isset($seen[$row->admission_criteria_id])) {
                continue;
            }
            DB::table('admission_criteria')
                ->where('id', $row->admission_criteria_id)
                ->update(['class_id' => $row->class_id]);
            $seen[$row->admission_criteria_id] = true;
        }

        Schema::table('admission_criteria', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->unique(['academic_year_id', 'class_id'], 'admission_criteria_year_class_unique');
            $table->dropColumn('applies_to_all_classes');
        });

        Schema::dropIfExists('admission_criteria_class');
    }
};
