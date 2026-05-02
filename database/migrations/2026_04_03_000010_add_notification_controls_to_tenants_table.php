<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_notification_controls_to_tenants_table
 *
 * Adds boolean toggles that allow the school admin to control which
 * notification channels are enabled for their school, and whether
 * teachers can use personal WhatsApp or send notifications directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // ── Notification Channel Toggles ─────────────────────────────
            // Allow the school admin to enable/disable each channel
            $table->boolean('allow_app_notifications')
                  ->default(true)
                  ->after('internal_notes')
                  ->comment('Enable in-app / database notifications');

            $table->boolean('allow_whatsapp')
                  ->default(true)
                  ->after('allow_app_notifications')
                  ->comment('Enable WhatsApp notifications');

            $table->boolean('allow_sms')
                  ->default(true)
                  ->after('allow_whatsapp')
                  ->comment('Enable SMS notifications');

            $table->boolean('allow_email')
                  ->default(true)
                  ->after('allow_sms')
                  ->comment('Enable email notifications');

            // ── Teacher Notification Permissions ─────────────────────────
            $table->boolean('teachers_can_use_own_whatsapp')
                  ->default(false)
                  ->after('allow_email')
                  ->comment('Allow teachers to send via their own personal WhatsApp number');

            $table->boolean('teachers_can_send_notifications')
                  ->default(true)
                  ->after('teachers_can_use_own_whatsapp')
                  ->comment('Allow teachers to use the NotificationComposer at all');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'allow_app_notifications',
                'allow_whatsapp',
                'allow_sms',
                'allow_email',
                'teachers_can_use_own_whatsapp',
                'teachers_can_send_notifications',
            ]);
        });
    }
};
