<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsMenu extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const LOCATIONS = [
        'header'  => 'Header',
        'footer'  => 'Footer',
        'sidebar' => 'Sidebar',
    ];

    protected $fillable = [
        'name',
        'location',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class)->orderBy('sort');
    }

    /** Top-level items (no parent), each with nested children, for rendering. */
    public function rootItems(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort');
    }
}
