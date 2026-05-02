<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numeric marks on submissions so homework / class assignments can
 * contribute to the weighted annual result. The original `grade` string
 * column stays for back-compat (free-text / letter grades).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->decimal('marks_obtained', 6, 2)->nullable()->after('grade');
            $table->unsignedInteger('total_marks')->nullable()->after('marks_obtained');
        });
    }

    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn(['marks_obtained', 'total_marks']);
        });
    }
};
