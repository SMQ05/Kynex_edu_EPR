<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('audit_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scope', 20);                        // all|tenant|role|url
            $table->string('tenant_slug', 120)->nullable();
            $table->string('role', 60)->nullable();
            $table->text('url')->nullable();
            $table->string('status', 20)->default('running');   // running|complete|timeout|incomplete|error
            $table->boolean('saas_skipped')->default(false);
            $table->string('saas_skip_reason', 80)->nullable(); // env_missing|flag|null
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('total_pages')->default(0);
            $table->integer('findings_critical')->default(0);
            $table->integer('findings_high')->default(0);
            $table->integer('findings_medium')->default(0);
            $table->integer('findings_low')->default(0);
            $table->integer('findings_info')->default(0);
            $table->text('last_url_crawled')->nullable();
            $table->string('last_role_crawled', 60)->nullable();
            $table->text('json_report_path')->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('audit_runs');
    }
};
