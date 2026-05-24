<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student groups (houses, clubs, remedial groups, teams) + membership.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type', 30)->default('general'); // house|club|remedial|team|general
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index('type');
        });

        Schema::create('student_group_members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('student_group_id');
            $table->ulid('student_id');
            $table->timestamps();

            $table->foreign('student_group_id')->references('id')->on('student_groups')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['student_group_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_group_members');
        Schema::dropIfExists('student_groups');
    }
};
