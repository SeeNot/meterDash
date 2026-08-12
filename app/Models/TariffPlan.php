<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TariffPlanType;
use Carbon\CarbonImmutable;
use Database\Factories\TariffPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An electricity tariff the recorded consumption can be costed against.
 *
 * For a fixed plan `energy_rate_eur` is the flat price per kWh. For a dynamic
 * plan it is the supplier margin added on top of the monthly market price.
 *
 * @property int $id
 * @property string $name
 * @property TariffPlanType $type
 * @property float $energy_rate_eur
 * @property float $standing_charge_eur
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'type', 'energy_rate_eur', 'standing_charge_eur', 'is_active'])]
class TariffPlan extends Model
{
    /** @use HasFactory<TariffPlanFactory> */
    use HasFactory;

    /**
     * Scope the query to plans available for comparison.
     *
     * @param  Builder<TariffPlan>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TariffPlanType::class,
            'energy_rate_eur' => 'float',
            'standing_charge_eur' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
