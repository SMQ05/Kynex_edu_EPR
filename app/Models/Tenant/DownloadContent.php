<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Download Center content item. "Shared Content" and "Video List" are
 * filtered views of this one table (is_shared / is_video flags).
 */
class DownloadContent extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const AUDIENCES = [
        'all'       => 'Everyone',
        'students'  => 'Students',
        'staff'     => 'Staff',
        'guardians' => 'Guardians',
    ];

    protected $fillable = [
        'content_type_id',
        'title',
        'description',
        'source_type',
        'file_path',
        'external_url',
        'is_video',
        'is_shared',
        'audience',
        'publish_date',
        'is_published',
        'download_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_video'       => 'boolean',
            'is_shared'      => 'boolean',
            'is_published'   => 'boolean',
            'publish_date'   => 'date',
            'download_count' => 'integer',
        ];
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeVideos($query)
    {
        return $query->where('is_video', true);
    }
}
