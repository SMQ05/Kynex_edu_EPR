<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('audit_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('run_id')
                  ->constrained('audit_runs')
                  ->cascadeOnDelete();
            $table->string('finding_hash', 64);               // SHA-256(tenant|role|url|type|title)
            $table->string('tenant_slug', 120);
            $table->string('role', 60);
            $table->text('url');
            $table->string('finding_type', 40);
            // 5xx|4xx|js_error|network_failure|broken_image|malformed_image_url|
            // empty_image_src|filament_error_toast|form_500_on_validation|
            // slow_page|blank_page|security_violation|panel_chrome_missing|access_denied_ok
            $table->string('severity', 10);                   // critical|high|medium|low|info|ok
            $table->text('title');
            $table->jsonb('details')->default('{}');
            $table->text('screenshot_path')->nullable();
            $table->smallInteger('http_status')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('fixed_at')->nullable();
            $table->string('fixed_by_commit', 60)->nullable();
            $table->timestamps();

            $table->index('run_id');
            $table->index('finding_hash');
            $table->index('severity');
            $table->index(['tenant_slug', 'role']);
            $table->unique(['run_id', 'finding_hash']);
        });
    }

    /**
     * NOTE: audit_findings depends on audit_runs via FK with cascadeOnDelete.
     * Laravel's migration runner rolls back in reverse-chronological order, so
     * audit_findings (created 2026_05_08_002) is dropped before audit_runs
     * (created 2026_05_08_001). Don't reorder these timestamps.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('audit_findings');
    }
};
