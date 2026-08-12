<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SpotPrice;
use App\Models\TariffPlan;
use App\Models\WattageEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Seeds 18 months of plausible household data so the dashboard has something
 * to show on a fresh install.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * How many kWh a day the household draws in each calendar month.
     *
     * Winter is heavier (heating, lighting); July and August are the floor.
     *
     * @var array<int, float>
     */
    private const SEASONAL_KWH_PER_DAY = [
        1 => 14.8,
        2 => 13.9,
        3 => 11.6,
        4 => 9.2,
        5 => 7.8,
        6 => 6.9,
        7 => 6.4,
        8 => 6.6,
        9 => 8.1,
        10 => 10.4,
        11 => 12.7,
        12 => 14.2,
    ];

    /**
     * The wholesale price per kWh in each calendar month.
     *
     * @var array<int, float>
     */
    private const SEASONAL_SPOT_EUR = [
        1 => 0.161,
        2 => 0.148,
        3 => 0.122,
        4 => 0.094,
        5 => 0.071,
        6 => 0.063,
        7 => 0.058,
        8 => 0.067,
        9 => 0.089,
        10 => 0.113,
        11 => 0.139,
        12 => 0.172,
    ];

    /**
     * Seed the demo dataset.
     */
    public function run(): void
    {
        /** @var CarbonImmutable $start */
        $start = Date::now()->startOfMonth()->subMonths(18);

        $this->seedReadings($start);
        $this->seedSpotPrices($start);
        $this->seedPlans();
    }

    /**
     * Walk month by month, accumulating a monotonically increasing meter value.
     *
     * A factory can't do this on its own: each reading only makes sense
     * relative to the one before it.
     */
    private function seedReadings(CarbonImmutable $start): void
    {
        $reading = 42_318.0;
        $month = $start;
        $end = $start->addMonths(18);

        WattageEntry::create([
            'measured_at' => $month,
            'reading_kwh' => round($reading, 3),
            'note' => 'Opening reading',
        ]);

        while ($month->lessThan($end)) {
            $next = $month->addMonth();
            $days = (int) $month->diffInDays($next);

            // A little noise so the chart isn't a perfectly smooth curve.
            $perDay = self::SEASONAL_KWH_PER_DAY[(int) $month->month] * random_int(92, 108) / 100;
            $reading += $perDay * $days;

            WattageEntry::create([
                'measured_at' => $next,
                'reading_kwh' => round($reading, 3),
                'note' => null,
            ]);

            $month = $next;
        }
    }

    private function seedSpotPrices(CarbonImmutable $start): void
    {
        $month = $start;
        $end = $start->addMonths(19);

        while ($month->lessThan($end)) {
            SpotPrice::create([
                'period' => $month,
                'price_eur_per_kwh' => round(
                    self::SEASONAL_SPOT_EUR[(int) $month->month] * random_int(88, 112) / 100,
                    5,
                ),
            ]);

            $month = $month->addMonth();
        }
    }

    private function seedPlans(): void
    {
        TariffPlan::factory()->fixed(rate: 0.1790, standingCharge: 2.99)->create([
            'name' => 'Steady 12',
        ]);

        TariffPlan::factory()->fixed(rate: 0.1590, standingCharge: 7.49)->create([
            'name' => 'Steady 24 Plus',
        ]);

        TariffPlan::factory()->dynamic(margin: 0.0120, standingCharge: 4.99)->create([
            'name' => 'Market Follow',
        ]);

        TariffPlan::factory()->dynamic(margin: 0.0290, standingCharge: 0.0)->create([
            'name' => 'Market Zero Fee',
        ]);
    }
}
