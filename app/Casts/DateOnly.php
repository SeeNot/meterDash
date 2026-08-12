<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Keeps a date column stored as a bare "Y-m-d".
 *
 * Laravel's built-in date casts write "Y-m-d 00:00:00" into the column, which
 * makes a `unique` validation rule — a plain equality check against a
 * submitted "Y-m-d" — silently never match, letting duplicates through to the
 * database constraint instead of a readable error.
 *
 * @implements CastsAttributes<CarbonImmutable, CarbonInterface|string>
 */
final class DateOnly implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): CarbonImmutable
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("The [{$key}] attribute must be stored as a date string.");
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! is_string($value) && ! $value instanceof CarbonInterface) {
            throw new InvalidArgumentException("The [{$key}] attribute must be a date or a date string.");
        }

        return CarbonImmutable::parse($value)->toDateString();
    }
}
