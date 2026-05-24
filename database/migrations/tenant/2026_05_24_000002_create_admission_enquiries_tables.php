<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admission Query (enquiry / lead) tracking + follow-up history.
 * Distinct from the full admission/application pipeline — this is the
 * prospective-parent enquiry log with AI lead-scoring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_enquiries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable(); // what they asked / requirement
            $table->ulid('interested_class_id')->nullable();
            $table->ulid('source_id')->nullable();   // front_office_references (source)
            $table->unsignedSmallInteger('number_of_children')->nullable();
            $table->ulid('assigned_to')->nullable();
            $table->date('enquiry_date')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->string('status', 20)->default('active'); // active|won|lost|dead|passive
            $table->unsignedTinyInteger('lead_score')->nullable(); // 0-100 (AI)
            $table->string('lead_band', 10)->nullable();          // low|medium|high
            $table->text('note')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('interested_class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('source_id')->references('id')->on('front_office_references')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('school_users')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['status', 'next_follow_up_date']);
        });

        Schema::create('admission_enquiry_followups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('enquiry_id');
            $table->date('follow_up_date');
            $table->date('next_follow_up_date')->nullable();
            $table->text('response')->nullable();
            $table->text('note')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('enquiry_id')->references('id')->on('admission_enquiries')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['enquiry_id', 'follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_enquiry_followups');
        Schema::dropIfExists('admission_enquiries');
    }
};
