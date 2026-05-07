<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * A safe alternative to plain enum casts. Unknown DB values return null
 * instead of crashing, and emit a deduplicated warning to the log so stale
 * data is visible without flooding.
 *
 * Usage in model casts():
 *   'exam_type' => AsEnum::of(ExamType::class),
 */
class AsEnum implements CastsAttributes
{
    /** Per-process dedup — prevents log flooding for repeated stale values */
    private static array $warned = [];

    public function __construct(private readonly string $enumClass) {}

    public static function of(string $enumClass): self
    {
        return new self($enumClass);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $enum = ($this->enumClass)::tryFrom($value);

        if ($enum === null) {
            $warnKey = $this->enumClass . ':' . $value;
            if (! array_key_exists($warnKey, self::$warned)) {
                self::$warned[$warnKey] = true;
                Log::warning('AsEnum: unrecognized value — returned null, needs data migration', [
                    'model'     => get_class($model),
                    'id'        => $model->getKey(),
                    'attribute' => $key,
                    'value'     => $value,
                    'enum'      => $this->enumClass,
                ]);
            }
        }

        return $enum;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        return $value;
    }
}
