<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_invoices_table
 *
 * Central database table for tenant billing invoices.
 * Each invoice represents a billing period for a tenant school.
 *
 * Invoice number format: KNX-{YYYY}-{MM}-{tenant_slug}
 * Example: KNX-2026-04-al-huda-school
 *
 * All monetary values are stored in PAISAS (1 PKR = 100 paisas)
 * to avoid floating-point precision issues.
 *
 * Uses ULID primary keys for sortable, unique identifiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            // ── Primary Key ──────────────────────────────────────
            $table->ulid('id')->primary();

            // ── Tenant Relationship ──────────────────────────────
            // References tenants.id which is a string (Stancl convention)
            $table->string('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // ── Invoice Identity ─────────────────────────────────
            // Format: KNX-2026-04-{tenant_slug}
            $table->string('invoice_number')->unique();

            // ── Billing Period ───────────────────────────────────
            $table->date('period_start');
            $table->date('period_end');

            // ── Usage Snapshot ────────────────────────────────────
            // Captures student count at time of invoice generation
            $table->unsignedInteger('active_student_count');

            // ── Line Items (all in paisas) ───────────────────────
            $table->unsignedBigInteger('base_amount_paisas')->default(0);       // Base subscription fee
            $table->unsignedBigInteger('per_student_amount_paisas')->default(0); // Per-student charges
            $table->unsignedBigInteger('sms_usage_paisas')->default(0);          // SMS gateway usage
            $table->unsignedBigInteger('whatsapp_usage_paisas')->default(0);     // WhatsApp API usage
            $table->unsignedBigInteger('ai_usage_paisas')->default(0);           // AI/LLM API usage
            $table->unsignedBigInteger('storage_overage_paisas')->default(0);    // Extra storage charges
            $table->unsignedBigInteger('discount_paisas')->default(0);           // Applied discount

            // ── Total ────────────────────────────────────────────
            $table->unsignedBigInteger('total_paisas');

            // ── Status Tracking ──────────────────────────────────
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            // ── Communication Tracking ───────────────────────────
            $table->timestamp('sent_via_whatsapp_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // ── Timestamps & Soft Deletes ────────────────────────
            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────
            $table->index('tenant_id');
            $table->index('status');
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
