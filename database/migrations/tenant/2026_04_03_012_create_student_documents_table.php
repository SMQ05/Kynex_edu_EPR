<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->string('document_type')->default('other');
            $table->string('title');
            $table->string('file_path');
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->ulid('uploaded_by');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
