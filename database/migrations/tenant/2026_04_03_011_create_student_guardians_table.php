<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('student_id');
            $table->string('guardian_type')->default('guardian');
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('phone', 20);
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('cnic', 15)->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_access_portal')->default(false);
            $table->ulid('school_user_id')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
