<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Module Manager (Infix "Module Manager"): per-school feature
 * flags to enable/disable optional modules. Complements SaaS plan flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_toggles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('module_key');     // e.g. 'chat', 'library', 'hostel'
            $table->string('label')->nullable();
            $table->boolean('enabled')->default(true);
            $table->text('description')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->unique(['module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_toggles');
    }
};
