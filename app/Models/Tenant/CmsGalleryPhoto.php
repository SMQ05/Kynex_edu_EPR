<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsGalleryPhoto extends Model
{
    use HasUlids;

    protected $fillable = [
        'album_id', 'title', 'image_path', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(CmsGalleryAlbum::class, 'album_id');
    }
}
