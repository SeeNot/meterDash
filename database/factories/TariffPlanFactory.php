<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TariffPlanType;
use App\Models\TariffPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffPlan>
 */
class TariffPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Energy',
            'type' => TariffPlanType::Fixed,
            'energy_rate_eur' => fake()->randomFloat(5, 0.12, 0.24),
            'standing_charge_eur' => fake()->randomFloat(2, 0, 8),
            'is_active' => true,
        ];
    }

    /**
     * A flat price per kWh.
     */
    public function fixed(float $rate = 0.18, float $standingCharge = 4.0): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TariffPlanType::Fixed,
            'energy_rate_eur' => $rate,
            'standing_charge_eur' => $standingCharge,
        ]);
    }

    /**
     * A margin applied on top of the monthly market price.
     */
    public function dynamic(float $margin = 0.02, float $standingCharge = 2.5): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TariffPlanType::Dynamic,
            'energy_rate_eur' => $margin,
            'standing_charge_eur' => $standingCharge,
        ]);
    }

    /**
     * Exclude the plan from comparisons.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
