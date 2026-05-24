<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum personal access tokens — TENANT-scoped.
 *
 * SchoolUser (the tokenable) lives in the tenant DB and uses ULID keys, so
 * this table belongs in the tenant migration set and uses ulidMorphs (not the
 * default bigint morphs from Sanctum's published migration, which we suppress
 * via Sanctum::ignoreMigrations() in AppServiceProvider).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->ulidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
