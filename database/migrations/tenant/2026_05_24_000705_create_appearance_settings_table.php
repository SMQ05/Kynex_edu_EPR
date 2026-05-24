<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Style / Theme (Infix "Style → Background Settings / Color Theme").
 * Single-row appearance config for the admin panel + login screen. Kept
 * separate from CmsSetting (public website) and Phase 8 generic settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appearance_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('primary_color', 20)->default('#1a56db');
            $table->string('secondary_color', 20)->default('#7e3af2');
            $table->string('sidebar_style', 20)->default('default'); // default|dark|light|compact
            $table->string('login_background_path')->nullable();
            $table->string('login_background_color', 20)->nullable();
            $table->string('panel_background_path')->nullable();
            $table->string('panel_background_color', 20)->nullable();
            $table->string('font_family')->nullable();
            $table->boolean('dark_mode_default')->default(false);
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appearance_settings');
    }
};
