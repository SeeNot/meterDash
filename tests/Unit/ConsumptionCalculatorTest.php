<?php

declare(strict_types=1);

use App\Data\Period;
use App\Models\WattageEntry;
use App\Services\ConsumptionCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Build an unsaved reading so the calculator can be exercised without the database.
 *
 * @param  array<int, array{0: string, 1: float}>  $readings
 * @return Collection<int, WattageEntry>
 */
function readings(array $readings): Collection
{
    return collect($readings)->map(fn (array $reading): WattageEntry => new WattageEntry([
        'measured_at' => $reading[0],
        'reading_kwh' => $reading[1],
    ]));
}

it('derives an interval from each consecutive pair of readings', function (): void {
    $intervals = (new ConsumptionCalculator)->intervals(readings([
        ['2026-01-01', 1000.0],
        ['2026-01-11', 1100.0],
        ['2026-01-31', 1500.0],
    ]));

    expect($intervals)->toHaveCount(2)
        ->and($intervals[0]->days)->toBe(10)
        ->and($intervals[0]->kwh)->toBe(100.0)
        ->and($intervals[0]->kwhPerDay)->toBe(10.0)
        ->and($intervals[1]->days)->toBe(20)
        ->and($intervals[1]->kwh)->toBe(400.0)
        ->and($intervals[1]->kwhPerDay)->toBe(20.0);
});

it('sorts readings before deriving intervals', function (): void {
    $intervals = (new ConsumptionCalculator)->intervals(readings([
        ['2026-01-31', 1500.0],
        ['2026-01-01', 1000.0],
        ['2026-01-11', 1100.0],
    ]));

    expect($intervals)->toHaveCount(2)
        ->and($intervals[0]->from->toDateString())->toBe('2026-01-01');
});

it('returns no intervals when there is nothing to compare', function (array $input): void {
    expect((new ConsumptionCalculator)->intervals(readings($input)))->toBe([]);
})->with([
    'no readings' => [[]],
    'a single reading' => [[['2026-01-01', 1000.0]]],
]);

it('skips a reading that goes backwards', function (): void {
    // A replaced meter restarts near zero; that is not negative consumption.
    $intervals = (new ConsumptionCalculator)->intervals(readings([
        ['2026-01-01', 1000.0],
        ['2026-01-11', 40.0],
        ['2026-01-21', 140.0],
    ]));

    expect($intervals)->toHaveCount(1)
        ->and($intervals[0]->kwh)->toBe(100.0)
        ->and($intervals[0]->from->toDateString())->toBe('2026-01-11');
});

it('skips a pair of readings taken on the same day', function (): void {
    $intervals = (new ConsumptionCalculator)->intervals(readings([
        ['2026-01-01', 1000.0],
        ['2026-01-01', 1010.0],
        ['2026-01-11', 1100.0],
    ]));

    expect($intervals)->toHaveCount(1)
        ->and($intervals[0]->days)->toBe(10);
});

it('weights the average by interval length rather than averaging the rates', function (): void {
    $calculator = new ConsumptionCalculator;

    // 10 kWh over 1 day, then 100 kWh over 100 days. The naive mean of the two
    // rates is 5.5 kWh/day; the day-weighted answer is 110/101.
    $stats = $calculator->stats($calculator->intervals(readings([
        ['2026-01-01', 0.0],
        ['2026-01-02', 10.0],
        ['2026-04-12', 110.0],
    ])));

    expect($stats->totalKwh)->toBe(110.0)
        ->and($stats->daysCovered)->toBe(101)
        ->and(round($stats->averageKwhPerDay, 4))->toBe(round(110 / 101, 4))
        ->and($stats->averageKwhPerDay)->not->toEqual(5.5);
});

it('takes the middle rate as the median for an odd number of intervals', function (): void {
    $calculator = new ConsumptionCalculator;

    // Daily rates of 1, 5 and 3 kWh/day.
    $stats = $calculator->stats($calculator->intervals(readings([
        ['2026-01-01', 0.0],
        ['2026-01-02', 1.0],
        ['2026-01-03', 6.0],
        ['2026-01-04', 9.0],
    ])));

    expect($stats->medianKwhPerDay)->toBe(3.0);
});

it('averages the two middle rates as the median for an even number of intervals', function (): void {
    $calculator = new ConsumptionCalculator;

    // Daily rates of 1, 5, 3 and 9 kWh/day; the middles are 3 and 5.
    $stats = $calculator->stats($calculator->intervals(readings([
        ['2026-01-01', 0.0],
        ['2026-01-02', 1.0],
        ['2026-01-03', 6.0],
        ['2026-01-04', 9.0],
        ['2026-01-05', 18.0],
    ])));

    expect($stats->medianKwhPerDay)->toBe(4.0);
});

it('reports zeroed statistics when there are no intervals', function (): void {
    $stats = (new ConsumptionCalculator)->stats([]);

    expect($stats->totalKwh)->toBe(0.0)
        ->and($stats->averageKwhPerDay)->toBe(0.0)
        ->and($stats->medianKwhPerDay)->toBe(0.0)
        ->and($stats->intervalCount)->toBe(0);
});

it('reports the lowest and highest daily rate', function (): void {
    $calculator = new ConsumptionCalculator;

    $stats = $calculator->stats($calculator->intervals(readings([
        ['2026-01-01', 0.0],
        ['2026-01-02', 1.0],
        ['2026-01-03', 6.0],
        ['2026-01-04', 9.0],
    ])));

    expect($stats->minKwhPerDay)->toBe(1.0)
        ->and($stats->maxKwhPerDay)->toBe(5.0)
        ->and($stats->intervalCount)->toBe(3);
});

it('clips an interval that straddles the start of the period', function (): void {
    // 310 kWh over 31 days; only 16 of those days are inside the period.
    $intervals = (new ConsumptionCalculator)->intervals(
        readings([['2026-05-01', 1000.0], ['2026-06-01', 1310.0]]),
        Period::custom(
            Date::parse('2026-05-16')->toImmutable(),
            Date::parse('2026-06-15')->toImmutable(),
        ),
    );

    expect($intervals)->toHaveCount(1)
        ->and($intervals[0]->from->toDateString())->toBe('2026-05-16')
        ->and($intervals[0]->to->toDateString())->toBe('2026-06-01')
        ->and($intervals[0]->days)->toBe(16)
        ->and($intervals[0]->kwh)->toBe(160.0)
        // Clipping trims the total, never the measured daily rate.
        ->and($intervals[0]->kwhPerDay)->toBe(10.0);
});

it('drops an interval that closes before the period opens', function (): void {
    $intervals = (new ConsumptionCalculator)->intervals(
        readings([['2024-01-01', 100.0], ['2026-06-01', 1000.0]]),
        Period::custom(
            Date::parse('2026-06-01')->toImmutable(),
            Date::parse('2026-06-30')->toImmutable(),
        ),
    );

    expect($intervals)->toBe([]);
});

it('builds a chart series labelled by the closing month of each interval', function (): void {
    $calculator = new ConsumptionCalculator;

    $series = $calculator->series($calculator->intervals(readings([
        ['2026-01-01', 0.0],
        ['2026-02-01', 310.0],
        ['2026-03-01', 590.0],
    ])));

    expect($series['labels'])->toBe(['Feb 2026', 'Mar 2026'])
        ->and($series['kwh'])->toBe([310.0, 280.0])
        ->and($series['kwhPerDay'])->toBe([10.0, 10.0]);
});
