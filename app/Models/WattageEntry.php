<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\DateOnly;
use Carbon\CarbonImmutable;
use Database\Factories\WattageEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single cumulative meter reading. Consumption is derived from the
 * difference between consecutive readings, never stored directly.
 *
 * @property int $id
 * @property CarbonImmutable $measured_at
 * @property float $reading_kwh
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['measured_at', 'reading_kwh', 'note'])]
class WattageEntry extends Model
{
    /** @use HasFactory<WattageEntryFactory> */
    use HasFactory;

    /**
     * Scope the query to readings taken on or before the given date.
     *
     * @param  Builder<WattageEntry>  $query
     */
    public function scopeUpTo(Builder $query, CarbonImmutable $date): void
    {
        $query->whereDate('measured_at', '<=', $date);
    }

    /**
     * Scope the query to readings taken on or after the given date.
     *
     * @param  Builder<WattageEntry>  $query
     */
    public function scopeFrom(Builder $query, CarbonImmutable $date): void
    {
        $query->whereDate('measured_at', '>=', $date);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_at' => DateOnly::class,
            'reading_kwh' => 'float',
        ];
    }
}
