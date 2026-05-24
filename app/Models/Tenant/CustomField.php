<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admin-defined dynamic field attached to a registration entity
 * (student or staff). Values are stored in custom_field_values.
 */
class CustomField extends Model
{
    use HasUlids, SoftDeletes, TracksCreator;

    public const ENTITIES = [
        'student' => 'Student',
        'staff'   => 'Staff',
    ];

    public const TYPES = [
        'text'     => 'Text',
        'number'   => 'Number',
        'date'     => 'Date',
        'select'   => 'Dropdown (select)',
        'textarea' => 'Paragraph (textarea)',
        'toggle'   => 'Yes / No (toggle)',
    ];

    protected $fillable = [
        'entity',
        'label',
        'key',
        'type',
        'options',
        'required',
        'help_text',
        'sort',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options'   => 'array',
            'required'  => 'boolean',
            'is_active' => 'boolean',
            'sort'      => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /**
     * Active definitions for an entity, ordered for rendering.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int,CustomField>
     */
    public static function activeFor(string $entity)
    {
        return static::query()
            ->where('entity', $entity)
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('label')
            ->get();
    }
}
