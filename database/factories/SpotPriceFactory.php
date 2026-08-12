<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpotPrice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<SpotPrice>
 */
class SpotPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period' => Date::now()->startOfMonth(),
            'price_eur_per_kwh' => fake()->randomFloat(5, 0.05, 0.25),
        ];
    }

    /**
     * Set the month and price explicitly.
     */
    public function forMonth(string $period, float $price): static
    {
        return $this->state(fn (array $attributes): array => [
            'period' => $period,
            'price_eur_per_kwh' => $price,
        ]);
    }
}
