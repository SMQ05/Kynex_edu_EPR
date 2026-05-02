<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 15C.1 — Add custom domain support columns to the domains table.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('is_primary')->default(true)->after('tenant_id');
            $table->boolean('is_verified')->default(false)->after('is_primary');
            $table->string('verification_token', 64)->nullable()->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('verification_token');
            $table->string('domain_type', 20)->default('subdomain')->after('verified_at');
        });

        // Back-fill existing subdomain rows as verified primaries
        DB::table('domains')
            ->where(function ($query) {
                $query->where('domain', 'like', '%.kynexedu.com')
                      ->orWhere('domain', 'like', '%.localhost');
            })
            ->update([
                'is_primary'   => true,
                'is_verified'  => true,
                'verified_at'  => now(),
                'domain_type'  => 'subdomain',
            ]);
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'is_primary',
                'is_verified',
                'verification_token',
                'verified_at',
                'domain_type',
            ]);
        });
    }
};
