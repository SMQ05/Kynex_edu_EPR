<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('school_user_id');
            $table->ulid('leave_type_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->tinyInteger('total_days');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->ulid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
