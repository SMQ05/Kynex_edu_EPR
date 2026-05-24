<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\BelongsToCampus;
use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admission Query / enquiry (prospective-parent lead). Separate from the
 * formal StudentApplication pipeline — this is the top-of-funnel lead log
 * with AI lead scoring + follow-up history.
 */
class AdmissionEnquiry extends Model
{
    use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator;

    public const STATUSES = [
        'active'  => 'Active',
        'won'     => 'Won (Enrolled)',
        'lost'    => 'Lost',
        'dead'    => 'Dead',
        'passive' => 'Passive',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'description',
        'interested_class_id',
        'source_id',
        'number_of_children',
        'assigned_to',
        'enquiry_date',
        'next_follow_up_date',
        'status',
        'lead_score',
        'lead_band',
        'note',
        'campus_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enquiry_date'        => 'date',
            'next_follow_up_date' => 'date',
            'number_of_children'  => 'integer',
            'lead_score'          => 'integer',
        ];
    }

    public function interestedClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'interested_class_id');
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

    public function followups(): HasMany
    {
        return $this->hasMany(AdmissionEnquiryFollowup::class, 'enquiry_id')->latest('follow_up_date');
    }

    public function scopeDueForFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<=', today())
            ->whereIn('status', ['active', 'passive']);
    }
}
