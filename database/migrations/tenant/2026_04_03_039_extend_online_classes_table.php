<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->timestamp('actual_start_at')->nullable()->after('status');
            $table->timestamp('actual_end_at')->nullable()->after('actual_start_at');
            $table->boolean('attendance_required')->default(false)->after('actual_end_at');
            $table->boolean('quiz_enabled')->default(false)->after('attendance_required');
            $table->unsignedInteger('max_participants')->nullable()->after('quiz_enabled');
            $table->tinyInteger('join_before_minutes')->default(5)->after('max_participants');
        });
    }

    public function down(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->dropColumn([
                'actual_start_at',
                'actual_end_at',
                'attendance_required',
                'quiz_enabled',
                'max_participants',
                'join_before_minutes',
            ]);
        });
    }
};
