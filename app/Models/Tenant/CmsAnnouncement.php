<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsAnnouncement extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'title', 'content', 'is_published',
        'published_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeActive($query)
    {
        return $query->published()
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
