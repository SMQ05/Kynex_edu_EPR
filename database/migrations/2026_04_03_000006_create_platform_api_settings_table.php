<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_platform_api_settings_table
 *
 * Stores platform-level API credentials and configuration for external
 * services used across all tenants. This is a key-value settings store
 * grouped by service type.
 *
 * Service groups:
 *   - sms       : Android SMS Gateway (capcom6/android-sms-gateway)
 *   - whatsapp  : Evolution API (EvolutionAPI/evolution-api)
 *   - ai        : OpenRouter AI (openrouter.ai)
 *
 * Sensitive values (tokens, keys) are encrypted at application level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_api_settings', function (Blueprint $table) {
            $table->id();

            // Service group: sms, whatsapp, ai
            $table->string('group')->index();

            // Setting key within the group (e.g. 'api_url', 'api_key', 'enabled')
            $table->string('key');

            // The value (encrypted for sensitive keys)
            $table->text('value')->nullable();

            // Whether this setting contains sensitive data (encrypted)
            $table->boolean('is_encrypted')->default(false);

            $table->timestamps();

            // Unique constraint: one key per group
            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_api_settings');
    }
};
