<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — Event + Calendar (Infix "Communicate → Event").
 * School events shown in a calendar/agenda view. Distinct from Notices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('audience', 30)->default('all'); // all|students|parents|staff|teachers
            $table->string('color', 20)->default('#1a56db');
            $table->boolean('is_published')->default(true);
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['start_at']);
            $table->index(['audience', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
