<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PeriodRequest;
use App\Models\SpotPrice;
use App\Models\TariffPlan;
use App\Models\WattageEntry;
use App\Services\ConsumptionCalculator;
use App\Services\TariffComparator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Response;

class WattageController extends Controller
{
    /**
     * Render the consumption dashboard for the requested period.
     */
    public function index(
        PeriodRequest $request,
        ConsumptionCalculator $calculator,
        TariffComparator $comparator,
    ): Response {
        $period = $request->period($this->earliestReading());

        $entries = $this->entriesForPeriod($period->from, $period->to);
        $intervals = $calculator->intervals($entries, $period);

        $plans = TariffPlan::query()->active()->orderBy('name')->get();

        $planCosts = $comparator->compare(
            $intervals,
            $plans,
            $this->spotPricesForPeriod($period->from, $period->to),
        );

        return inertia('Wattage/Index', [
            'period' => $period->toArray(),
            'series' => $calculator->series($intervals),
            'stats' => $calculator->stats($intervals)->toArray(),
            'intervals' => array_map(fn ($interval): array => $interval->toArray(), $intervals),
            'entries' => $this->allEntries(),
            'planCosts' => array_map(fn ($cost): array => $cost->toArray(), $planCosts),
            'planCount' => $plans->count(),
        ]);
    }

    /**
     * Load the readings needed to derive consumption inside the period.
     *
     * The most recent reading *before* the period is included as an anchor:
     * consumption is a delta between two readings, so without that baseline
     * the first in-period reading would contribute nothing.
     *
     * @return Collection<int, WattageEntry>
     */
    private function entriesForPeriod(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $entries = WattageEntry::query()
            ->from($from)
            ->upTo($to)
            ->orderBy('measured_at')
            ->get();

        $anchor = WattageEntry::query()
            ->whereDate('measured_at', '<', $from)
            ->orderByDesc('measured_at')
            ->first();

        if ($anchor instanceof WattageEntry) {
            $entries->prepend($anchor);
        }

        return $entries;
    }

    /**
     * @return Collection<int, SpotPrice>
     */
    private function spotPricesForPeriod(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return SpotPrice::query()
            ->whereDate('period', '>=', $from->startOfMonth())
            ->whereDate('period', '<=', $to->startOfMonth())
            ->orderBy('period')
            ->get();
    }

    /**
     * Every reading on record, newest first, for the management table.
     *
     * @return list<array{id: int, measured_at: string, reading_kwh: float, note: string|null}>
     */
    private function allEntries(): array
    {
        return array_values(
            WattageEntry::query()
                ->orderByDesc('measured_at')
                ->get()
                ->map(fn (WattageEntry $entry): array => [
                    'id' => $entry->id,
                    'measured_at' => $entry->measured_at->toDateString(),
                    'reading_kwh' => $entry->reading_kwh,
                    'note' => $entry->note,
                ])
                ->all(),
        );
    }

    /**
     * The date of the very first reading, used to resolve the "all time" preset.
     */
    private function earliestReading(): ?CarbonImmutable
    {
        $entry = WattageEntry::query()->orderBy('measured_at')->first();

        return $entry?->measured_at;
    }
}
