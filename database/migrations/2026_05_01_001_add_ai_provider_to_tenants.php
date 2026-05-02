<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants can now specify which AI provider their per-school key is for.
 * Defaults to openrouter (the existing behaviour).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenants', 'ai_provider')) {
                $table->string('ai_provider', 32)->default('openrouter')->after('ai_model');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('tenants', 'ai_provider')) {
                $table->dropColumn('ai_provider');
            }
        });
    }
};
