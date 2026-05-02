<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\TopicStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusTopic extends Model
{
    use HasUlids;

    protected $fillable = [
        'syllabus_id',
        'title',
        'description',
        'week_number',
        'planned_date',
        'completed_at',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status'       => TopicStatus::class,
            'planned_date' => 'date',
            'completed_at' => 'date',
            'week_number'  => 'integer',
            'sort_order'   => 'integer',
        ];
    }

    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }
}
