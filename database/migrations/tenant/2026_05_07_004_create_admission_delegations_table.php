<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class-scoped delegation of admission permissions.
 *
 *   - One row per (user, permission, class) triple.
 *   - class_id NULL means "all classes" — unlimited scope.
 *   - The four permissions managed via this table:
 *       enter_admission_marks
 *       conduct_admission_interview
 *       create_admission_tests
 *       manage_student_admissions  (umbrella — covers the other three)
 *
 * The school admin can compose any combination: a single teacher across
 * Class 5 and Class 7 only, several teachers each owning different
 * subjects/classes for marking, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_delegations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id');
            $table->string('permission', 64);
            $table->ulid('class_id')->nullable();
            $table->timestamps();

            $table->foreign('school_user_id')
                ->references('id')->on('school_users')->cascadeOnDelete();
            $table->foreign('class_id')
                ->references('id')->on('classes')->cascadeOnDelete();

            $table->unique(
                ['school_user_id', 'permission', 'class_id'],
                'admission_delegations_unique',
            );
            $table->index(['school_user_id', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_delegations');
    }
};
