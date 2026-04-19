<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiUsageLog — Central DB model for tracking AI usage per tenant.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $model_used
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $estimated_cost_paisas
 * @property string $feature_used
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiUsageLog extends Model
{
    use HasUlids;

    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'prompt_tokens'        => 'integer',
            'completion_tokens'    => 'integer',
            'estimated_cost_paisas' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
