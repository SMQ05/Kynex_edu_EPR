<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_subscription_plans_table
 *
 * Central database table for subscription/pricing plans.
 * All monetary values are stored in PAISAS (1 PKR = 100 paisas)
 * to avoid floating-point precision issues.
 *
 * Billing types:
 *   - per_student : charged based on active student count
 *   - per_school  : flat fee regardless of students
 *   - hybrid      : base fee + per-student surcharge
 *
 * Uses ULID primary keys for sortable, unique identifiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            // ── Primary Key ──────────────────────────────────────
            $table->ulid('id')->primary();

            // ── Plan Identity ────────────────────────────────────
            $table->string('name');                       // e.g. "Starter", "Professional"
            $table->string('slug')->unique();             // e.g. "starter", "professional"
            $table->text('description')->nullable();      // Marketing description

            // ── Billing Configuration ────────────────────────────
            $table->string('billing_type')->default('per_school');

            // ── Pricing (all in paisas — 1 PKR = 100 paisas) ────
            $table->unsignedBigInteger('base_price_paisas')->default(0);        // Monthly base price
            $table->unsignedBigInteger('per_student_price_paisas')->default(0); // Per-student monthly charge
            $table->unsignedBigInteger('setup_fee_paisas')->default(0);         // One-time setup fee

            // ── Resource Limits ──────────────────────────────────
            $table->unsignedInteger('max_students')->default(0);    // 0 = unlimited
            $table->unsignedInteger('max_staff')->default(0);       // 0 = unlimited
            $table->unsignedInteger('max_campuses')->default(1);    // Multi-campus support
            $table->unsignedInteger('storage_limit_gb')->default(5); // Cloud storage cap

            // ── Feature Modules ──────────────────────────────────
            // JSON array of enabled module slugs, e.g. ["attendance","fees","exams"]
            $table->json('enabled_modules')->nullable();

            // ── Trial Configuration ──────────────────────────────
            $table->unsignedInteger('trial_days')->default(14);

            // ── Flags ────────────────────────────────────────────
            $table->boolean('is_active')->default(true);    // Available for new signups
            $table->boolean('is_featured')->default(false); // Highlighted in pricing page
            $table->boolean('is_custom')->default(false);   // Custom/enterprise plan

            // ── Display Order ────────────────────────────────────
            $table->unsignedInteger('sort_order')->default(0);

            // ── Timestamps & Soft Deletes ────────────────────────
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
