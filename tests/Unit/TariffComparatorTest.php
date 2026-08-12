<?php

declare(strict_types=1);

use App\Data\ConsumptionInterval;
use App\Enums\TariffPlanType;
use App\Models\SpotPrice;
use App\Models\TariffPlan;
use App\Services\TariffComparator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * @param  array<int, array{0: string, 1: string, 2: float}>  $intervals
 * @return list<ConsumptionInterval>
 */
function intervals(array $intervals): array
{
    return array_map(fn (array $interval): ConsumptionInterval => ConsumptionInterval::between(
        Date::parse($interval[0])->toImmutable(),
        Date::parse($interval[1])->toImmutable(),
        $interval[2],
    ), $intervals);
}

/**
 * @return Collection<int, TariffPlan>
 */
function plan(TariffPlanType $type, float $rate, float $standingCharge): Collection
{
    $plan = new TariffPlan([
        'name' => 'Test plan',
        'type' => $type,
        'energy_rate_eur' => $rate,
        'standing_charge_eur' => $standingCharge,
    ]);

    $plan->id = 1;

    return collect([$plan]);
}

/**
 * @param  array<string, float>  $prices
 * @return Collection<int, SpotPrice>
 */
function spotPrices(array $prices): Collection
{
    return collect($prices)->map(fn (float $price, string $period): SpotPrice => new SpotPrice([
        'period' => $period.'-01',
        'price_eur_per_kwh' => $price,
    ]))->values();
}

it('prices a fixed plan at its flat rate', function (): void {
    // A whole calendar month, so the standing charge is not pro-rated away.
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-04-01', 300.0]]),
        plan(TariffPlanType::Fixed, rate: 0.20, standingCharge: 5.0),
        collect(),
    );

    expect($costs[0]->energyEur)->toBe(60.0)
        ->and($costs[0]->standingEur)->toBe(5.0)
        ->and($costs[0]->totalEur())->toBe(65.0)
        ->and(round($costs[0]->effectiveRateEur(), 4))->toBe(0.2167);
});

it('splits an interval across the months it spans, pro rata by days', function (): void {
    // 20 days in March, 10 in April, 300 kWh total → 200 kWh March, 100 kWh April.
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-12', '2026-04-11', 300.0]]),
        plan(TariffPlanType::Dynamic, rate: 0.0, standingCharge: 0.0),
        spotPrices(['2026-03' => 0.10, '2026-04' => 0.20]),
    );

    expect($costs[0]->monthly)->toHaveCount(2)
        ->and($costs[0]->monthly[0]['kwh'])->toBe(200.0)
        ->and($costs[0]->monthly[1]['kwh'])->toBe(100.0)
        // 200 × 0.10 + 100 × 0.20
        ->and($costs[0]->energyEur)->toBe(40.0);
});

it('adds the supplier margin on top of the market price', function (): void {
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-04-01', 100.0]]),
        plan(TariffPlanType::Dynamic, rate: 0.03, standingCharge: 0.0),
        spotPrices(['2026-03' => 0.12]),
    );

    expect($costs[0]->monthly[0]['rateEur'])->toBe(0.15)
        ->and($costs[0]->energyEur)->toBe(15.0);
});

it('pro-rates the standing charge by the share of each month covered', function (): void {
    // 15 of March's 31 days.
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-03-16', 100.0]]),
        plan(TariffPlanType::Fixed, rate: 0.0, standingCharge: 31.0),
        collect(),
    );

    expect($costs[0]->standingEur)->toBe(15.0);
});

it('falls back to the average market price for a month with no data, and flags it', function (): void {
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-05-01', 200.0]]),
        plan(TariffPlanType::Dynamic, rate: 0.0, standingCharge: 0.0),
        // April is missing; the mean of 0.10 and 0.30 is 0.20.
        spotPrices(['2026-03' => 0.10, '2026-05' => 0.30]),
    );

    expect($costs[0]->hasEstimatedPrices)->toBeTrue()
        ->and($costs[0]->monthly[0]['estimated'])->toBeFalse()
        ->and($costs[0]->monthly[1]['estimated'])->toBeTrue()
        ->and($costs[0]->monthly[1]['rateEur'])->toBe(0.2);
});

it('never flags a fixed plan as estimated, whatever the market data', function (): void {
    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-04-01', 100.0]]),
        plan(TariffPlanType::Fixed, rate: 0.2, standingCharge: 0.0),
        collect(),
    );

    expect($costs[0]->hasEstimatedPrices)->toBeFalse();
});

it('orders plans cheapest first', function (): void {
    $cheap = new TariffPlan([
        'name' => 'Cheap',
        'type' => TariffPlanType::Fixed,
        'energy_rate_eur' => 0.10,
        'standing_charge_eur' => 0.0,
    ]);
    $cheap->id = 1;

    $pricey = new TariffPlan([
        'name' => 'Pricey',
        'type' => TariffPlanType::Fixed,
        'energy_rate_eur' => 0.30,
        'standing_charge_eur' => 0.0,
    ]);
    $pricey->id = 2;

    $costs = (new TariffComparator)->compare(
        intervals([['2026-03-01', '2026-04-01', 100.0]]),
        collect([$pricey, $cheap]),
        collect(),
    );

    expect($costs[0]->planName)->toBe('Cheap')
        ->and($costs[1]->planName)->toBe('Pricey');
});

it('costs nothing when there is no consumption to price', function (): void {
    $costs = (new TariffComparator)->compare(
        [],
        plan(TariffPlanType::Fixed, rate: 0.2, standingCharge: 5.0),
        collect(),
    );

    expect($costs)->toHaveCount(1)
        ->and($costs[0]->totalEur())->toBe(0.0)
        ->and($costs[0]->effectiveRateEur())->toBe(0.0)
        ->and($costs[0]->monthly)->toBe([]);
});
