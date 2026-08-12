<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConsumptionChart from '@/components/wattage/ConsumptionChart.vue';
import EmptyState from '@/components/wattage/EmptyState.vue';
import EntryForm from '@/components/wattage/EntryForm.vue';
import EntryList from '@/components/wattage/EntryList.vue';
import PeriodSelector from '@/components/wattage/PeriodSelector.vue';
import PlanComparisonChart from '@/components/wattage/PlanComparisonChart.vue';
import PlanCostTable from '@/components/wattage/PlanCostTable.vue';
import StatCard from '@/components/wattage/StatCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { date, euro, kwh } from '@/lib/format';
import type {
    ConsumptionInterval,
    ConsumptionSeries,
    ConsumptionStats,
    Period,
    PlanCost,
    WattageEntry,
} from '@/types';

const props = defineProps<{
    period: Period;
    series: ConsumptionSeries;
    stats: ConsumptionStats;
    intervals: ConsumptionInterval[];
    entries: WattageEntry[];
    planCosts: PlanCost[];
    planCount: number;
}>();

const hasSeries = computed(() => props.intervals.length > 0);
const latest = computed(() => props.entries[0] ?? null);
const cheapest = computed(() => props.planCosts[0] ?? null);

const subtitle = computed(
    () => `${date(props.period.from)} – ${date(props.period.to)}`,
);
</script>

<template>
    <AppLayout title="Consumption" :subtitle="subtitle">
        <Head title="Consumption" />

        <div class="space-y-6">
            <PeriodSelector :period="period" />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total used"
                    :value="kwh(stats.totalKwh, 0)"
                    unit="kWh"
                    :hint="`over ${stats.daysCovered} days`"
                />
                <StatCard
                    label="Average"
                    :value="kwh(stats.averageKwhPerDay, 2)"
                    unit="kWh/day"
                    hint="weighted by interval length"
                />
                <StatCard
                    label="Median"
                    :value="kwh(stats.medianKwhPerDay, 2)"
                    unit="kWh/day"
                    :hint="`across ${stats.intervalCount} intervals`"
                />
                <StatCard
                    label="Range"
                    :value="`${kwh(stats.minKwhPerDay, 1)}–${kwh(stats.maxKwhPerDay, 1)}`"
                    unit="kWh/day"
                    hint="lowest to highest interval"
                />
            </div>

            <section class="rounded-lg border border-hairline bg-surface p-5">
                <h2 class="text-sm font-semibold text-ink">
                    Consumption over time
                </h2>
                <p class="mt-1 mb-4 text-xs text-ink-secondary">
                    Daily average between consecutive meter readings, with the
                    median for the period.
                </p>
                <ConsumptionChart
                    v-if="hasSeries"
                    :series="series"
                    :stats="stats"
                />
                <EmptyState
                    v-else
                    title="Not enough readings yet"
                    message="Consumption is the difference between two readings, so at least two are needed in this period before a chart can be drawn."
                />
            </section>

            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-ink">
                            Tariff comparison
                        </h2>
                        <p class="mt-1 text-xs text-ink-secondary">
                            What this period's consumption would have cost on
                            each plan.
                        </p>
                    </div>
                    <p
                        v-if="cheapest && hasSeries"
                        class="text-xs text-ink-secondary"
                    >
                        Best:
                        <span class="font-medium text-ink">{{
                            cheapest.planName
                        }}</span>
                        at {{ euro(cheapest.totalEur) }}
                    </p>
                </div>

                <template v-if="hasSeries && planCosts.length > 0">
                    <div
                        class="rounded-lg border border-hairline bg-surface p-5"
                    >
                        <PlanComparisonChart :plan-costs="planCosts" />
                    </div>
                    <PlanCostTable :plan-costs="planCosts" />
                </template>
                <EmptyState
                    v-else-if="planCount === 0"
                    title="No plans to compare"
                    message="Add at least one tariff plan on the Plans page to see what this consumption would cost."
                />
                <EmptyState
                    v-else
                    title="Nothing to cost yet"
                    message="Record two or more readings in this period and the plan comparison will fill in."
                />
            </section>

            <section class="grid gap-6 lg:grid-cols-[20rem_1fr]">
                <EntryForm :latest="latest" />

                <div class="space-y-2">
                    <h2 class="text-sm font-semibold text-ink">Readings</h2>
                    <EntryList v-if="entries.length > 0" :entries="entries" />
                    <EmptyState
                        v-else
                        title="No readings recorded"
                        message="Add your first meter reading to get started."
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
