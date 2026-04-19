<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_school_invitations_table
 *
 * Stores invitation/set-password tokens for:
 *  - School self-registration (email verification → set password)
 *  - Admin-invited schools, school admins, owners, multi-institute owners
 *
 * Flow:
 *  1. School registers → invitation row created (type=school_signup) → email verification sent
 *  2. School clicks verify link → invitation row updated (email_verified_at set)
 *  3. School sets password → tenant provisioned, invitation consumed
 *
 *  OR
 *
 *  1. Admin creates tenant/user → invitation row created (type=admin_invite) → set-password link sent
 *  2. User clicks link → sets password → account activated
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // ── Who this invitation is for ────────────────────────
            $table->string('school_name');
            $table->string('contact_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();

            // ── Invitation type ───────────────────────────────────
            // school_signup      = school self-registered on landing page
            // admin_invite       = admin invited a school/user
            $table->string('type')->default('school_signup');

            // ── Token (for email verification + set-password) ─────
            $table->string('token', 64)->unique()->index();

            // ── Expiry (3 hours) ──────────────────────────────────
            $table->timestamp('expires_at');

            // ── State ─────────────────────────────────────────────
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();   // password was set

            // ── Link to tenant (set after provisioning) ───────────
            $table->string('tenant_id')->nullable();        // Stancl tenant ID

            // ── Metadata (for pre-filling) ────────────────────────
            // JSON: { role: 'admin|owner|multi_owner', plan_id: '...' }
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['token', 'expires_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_invitations');
    }
};
