<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Front-office phone call log (incoming / outgoing) with follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_call_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('call_type', 10)->default('incoming'); // incoming|outgoing
            $table->date('call_date');
            $table->time('call_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('purpose')->nullable();
            $table->text('description')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 20)->default('completed'); // completed|follow_up|pending
            $table->text('note')->nullable();
            $table->ulid('campus_id')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
            $table->index(['call_type', 'call_date']);
            $table->index(['status', 'follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_call_logs');
    }
};
