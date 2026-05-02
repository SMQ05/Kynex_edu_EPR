<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_sliders', function (Blueprint $t) {
            $t->string('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cms_sliders', function (Blueprint $t) {
            $t->string('image_path')->nullable(false)->change();
        });
    }
};
