<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1.5 — Add cert provisioning state columns to the domains table.
     *
     * Allowed cert_status values (enforced in Job/Service, not DB):
     *   pending | issuing | issued | failed | rate_limited | dns_mismatch
     *   | lock_timeout
     *
     * `lock_timeout` is added for the case where the in-container
     * /var/lock/nginx-mutate.lock cannot be acquired within the script's
     * timeout. Treated like rate_limited (no retry-burn; renewal sweep
     * picks it up later).
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('cert_status', 20)->default('pending')->after('domain_type');
            $table->timestamp('cert_issued_at')->nullable()->after('cert_status');
            $table->timestamp('cert_expires_at')->nullable()->after('cert_issued_at');
            $table->text('cert_last_error')->nullable()->after('cert_expires_at');
            $table->unsignedSmallInteger('cert_attempt_count')->default(0)->after('cert_last_error');

            $table->index(['cert_status', 'cert_expires_at'], 'domains_cert_sweep_idx');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_cert_sweep_idx');
            $table->dropColumn([
                'cert_status',
                'cert_issued_at',
                'cert_expires_at',
                'cert_last_error',
                'cert_attempt_count',
            ]);
        });
    }
};
