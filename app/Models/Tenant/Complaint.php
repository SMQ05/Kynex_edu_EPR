<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const STATUSES = [
        'open'        => 'Open',
        'in_progress' => 'In Progress',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
    ];

    protected $fillable = [
        'complainant_name',
        'contact',
        'email',
        'complaint_type_id',
        'source_id',
        'complaint_date',
        'description',
        'action_taken',
        'urgency',
        'sentiment',
        'assigned_to',
        'status',
        'resolved_at',
        'attachment_path',
        'note',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'complaint_date' => 'date',
            'resolved_at'    => 'datetime',
        ];
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(FrontOfficeReference::class, 'complaint_type_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(FrontOfficeReference::class, 'source_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
