<?php

declare(strict_types=1);

use App\Models\SpotPrice;
use App\Models\TariffPlan;
use App\Models\WattageEntry;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    Date::setTestNow('2026-06-15');
});

it('renders the dashboard', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Wattage/Index')
            ->has('period')
            ->has('series')
            ->has('stats')
            ->has('planCosts')
        );
});

it('derives consumption from the readings in the period', function (): void {
    WattageEntry::factory()->reading('2026-05-01', 1000.0)->create();
    WattageEntry::factory()->reading('2026-05-31', 1300.0)->create();

    $this->get('/?preset=3m')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.totalKwh', 300)
            ->where('stats.averageKwhPerDay', 10)
            ->where('stats.intervalCount', 1)
            ->count('series.labels', 1)
        );
});

it('anchors on the last reading before the period so the first interval is not lost', function (): void {
    // The anchor sits outside the 30-day window (16 May – 15 Jun) but is the
    // only way to know how much was used before the first in-window reading.
    // Its interval is clipped to the 16 days that fall inside the window:
    // 310 kWh over 31 days → 160 kWh, plus the 100 kWh measured in June.
    WattageEntry::factory()->reading('2026-05-01', 1000.0)->create();
    WattageEntry::factory()->reading('2026-06-01', 1310.0)->create();
    WattageEntry::factory()->reading('2026-06-11', 1410.0)->create();

    $this->get('/?preset=30d')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.intervalCount', 2)
            ->where('stats.totalKwh', 260)
            ->where('intervals.0.from', '2026-05-16')
            ->where('intervals.0.days', 16)
        );
});

it('does not drag a distant anchor reading into the period totals', function (): void {
    // Without clipping, the 900 kWh accumulated since 2024 would all land in
    // a one-month window.
    WattageEntry::factory()->reading('2024-01-01', 100.0)->create();
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();
    WattageEntry::factory()->reading('2026-06-11', 1100.0)->create();

    $this->get('/?from=2026-06-01&to=2026-06-30')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('period.preset', 'custom')
            ->where('period.from', '2026-06-01')
            ->where('stats.intervalCount', 1)
            ->where('stats.totalKwh', 100)
        );
});

it('reports empty statistics when the period holds fewer than two readings', function (): void {
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->get('/?preset=30d')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.intervalCount', 0)
            ->where('stats.totalKwh', 0)
            ->count('intervals', 0)
            ->count('series.labels', 0)
        );
});

it('costs the period against every active plan, cheapest first', function (): void {
    WattageEntry::factory()->reading('2026-05-01', 1000.0)->create();
    WattageEntry::factory()->reading('2026-06-01', 1100.0)->create();
    SpotPrice::factory()->forMonth('2026-05-01', 0.10)->create();

    TariffPlan::factory()->fixed(rate: 0.50, standingCharge: 0.0)->create(['name' => 'Pricey']);
    TariffPlan::factory()->fixed(rate: 0.10, standingCharge: 0.0)->create(['name' => 'Cheap']);
    TariffPlan::factory()->fixed(rate: 0.01, standingCharge: 0.0)->inactive()->create(['name' => 'Hidden']);

    $this->get('/?preset=3m')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('planCosts', 2)
            ->where('planCosts.0.planName', 'Cheap')
            ->where('planCosts.1.planName', 'Pricey')
            ->where('planCount', 2)
        );
});

it('lists every reading regardless of the selected period', function (): void {
    WattageEntry::factory()->reading('2020-01-01', 10.0)->create();
    WattageEntry::factory()->reading('2026-06-01', 1000.0)->create();

    $this->get('/?preset=30d')
        ->assertInertia(fn (AssertableInertia $page) => $page->count('entries', 2));
});

it('resolves the all-time preset from the first reading on record', function (): void {
    WattageEntry::factory()->reading('2023-02-09', 10.0)->create();

    $this->get('/?preset=all')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('period.preset', 'all')
            ->where('period.from', '2023-02-09')
        );
});

it('rejects a range that ends before it starts', function (): void {
    $this->get('/?from=2026-06-30&to=2026-06-01')
        ->assertSessionHasErrors('to');
});
