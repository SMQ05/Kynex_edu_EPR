<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stored value of a CustomField for a single entity record
 * (a student or staff member, identified by entity + entity_id).
 */
class CustomFieldValue extends Model
{
    use HasUlids;

    protected $fillable = [
        'custom_field_id',
        'entity',
        'entity_id',
        'value',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
