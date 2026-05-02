<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_saas_admins_table
 *
 * Central database table for SaaS platform administrators.
 * These are the people who manage the multi-tenant platform itself
 * (superadmins and support staff), NOT school-level users.
 *
 * Uses ULID primary keys for sortable, unique identifiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_admins', function (Blueprint $table) {
            // ── Primary Key ──────────────────────────────────────
            $table->ulid('id')->primary();

            // ── Profile ──────────────────────────────────────────
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // ── Role & Status ────────────────────────────────────
            // superadmin = full platform access
            // support    = limited access for customer support tasks
            $table->string('role')->default('support');
            $table->boolean('is_active')->default(true);

            // ── Audit ────────────────────────────────────────────
            $table->timestamp('last_login_at')->nullable();

            // ── Timestamps & Soft Deletes ────────────────────────
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_admins');
    }
};
