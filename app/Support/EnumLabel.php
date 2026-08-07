<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Safely turn an enum-cast attribute into display text.
 *
 * WHY THIS EXISTS
 * ---------------
 * Many tenant models cast columns to native PHP enums, either directly
 * (`'status' => StudentStatus::class`) or through the project's own
 * `AsEnum::of(...)` cast (`'payment_method' => AsEnum::of(FeePaymentMethod::class)`).
 *
 * Native enums are NOT Stringable. So the natural-looking:
 *
 *     (string) $payment->payment_method
 *
 * raises `Error: Object of class App\Enums\FeePaymentMethod could not be
 * converted to string` — a 500, not a warning. This has bitten the student ID
 * verification page and the fee statement already, and it will keep biting
 * anywhere a Blade template interpolates one of these attributes, because the
 * failure only shows up when that specific row is rendered.
 *
 * Use this instead of casting. It handles backed enums, pure enums, plain
 * strings, and null, and humanises snake_case / kebab-case values:
 *
 *     EnumLabel::text($payment->payment_method)   // 'Bank transfer'
 *     EnumLabel::text($student->status, 'Active') // 'Active'
 */
final class EnumLabel
{
    /**
     * Display text for a value that may be an enum, a string, or null.
     *
     * @param  string  $fallback  used when the value is null or an empty string
     */
    public static function text(mixed $value, string $fallback = '—'): string
    {
        $raw = self::raw($value);

        if ($raw === null || $raw === '') {
            return $fallback;
        }

        return ucfirst(str_replace(['_', '-'], ' ', $raw));
    }

    /**
     * The underlying scalar, unformatted — the backing value for a backed
     * enum, the case name for a pure one. Null when there is nothing usable.
     */
    public static function raw(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof \BackedEnum => (string) $value->value,
            $value instanceof \UnitEnum => $value->name,
            is_string($value) => $value,
            is_scalar($value) => (string) $value,
            // The project's AsEnum wrapper exposes the case; fall back to it.
            is_object($value) && property_exists($value, 'value') => (string) $value->value,
            is_object($value) && method_exists($value, '__toString') => (string) $value,
            default => null,
        };
    }
}
