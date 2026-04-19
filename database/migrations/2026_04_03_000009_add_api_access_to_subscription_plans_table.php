<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_api_access_to_subscription_plans_table
 *
 * Adds api_access boolean to subscription_plans table
 * to gate API endpoints for Enterprise-tier tenants only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('api_access')->default(false)->after('is_custom');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('api_access');
        });
    }
};
