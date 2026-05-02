<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('template_type')->default('custom');
            $table->longText('html_template');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('generated_certificates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->ulid('template_id');
            $table->string('certificate_number', 50)->unique();
            $table->date('issued_date');
            $table->ulid('issued_by');
            $table->json('variables_used')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('certificate_templates')->cascadeOnDelete();
            $table->foreign('issued_by')->references('id')->on('school_users')->cascadeOnDelete();
        });

        Schema::create('id_card_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('card_type')->default('student');
            $table->longText('html_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_templates');
        Schema::dropIfExists('generated_certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
