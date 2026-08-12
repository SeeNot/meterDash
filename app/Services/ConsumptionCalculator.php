<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConsumptionInterval;
use App\Data\ConsumptionStats;
use App\Data\Period;
use App\Models\WattageEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Turns cumulative meter readings into the consumption between them.
 */
final class ConsumptionCalculator
{
    /**
     * Derive the consumption intervals bounded by consecutive readings.
     *
     * Readings taken on the same day and readings that go backwards (a meter
     * reset or replacement) cannot describe real consumption, so they are
     * skipped rather than producing an infinite or negative rate.
     *
     * When a period is given, an interval that straddles its edge is clipped
     * to the overlap and its consumption pro-rated by days. Without that, the
     * reading that anchors the start of the period would drag its entire span
     * — potentially years of usage — into the period's totals.
     *
     * @param  Collection<int, WattageEntry>  $entries
     * @return list<ConsumptionInterval>
     */
    public function intervals(Collection $entries, ?Period $period = null): array
    {
        $intervals = [];
        $previous = null;

        foreach ($entries->sortBy('measured_at')->values() as $current) {
            if ($previous === null) {
                $previous = $current;

                continue;
            }

            $days = (int) $previous->measured_at->diffInDays($current->measured_at);
            $kwh = $current->reading_kwh - $previous->reading_kwh;

            if ($days > 0 && $kwh >= 0.0) {
                $interval = $this->clip(
                    $previous->measured_at,
                    $current->measured_at,
                    $days,
                    $kwh,
                    $period,
                );

                if ($interval instanceof ConsumptionInterval) {
                    $intervals[] = $interval;
                }
            }

            $previous = $current;
        }

        return $intervals;
    }

    /**
     * Trim an interval to the reporting period, or drop it if it falls outside.
     *
     * The daily rate is unchanged by clipping — both the kWh and the days
     * scale together — so only the totals shrink.
     */
    private function clip(
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $days,
        float $kwh,
        ?Period $period,
    ): ?ConsumptionInterval {
        if (! $period instanceof Period) {
            return ConsumptionInterval::between($from, $to, $kwh);
        }

        $start = $from->greaterThan($period->from) ? $from : $period->from;
        $end = $to->lessThan($period->to) ? $to : $period->to;

        $overlapDays = (int) $start->diffInDays($end);

        if ($overlapDays <= 0) {
            return null;
        }

        return ConsumptionInterval::between($start, $end, $kwh * $overlapDays / $days);
    }

    /**
     * Summarise a set of intervals.
     *
     * @param  list<ConsumptionInterval>  $intervals
     */
    public function stats(array $intervals): ConsumptionStats
    {
        if ($intervals === []) {
            return ConsumptionStats::empty();
        }

        $totalKwh = array_sum(array_map(fn (ConsumptionInterval $i): float => $i->kwh, $intervals));
        $totalDays = array_sum(array_map(fn (ConsumptionInterval $i): int => $i->days, $intervals));
        $rates = array_map(fn (ConsumptionInterval $i): float => $i->kwhPerDay, $intervals);

        return new ConsumptionStats(
            totalKwh: $totalKwh,
            // Day-weighted, not the mean of the per-interval rates: intervals
            // have unequal lengths, so a plain mean over-weights short gaps.
            averageKwhPerDay: $totalDays > 0 ? $totalKwh / $totalDays : 0.0,
            medianKwhPerDay: $this->median($rates),
            minKwhPerDay: min($rates),
            maxKwhPerDay: max($rates),
            daysCovered: $totalDays,
            intervalCount: count($intervals),
        );
    }

    /**
     * Build the chart series for a set of intervals.
     *
     * @param  list<ConsumptionInterval>  $intervals
     * @return array{labels: list<string>, kwh: list<float>, kwhPerDay: list<float>}
     */
    public function series(array $intervals): array
    {
        return [
            'labels' => array_map(fn (ConsumptionInterval $i): string => $i->to->format('M Y'), $intervals),
            'kwh' => array_map(fn (ConsumptionInterval $i): float => round($i->kwh, 2), $intervals),
            'kwhPerDay' => array_map(fn (ConsumptionInterval $i): float => round($i->kwhPerDay, 2), $intervals),
        ];
    }

    /**
     * The middle value, averaging the two middles for an even count.
     *
     * @param  list<float>  $values
     */
    private function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);

        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
