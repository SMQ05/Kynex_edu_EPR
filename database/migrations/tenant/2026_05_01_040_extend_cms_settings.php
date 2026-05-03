<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing fields a real school website needs: vision,
 * mission, why-choose-us, stats, facilities, testimonials, exam
 * highlights, and admission process steps. All optional, all stored
 * as JSON where they are list-shaped, so the admin UI can render them
 * as Repeaters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            foreach (
                [
                    'vision_text', 'mission_text',
                    'why_choose_us', 'facilities', 'testimonials',
                    'stats', 'exam_highlights', 'admission_steps',
                    'hero_video_url', 'hero_image_path',
                    'about_image_path', 'address_map_iframe',
                ] as $col
            ) {
                if (! Schema::hasColumn('cms_settings', $col)) {
                    if (str_ends_with($col, '_text') || str_contains($col, '_image_path') || str_contains($col, '_url') || str_contains($col, '_iframe')) {
                        $table->text($col)->nullable();
                    } else {
                        $table->json($col)->nullable();
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_settings', function (Blueprint $table) {
            foreach ([
                'vision_text', 'mission_text', 'why_choose_us', 'facilities',
                'testimonials', 'stats', 'exam_highlights', 'admission_steps',
                'hero_video_url', 'hero_image_path', 'about_image_path', 'address_map_iframe',
            ] as $col) {
                if (Schema::hasColumn('cms_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
