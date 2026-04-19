<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CmsSetting extends Model
{
    use HasUlids;

    protected $fillable = [
        'school_name', 'tagline', 'logo_path', 'favicon_path',
        'address', 'phone', 'email', 'whatsapp',
        'facebook_url', 'twitter_url', 'instagram_url', 'youtube_url',
        'primary_color', 'about_text',
        'principal_message', 'principal_name', 'principal_photo_path',
        'admission_open', 'admission_form_url',
    ];

    protected function casts(): array
    {
        return [
            'admission_open' => 'boolean',
        ];
    }

    /**
     * Get or create the singleton CMS settings record.
     */
    public static function getSettings(): self
    {
        $settings = static::first();
        if (! $settings) {
            $tenant = tenancy()->tenant;
            $settings = static::create([
                'school_name' => $tenant?->school_name ?? 'My School',
            ]);
        }
        return $settings;
    }
}
