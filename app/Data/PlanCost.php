<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TariffPlanType;

/**
 * What one tariff plan would have cost for the consumption in a period.
 */
final readonly class PlanCost
{
    /**
     * @param  list<array{period: string, label: string, kwh: float, rateEur: float, energyEur: float, standingEur: float, estimated: bool}>  $monthly
     */
    public function __construct(
        public int $planId,
        public string $planName,
        public TariffPlanType $type,
        public float $energyEur,
        public float $standingEur,
        public float $totalKwh,
        public array $monthly,
        public bool $hasEstimatedPrices,
    ) {}

    /**
     * The total bill for the period.
     */
    public function totalEur(): float
    {
        return $this->energyEur + $this->standingEur;
    }

    /**
     * The all-in cost per kWh, standing charges included.
     */
    public function effectiveRateEur(): float
    {
        if ($this->totalKwh <= 0.0) {
            return 0.0;
        }

        return $this->totalEur() / $this->totalKwh;
    }

    /**
     * @return array{planId: int, planName: string, type: string, typeLabel: string, totalEur: float, energyEur: float, standingEur: float, effectiveRateEur: float, hasEstimatedPrices: bool, monthly: list<array{period: string, label: string, kwh: float, rateEur: float, energyEur: float, standingEur: float, estimated: bool}>}
     */
    public function toArray(): array
    {
        return [
            'planId' => $this->planId,
            'planName' => $this->planName,
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'totalEur' => round($this->totalEur(), 2),
            'energyEur' => round($this->energyEur, 2),
            'standingEur' => round($this->standingEur, 2),
            'effectiveRateEur' => round($this->effectiveRateEur(), 4),
            'hasEstimatedPrices' => $this->hasEstimatedPrices,
            'monthly' => $this->monthly,
        ];
    }
}
