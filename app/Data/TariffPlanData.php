<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TariffPlanType;
use App\Models\TariffPlan;

/**
 * A tariff plan shaped for the plans list.
 */
final readonly class TariffPlanData
{
    public function __construct(
        public int $id,
        public string $name,
        public TariffPlanType $type,
        public float $energyRateEur,
        public float $standingChargeEur,
        public bool $isActive,
    ) {}

    public static function fromModel(TariffPlan $plan): self
    {
        return new self(
            id: $plan->id,
            name: $plan->name,
            type: $plan->type,
            energyRateEur: $plan->energy_rate_eur,
            standingChargeEur: $plan->standing_charge_eur,
            isActive: $plan->is_active,
        );
    }

    /**
     * @return array{id: int, name: string, type: string, typeLabel: string, energy_rate_eur: float, standing_charge_eur: float, is_active: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'energy_rate_eur' => $this->energyRateEur,
            'standing_charge_eur' => $this->standingChargeEur,
            'is_active' => $this->isActive,
        ];
    }
}
