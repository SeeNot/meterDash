<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SpotPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The average wholesale market price for one calendar month.
 *
 * This is shared market data rather than plan-specific: every dynamic plan
 * prices against the same series and differs only by its margin and fee.
 *
 * @property int $id
 * @property CarbonImmutable $period First day of the month the price applies to.
 * @property float $price_eur_per_kwh
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['period', 'price_eur_per_kwh'])]
class SpotPrice extends Model
{
    /** @use HasFactory<SpotPriceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'immutable_date',
            'price_eur_per_kwh' => 'float',
        ];
    }
}
