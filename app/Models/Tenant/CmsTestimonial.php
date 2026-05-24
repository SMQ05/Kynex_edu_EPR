<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsTestimonial extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    protected $fillable = [
        'name',
        'role',
        'photo_path',
        'quote',
        'rating',
        'sort',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rating'    => 'integer',
            'sort'      => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
