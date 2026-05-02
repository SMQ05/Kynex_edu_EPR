<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a registration_number to students. Issued only after admission is
 * approved (status = enrolled) — distinct from admission_number which is
 * the temporary intake reference assigned at form submission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('registration_number', 50)->nullable()->after('admission_number');
            $table->unique('registration_number');
            $table->index('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['registration_number']);
            $table->dropIndex(['registration_number']);
            $table->dropColumn('registration_number');
        });
    }
};
