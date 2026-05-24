<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-panel + login-screen appearance/theme settings (Infix "Style").
 * Single-row config. Separate from CmsSetting (public website theme) and
 * from Phase 8 generic settings.
 */
class AppearanceSetting extends Model
{
    use HasUlids, TracksCreator;

    public const SIDEBAR_STYLES = [
        'default' => 'Default',
        'dark'    => 'Dark',
        'light'   => 'Light',
        'compact' => 'Compact',
    ];

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'sidebar_style',
        'login_background_path',
        'login_background_color',
        'panel_background_path',
        'panel_background_color',
        'font_family',
        'dark_mode_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dark_mode_default' => 'boolean',
        ];
    }

    /** Fetch (or lazily create) the single config row. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'primary_color'   => '#1a56db',
            'secondary_color' => '#7e3af2',
            'sidebar_style'   => 'default',
        ]);
    }
}
