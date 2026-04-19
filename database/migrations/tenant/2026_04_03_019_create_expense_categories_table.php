<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->ulid('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Self-referencing FK must be added after the table (and its PK) exists — required by PostgreSQL
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('expense_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
