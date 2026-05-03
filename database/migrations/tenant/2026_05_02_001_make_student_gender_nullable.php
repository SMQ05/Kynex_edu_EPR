<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * gender on students was originally NOT NULL, but bulk imports and
 * older paper records often don't include it. Real schools want to
 * onboard students first and capture gender later. Make it nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Postgres: ALTER COLUMN ... DROP NOT NULL is the safe primitive.
        DB::statement('ALTER TABLE students ALTER COLUMN gender DROP NOT NULL');
    }

    public function down(): void
    {
        // Backfill any nulls before re-imposing NOT NULL.
        DB::statement("UPDATE students SET gender = 'other' WHERE gender IS NULL");
        DB::statement('ALTER TABLE students ALTER COLUMN gender SET NOT NULL');
    }
};
