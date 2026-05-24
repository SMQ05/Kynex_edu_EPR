<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_menus', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('location')->nullable(); // header|footer|...
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });

        Schema::create('cms_menu_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('cms_menu_id');
            $table->ulid('parent_id')->nullable();
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('target')->default('_self'); // _self|_blank
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cms_menu_id', 'parent_id', 'sort']);
            $table->foreign('cms_menu_id')->references('id')->on('cms_menus')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('cms_menu_items')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_menu_items');
        Schema::dropIfExists('cms_menus');
    }
};
