<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Login Permission + Due-Fees Login Permission (Infix
 * "Role & Permission → Login Permission"). Storage + config UI only;
 * enforcement is reported, NOT wired into auth middleware here.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Which roles may log in.
        Schema::create('login_permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('role');               // role name (Spatie role name)
            $table->boolean('can_login')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['role']);
        });

        // Block login when a student/guardian has overdue fees (single-row config).
        Schema::create('due_fees_login_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('grace_days')->default(0);
            $table->string('applies_to', 30)->default('students'); // students|guardians|both
            $table->unsignedBigInteger('min_due_paisas')->default(0); // only block if due >= this
            $table->text('block_message')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('due_fees_login_settings');
        Schema::dropIfExists('login_permissions');
    }
};
