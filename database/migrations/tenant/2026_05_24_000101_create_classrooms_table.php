<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical classroom / lab / hall registry (Infix "Class Room"). Distinct
 * from Section.room_number (a free-text label) — this is a managed list of
 * real rooms with capacity, usable by routines and exam seat plans later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->string('room_type', 30)->default('classroom'); // classroom|lab|hall|library|other
            $table->unsignedInteger('capacity')->nullable();
            $table->string('building')->nullable();
            $table->string('floor', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['is_active', 'room_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
