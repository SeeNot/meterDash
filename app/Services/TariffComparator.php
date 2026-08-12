<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConsumptionInterval;
use App\Data\PlanCost;
use App\Enums\TariffPlanType;
use App\Models\SpotPrice;
use App\Models\TariffPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Costs a period's consumption against each available tariff plan.
 */
final class TariffComparator
{
    /**
     * Cost every plan over the given intervals, cheapest first.
     *
     * @param  list<ConsumptionInterval>  $intervals
     * @param  Collection<int, TariffPlan>  $plans
     * @param  Collection<int, SpotPrice>  $spotPrices
     * @return list<PlanCost>
     */
    public function compare(array $intervals, Collection $plans, Collection $spotPrices): array
    {
        $months = $this->allocateByMonth($intervals);

        $rates = $spotPrices
            ->mapWithKeys(fn (SpotPrice $price): array => [
                $price->period->format('Y-m') => $price->price_eur_per_kwh,
            ])
            ->all();

        $fallbackRate = $rates === [] ? 0.0 : array_sum($rates) / count($rates);

        return array_values(
            $plans
                ->map(fn (TariffPlan $plan): PlanCost => $this->costFor($plan, $months, $rates, $fallbackRate))
                ->sortBy(fn (PlanCost $cost): float => $cost->totalEur())
                ->all(),
        );
    }

    /**
     * Spread each interval's consumption across the calendar months it spans.
     *
     * Consumption is apportioned pro rata by days, which is the best we can do
     * without sub-reading granularity, and it keeps a reading that straddles a
     * month boundary from being priced entirely at one month's rate.
     *
     * @param  list<ConsumptionInterval>  $intervals
     * @return array<string, array{month: CarbonImmutable, days: int, kwh: float}>
     */
    private function allocateByMonth(array $intervals): array
    {
        /** @var array<string, array{month: CarbonImmutable, days: int, kwh: float}> $months */
        $months = [];

        foreach ($intervals as $interval) {
            $cursor = $interval->from;

            while ($cursor < $interval->to) {
                $nextMonth = $cursor->startOfMonth()->addMonth();
                $segmentEnd = $nextMonth->lessThan($interval->to) ? $nextMonth : $interval->to;

                $segmentDays = (int) $cursor->diffInDays($segmentEnd);
                $key = $cursor->format('Y-m');

                $months[$key] ??= [
                    'month' => $cursor->startOfMonth(),
                    'days' => 0,
                    'kwh' => 0.0,
                ];

                $months[$key]['days'] += $segmentDays;
                $months[$key]['kwh'] += $interval->kwh * $segmentDays / $interval->days;

                $cursor = $segmentEnd;
            }
        }

        ksort($months);

        return $months;
    }

    /**
     * @param  array<string, array{month: CarbonImmutable, days: int, kwh: float}>  $months
     * @param  array<string, float>  $rates
     */
    private function costFor(TariffPlan $plan, array $months, array $rates, float $fallbackRate): PlanCost
    {
        $energyEur = 0.0;
        $standingEur = 0.0;
        $totalKwh = 0.0;
        $hasEstimatedPrices = false;

        /** @var list<array{period: string, label: string, kwh: float, rateEur: float, energyEur: float, standingEur: float, estimated: bool}> $monthly */
        $monthly = [];

        foreach ($months as $key => $month) {
            $estimated = $plan->type === TariffPlanType::Dynamic && ! isset($rates[$key]);

            $rate = match ($plan->type) {
                TariffPlanType::Fixed => $plan->energy_rate_eur,
                TariffPlanType::Dynamic => ($rates[$key] ?? $fallbackRate) + $plan->energy_rate_eur,
            };

            $monthEnergy = $month['kwh'] * $rate;

            // Pro-rate the monthly fee by the share of the month actually
            // covered, so a two-week period is not charged a full month.
            $monthStanding = $plan->standing_charge_eur
                * $month['days']
                / $month['month']->daysInMonth;

            $energyEur += $monthEnergy;
            $standingEur += $monthStanding;
            $totalKwh += $month['kwh'];
            $hasEstimatedPrices = $hasEstimatedPrices || $estimated;

            $monthly[] = [
                'period' => $key,
                'label' => $month['month']->format('M Y'),
                'kwh' => round($month['kwh'], 2),
                'rateEur' => round($rate, 4),
                'energyEur' => round($monthEnergy, 2),
                'standingEur' => round($monthStanding, 2),
                'estimated' => $estimated,
            ];
        }

        return new PlanCost(
            planId: $plan->id,
            planName: $plan->name,
            type: $plan->type,
            energyEur: $energyEur,
            standingEur: $standingEur,
            totalKwh: $totalKwh,
            monthly: $monthly,
            hasEstimatedPrices: $hasEstimatedPrices,
        );
    }
}
