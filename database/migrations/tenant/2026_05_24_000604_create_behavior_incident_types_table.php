<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Behaviour enhancement (Infix "Behaviour Records → Settings"): an incident
 * CATALOG of reusable incident types with default points/severity. This is a
 * NEW reference table — the existing `behavior_incidents` table & resource are
 * left untouched (a future link would add an optional `incident_type_id` FK to
 * behavior_incidents; reported, not done here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavior_incident_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('nature', 20)->default('negative'); // positive|negative|neutral
            $table->string('severity', 20)->default('minor');   // minor|moderate|major|severe
            $table->integer('default_points')->default(0);      // +ve for merits, -ve for demerits
            $table->text('description')->nullable();
            $table->string('default_action')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['nature', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavior_incident_types');
    }
};
