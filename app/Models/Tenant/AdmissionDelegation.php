<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\SchoolUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDelegation extends Model
{
    use HasUlids;

    protected $fillable = [
        'school_user_id',
        'permission',
        'class_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'school_user_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
