<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Component weights for the weighted annual result.
 *
 * Defaults: exam = 80%, homework = 10%, class assignment = 10%
 * (must sum to 100; enforced in form validation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->unsignedTinyInteger('exam_weight_percent')->default(80)->after('is_current');
            $table->unsignedTinyInteger('homework_weight_percent')->default(10)->after('exam_weight_percent');
            $table->unsignedTinyInteger('class_assignment_weight_percent')->default(10)->after('homework_weight_percent');
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn([
                'exam_weight_percent',
                'homework_weight_percent',
                'class_assignment_weight_percent',
            ]);
        });
    }
};
