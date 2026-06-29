<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * subjects.code was created NOT NULL, but the application treats it as
 * optional (Filament form uses ->nullable() and the table renders a '—'
 * placeholder when empty). Submitting the form without a code therefore
 * violated the NOT NULL constraint. Make the column nullable to match
 * the intended behaviour. Existing data is preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 20)->nullable(false)->change();
        });
    }
};
