<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-school feature flags (Infix "Module Manager"). Complements SaaS
 * plan flags — lets a school enable/disable optional modules.
 */
class ModuleToggle extends Model
{
    use HasUlids, TracksCreator;

    /** Known optional modules (key => default label). Seeds the manager UI. */
    public const KNOWN_MODULES = [
        'chat'        => 'Chat (User-to-User)',
        'events'      => 'Events & Calendar',
        'library'     => 'Library',
        'hostel'      => 'Hostel / Dormitory',
        'transport'   => 'Transport',
        'inventory'   => 'Inventory',
        'cafeteria'   => 'Cafeteria',
        'wallet'      => 'Student Wallet',
        'online_class'=> 'Online Classes',
        'behaviour'   => 'Behaviour Records',
    ];

    protected $fillable = [
        'module_key',
        'label',
        'enabled',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * Whether a module is enabled. Defaults to true when no row exists
     * (fail-open: only modules you turn off are hidden).
     */
    public static function isEnabled(string $moduleKey): bool
    {
        $row = static::query()->where('module_key', $moduleKey)->first();

        return $row ? (bool) $row->enabled : true;
    }
}
