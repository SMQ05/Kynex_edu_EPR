<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add per-school OpenRouter API key to tenants table.
 *
 * Schools can have their own API key (separate billing) or leave it
 * null to fall back to the platform-wide key from PlatformApiSetting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->text('ai_openrouter_api_key')->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('ai_openrouter_api_key');
        });
    }
};
