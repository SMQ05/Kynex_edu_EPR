<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Download Center (Infix "Download Center": Content Type / Content List /
 * Shared Content List / Video List). One `content_types` catalog + one
 * `download_contents` table; "Shared Content" and "Video List" are FILTERED
 * VIEWS (is_shared flag / is_video flag), not separate tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug', 80)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index('is_active');
        });

        Schema::create('download_contents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('content_type_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_type', 10)->default('file'); // file|url|video
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->boolean('is_video')->default(false);
            $table->boolean('is_shared')->default(false); // surfaced on the "Shared Content" tab
            $table->string('audience', 20)->default('all'); // all|students|staff|guardians
            $table->date('publish_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('download_count')->default(0);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('content_type_id')->references('id')->on('content_types')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['is_published', 'is_shared']);
            $table->index('is_video');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_contents');
        Schema::dropIfExists('content_types');
    }
};
