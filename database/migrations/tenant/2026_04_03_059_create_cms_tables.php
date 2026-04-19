<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_name');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('primary_color', 7)->default('#1a56db');
            $table->text('about_text')->nullable();
            $table->text('principal_message')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('principal_photo_path')->nullable();
            $table->boolean('admission_open')->default(false);
            $table->string('admission_form_url')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('slug', 200)->unique();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cms_sliders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_path');
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_announcements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cms_gallery_albums', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('cms_gallery_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('album_id');
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('album_id')->references('id')->on('cms_gallery_albums')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_gallery_photos');
        Schema::dropIfExists('cms_gallery_albums');
        Schema::dropIfExists('cms_announcements');
        Schema::dropIfExists('cms_sliders');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('cms_settings');
    }
};
