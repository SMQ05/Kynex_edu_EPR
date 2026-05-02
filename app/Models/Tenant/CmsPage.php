<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'is_published', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order'   => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
