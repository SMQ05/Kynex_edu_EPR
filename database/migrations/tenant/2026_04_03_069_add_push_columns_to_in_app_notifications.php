<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->timestamp('push_sent_at')->nullable()->after('read_at');
            $table->timestamp('push_delivered_at')->nullable()->after('push_sent_at');
            $table->timestamp('fallback_sent_at')->nullable()->after('push_delivered_at');
            $table->string('fallback_channel')->nullable()->after('fallback_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropColumn([
                'push_sent_at',
                'push_delivered_at',
                'fallback_sent_at',
                'fallback_channel',
            ]);
        });
    }
};
