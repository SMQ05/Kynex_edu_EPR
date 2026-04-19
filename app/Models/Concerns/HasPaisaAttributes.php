<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Trait HasPaisaAttributes
 *
 * Provides helper accessors that convert paisa integer columns
 * to human-readable PKR currency strings.
 *
 * Usage in any model:
 *   use HasPaisaAttributes;
 *
 * Then call: $model->getPriceInPkr('base_price_paisas')
 * Or use the generic accessor: $model->price_in_pkr (if the model defines a 'total_paisas' column)
 */
trait HasPaisaAttributes
{
    /**
     * Convert a paisa column value to a formatted PKR string.
     *
     * Example: 150000 → "PKR 1,500.00"
     */
    public function getPriceInPkr(string $column): string
    {
        $paisas = (int) ($this->{$column} ?? 0);

        return 'PKR ' . number_format($paisas / 100, 2);
    }

    /**
     * Generic accessor for the "total_paisas" column.
     * Override in individual models if the primary money column differs.
     */
    public function getPriceInPkrAttribute(): string
    {
        // Default to 'total_paisas' — models can override this method
        $column = $this->primaryPaisaColumn ?? 'total_paisas';

        return $this->getPriceInPkr($column);
    }
}
