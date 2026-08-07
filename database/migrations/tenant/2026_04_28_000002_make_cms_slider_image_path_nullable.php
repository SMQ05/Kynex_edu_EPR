<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_sliders', function (Blueprint $t) {
            // Makes the image_path column optional
            $t->string('image_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CRITICAL: If you have NULL values in the DB, you must handle them 
        // before forcing the column to be NOT NULL.
        // Optional safety net: DB::table('cms_sliders')->whereNull('image_path')->update(['image_path' => '']);

        Schema::table('cms_sliders', function (Blueprint $t) {
            // Reverts the column to NOT NULL (default behavior when ->nullable() is omitted)
            $t->string('image_path')->nullable(false)->change();
        });
    }
};
