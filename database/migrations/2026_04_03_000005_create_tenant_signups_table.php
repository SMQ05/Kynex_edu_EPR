<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_tenant_signups_table
 *
 * Central database table for prospective tenant/school signup requests.
 * This is a CRM-lite table that tracks schools interested in the platform
 * through the signup pipeline: new → contacted → invited → onboarded.
 *
 * Uses ULID primary keys for sortable, unique identifiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_signups', function (Blueprint $table) {
            // ── Primary Key ──────────────────────────────────────
            $table->ulid('id')->primary();

            // ── Contact Information ──────────────────────────────
            $table->string('school_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp_number')->nullable();

            // ── Location ─────────────────────────────────────────
            $table->string('city')->nullable();
            $table->string('country')->default('PK');   // ISO 3166-1 alpha-2

            // ── Inquiry Details ──────────────────────────────────
            $table->text('message')->nullable();

            // ── Pipeline Status ──────────────────────────────────
            // new       = just submitted the form
            // contacted = support team has reached out
            // invited   = invitation sent to onboard
            // onboarded = tenant created, fully set up
            // rejected  = not a good fit / spam
            $table->string('status')->default('new');

            // ── Pipeline Timestamps ──────────────────────────────
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('onboarded_at')->nullable();

            // ── Internal Notes ───────────────────────────────────
            $table->text('internal_notes')->nullable();

            // ── Timestamps ───────────────────────────────────────
            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_signups');
    }
};
