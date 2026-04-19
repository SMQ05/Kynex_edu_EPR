<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_ai_usage_logs_table (CENTRAL DB)
 *
 * Tracks AI usage per tenant for billing purposes.
 * OpenRouter API is paid by KynexEdu centrally;
 * usage is billed to schools monthly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->cascadeOnDelete();

            $table->string('model_used');
            $table->unsignedInteger('prompt_tokens');
            $table->unsignedInteger('completion_tokens');
            $table->unsignedBigInteger('estimated_cost_paisas');

            // e.g. 'admission_assistant', 'parent_portal_guide', 'admin_overview'
            $table->string('feature_used');

            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
