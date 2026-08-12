<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WattageEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<WattageEntry>
 */
class WattageEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'measured_at' => Date::now()->startOfDay(),
            'reading_kwh' => fake()->randomFloat(3, 10_000, 90_000),
            'note' => null,
        ];
    }

    /**
     * Place the reading on a specific date with a specific meter value.
     */
    public function reading(string $measuredAt, float $readingKwh): static
    {
        return $this->state(fn (array $attributes): array => [
            'measured_at' => $measuredAt,
            'reading_kwh' => $readingKwh,
        ]);
    }
}
