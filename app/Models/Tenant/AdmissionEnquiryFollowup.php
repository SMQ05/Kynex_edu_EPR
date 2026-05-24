<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionEnquiryFollowup extends Model
{
    use HasUlids, TracksCreator;

    protected $fillable = [
        'enquiry_id',
        'follow_up_date',
        'next_follow_up_date',
        'response',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date'      => 'date',
            'next_follow_up_date' => 'date',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(AdmissionEnquiry::class, 'enquiry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }
}
