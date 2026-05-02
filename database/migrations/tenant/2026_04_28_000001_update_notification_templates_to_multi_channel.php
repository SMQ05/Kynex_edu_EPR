<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_templates', function (Blueprint $t) {
            // Drop old single-channel columns
            $t->dropColumn('channel');
            $t->dropColumn('subject');
            $t->dropColumn('body');

            // Add multi-channel columns
            $t->string('event_trigger')->nullable()->after('slug');
            $t->json('channels')->nullable()->after('event_trigger');
            $t->json('send_to')->nullable()->after('channels');
            $t->text('sms_template')->nullable()->after('send_to');
            $t->text('whatsapp_template')->nullable()->after('sms_template');
            $t->string('email_subject')->nullable()->after('whatsapp_template');
            $t->text('email_body')->nullable()->after('email_subject');
        });
    }

    public function down(): void
    {
        Schema::table('notification_templates', function (Blueprint $t) {
            $t->dropColumn([
                'event_trigger', 'channels', 'send_to',
                'sms_template', 'whatsapp_template', 'email_subject', 'email_body',
            ]);

            $t->string('channel')->nullable()->after('slug');
            $t->string('subject')->nullable()->after('channel');
            $t->text('body')->nullable()->after('subject');
        });
    }
};
