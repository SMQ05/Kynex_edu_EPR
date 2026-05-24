<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsMenuItem extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'cms_menu_id',
        'parent_id',
        'label',
        'url',
        'target',
        'sort',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort'      => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'cms_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsMenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'parent_id')->orderBy('sort');
    }
}
