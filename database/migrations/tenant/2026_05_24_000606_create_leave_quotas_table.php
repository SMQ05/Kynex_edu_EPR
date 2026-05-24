<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave Define (Infix "Leave → Leave Define"): per-role leave QUOTAS so that
 * leave balances are defined. References the existing `leave_types` table.
 * `applies_to_role` is a Spatie role name (e.g. TEACHER); null = all roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_quotas', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('leave_type_id');
            $table->string('applies_to_role')->nullable(); // Spatie role name; null = everyone
            $table->ulid('academic_year_id')->nullable();
            $table->unsignedInteger('days_allowed');
            $table->string('period', 20)->default('yearly'); // yearly|monthly|term
            $table->boolean('carry_forward')->default(false);
            $table->unsignedInteger('max_carry_forward_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('leave_type_id')->references('id')->on('leave_types')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['leave_type_id', 'applies_to_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_quotas');
    }
};
