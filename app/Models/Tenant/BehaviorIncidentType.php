<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Behaviour incident CATALOG entry: a reusable incident type with default
 * points + severity. New reference table; does not touch the existing
 * `behavior_incidents` table or BehaviorIncidentResource.
 *
 * NB: class name collides with App\Enums\BehaviorIncidentType (an enum). This
 * Eloquent model lives under App\Models\Tenant\ so the FQCNs differ — always
 * import the right namespace.
 */
class BehaviorIncidentType extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const NATURES = [
        'positive' => 'Positive (merit)',
        'negative' => 'Negative (demerit)',
        'neutral'  => 'Neutral',
    ];

    public const SEVERITIES = [
        'minor'    => 'Minor',
        'moderate' => 'Moderate',
        'major'    => 'Major',
        'severe'   => 'Severe',
    ];

    protected $table = 'behavior_incident_types';

    protected $fillable = [
        'name',
        'nature',
        'severity',
        'default_points',
        'description',
        'default_action',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'default_points' => 'integer',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
