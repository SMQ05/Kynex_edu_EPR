<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Book Categories ──────────────────────────────────────────
        Schema::create('book_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Books ────────────────────────────────────────────────────
        Schema::create('books', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('category_id')->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->year('edition_year')->nullable();
            $table->text('description')->nullable();
            $table->string('rack_number')->nullable();
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
            $table->unsignedInteger('price_paisas')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('book_categories')
                ->nullOnDelete();

            $table->index('isbn');
            $table->index('title');
        });

        // ── Library Members ──────────────────────────────────────────
        Schema::create('library_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('member_type'); // student, staff
            $table->ulid('student_id')->nullable();
            $table->ulid('school_user_id')->nullable();
            $table->string('library_card_number')->unique();
            $table->unsignedInteger('max_books_allowed')->default(3);
            $table->date('membership_start');
            $table->date('membership_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->nullOnDelete();

            $table->foreign('school_user_id')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();
        });

        // ── Book Issues ──────────────────────────────────────────────
        Schema::create('book_issues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('book_id');
            $table->ulid('library_member_id');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('issued');
            $table->unsignedInteger('fine_paisas')->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->text('remarks')->nullable();
            $table->ulid('issued_by')->nullable();
            $table->ulid('returned_to')->nullable();
            $table->timestamps();

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();

            $table->foreign('library_member_id')
                ->references('id')
                ->on('library_members')
                ->cascadeOnDelete();

            $table->foreign('issued_by')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();

            $table->foreign('returned_to')
                ->references('id')
                ->on('school_users')
                ->nullOnDelete();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_categories');
    }
};
