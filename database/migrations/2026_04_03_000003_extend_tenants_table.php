<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: extend_tenants_table
 *
 * Adds SaaS-specific columns to the Stancl Tenancy tenants table.
 * The base tenants table only has: id (string), timestamps, data (json).
 * This migration extends it with subscription, communication, AI, and
 * school metadata columns needed for the KynexEdu platform.
 *
 * All monetary values are stored in PAISAS (1 PKR = 100 paisas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // ── Subscription ─────────────────────────────────────
            // Links to subscription_plans table (ULID)
            $table->ulid('plan_id')->nullable()->after('id');
            $table->foreign('plan_id')
                  ->references('id')
                  ->on('subscription_plans')
                  ->nullOnDelete();

            $table->string('status')
                  ->default('trial')
                  ->after('plan_id');

            // ── School Information ───────────────────────────────
            $table->string('school_name')->after('status');
            $table->string('admin_name')->after('school_name');
            $table->string('admin_email')->after('admin_name');
            $table->string('admin_whatsapp')->nullable()->after('admin_email');
            $table->string('owner_whatsapp')->nullable()->after('admin_whatsapp');

            // ── Billing Period ───────────────────────────────────
            $table->timestamp('trial_ends_at')->nullable()->after('owner_whatsapp');
            $table->date('current_period_start')->nullable()->after('trial_ends_at');
            $table->date('current_period_end')->nullable()->after('current_period_start');

            // ── Usage Tracking ───────────────────────────────────
            $table->unsignedInteger('active_student_count')->default(0)->after('current_period_end');
            $table->unsignedInteger('storage_used_mb')->default(0)->after('active_student_count');

            // ── WhatsApp Channel Configuration ───────────────────
            // Determines which WhatsApp API provider the tenant uses
            // 'evolution' = Evolution API (https://github.com/EvolutionAPI/evolution-api)
            $table->string('whatsapp_channel')
                  ->default('none')
                  ->after('storage_used_mb');
            $table->json('whatsapp_config')->nullable()->after('whatsapp_channel');

            // ── SMS Channel Configuration ────────────────────────
            // Determines which SMS gateway the tenant uses
            // 'android_sms_gateway' = capcom6/android-sms-gateway (https://github.com/capcom6/android-sms-gateway)
            $table->string('sms_channel')
                  ->default('none')
                  ->after('whatsapp_config');
            $table->json('sms_config')->nullable()->after('sms_channel');

            // ── AI Configuration ─────────────────────────────────
            // Uses OpenRouter (https://openrouter.ai/) as the AI gateway
            $table->boolean('ai_enabled')->default(false)->after('sms_config');
            $table->string('ai_model')->default('openai/gpt-4o-mini')->after('ai_enabled');
            $table->unsignedBigInteger('ai_monthly_budget_paisas')->default(0)->after('ai_model');
            $table->unsignedBigInteger('ai_used_this_month_paisas')->default(0)->after('ai_monthly_budget_paisas');

            // ── Internal Admin Notes ─────────────────────────────
            $table->text('internal_notes')->nullable()->after('ai_used_this_month_paisas');
            $table->text('suspended_reason')->nullable()->after('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['plan_id']);

            // Drop all added columns
            $table->dropColumn([
                'plan_id',
                'status',
                'school_name',
                'admin_name',
                'admin_email',
                'admin_whatsapp',
                'owner_whatsapp',
                'trial_ends_at',
                'current_period_start',
                'current_period_end',
                'active_student_count',
                'storage_used_mb',
                'whatsapp_channel',
                'whatsapp_config',
                'sms_channel',
                'sms_config',
                'ai_enabled',
                'ai_model',
                'ai_monthly_budget_paisas',
                'ai_used_this_month_paisas',
                'internal_notes',
                'suspended_reason',
            ]);
        });
    }
};
