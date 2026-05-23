<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            // Student CNIC / B-Form — hard-block dedup. Postgres treats
            // multiple NULLs as distinct, so we can keep nullable + unique
            // and only non-null values are enforced unique.
            $table->string('student_cnic', 20)->nullable()->after('email');

            // Parent CNIC — optional, intentionally NOT unique because
            // a single parent may apply for multiple children.
            $table->string('parent_cnic', 20)->nullable()->after('mother_name');
        });

        // Add the unique index in a separate statement so we can use
        // Postgres-specific behaviour cleanly. (A simple unique index
        // on a nullable column is sufficient — NULLs are allowed.)
        Schema::table('student_applications', function (Blueprint $table) {
            $table->unique('student_cnic', 'student_applications_student_cnic_unique');
            $table->index('parent_cnic', 'student_applications_parent_cnic_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_applications', function (Blueprint $table) {
            $table->dropUnique('student_applications_student_cnic_unique');
            $table->dropIndex('student_applications_parent_cnic_idx');
            $table->dropColumn(['student_cnic', 'parent_cnic']);
        });
    }
};
