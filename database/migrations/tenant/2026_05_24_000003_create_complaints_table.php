<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front-office complaint register with AI auto-categorisation + urgency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('complainant_name');
            $table->string('contact', 30)->nullable();
            $table->string('email')->nullable();
            $table->ulid('complaint_type_id')->nullable(); // front_office_references (complaint_type)
            $table->ulid('source_id')->nullable();          // front_office_references (source)
            $table->date('complaint_date');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('urgency', 10)->nullable();   // low|medium|high (AI)
            $table->string('sentiment', 10)->nullable(); // positive|neutral|negative (AI)
            $table->ulid('assigned_to')->nullable();
            $table->string('status', 20)->default('open'); // open|in_progress|resolved|closed
            $table->timestamp('resolved_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('note')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_type_id')->references('id')->on('front_office_references')->nullOnDelete();
            $table->foreign('source_id')->references('id')->on('front_office_references')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['status', 'complaint_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
