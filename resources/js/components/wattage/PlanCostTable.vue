<script setup lang="ts">
import { computed } from 'vue';
import { euro, rate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PlanCost } from '@/types';

const props = defineProps<{
    planCosts: PlanCost[];
}>();

const cheapest = computed(() => props.planCosts[0]?.totalEur ?? 0);
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-hairline bg-surface">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <caption class="sr-only">
                Cost of the selected period's consumption on each tariff plan
            </caption>
            <thead
                class="border-b border-hairline text-xs tracking-wide text-ink-muted uppercase"
            >
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Plan</th>
                    <th scope="col" class="px-4 py-3 font-medium">Type</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Energy
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Standing
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Total
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        Effective
                    </th>
                    <th scope="col" class="px-4 py-3 text-right font-medium">
                        vs best
                    </th>
                </tr>
            </thead>
            <tbody class="tabular-nums">
                <tr
                    v-for="(cost, index) in planCosts"
                    :key="cost.planId"
                    class="border-b border-hairline last:border-0"
                >
                    <th
                        scope="row"
                        class="px-4 py-3 text-left font-medium text-ink"
                    >
                        <span class="flex items-center gap-2">
                            {{ cost.planName }}
                            <span
                                v-if="index === 0"
                                class="rounded-sm border border-good px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-good uppercase"
                                >Cheapest</span
                            >
                        </span>
                    </th>
                    <td class="px-4 py-3 text-ink-secondary">
                        {{ cost.typeLabel }}
                        <span
                            v-if="cost.hasEstimatedPrices"
                            class="text-ink-muted"
                            title="Some months had no market price on record and use the series average."
                            >·&nbsp;est.</span
                        >
                    </td>
                    <td class="px-4 py-3 text-right text-ink-secondary">
                        {{ euro(cost.energyEur) }}
                    </td>
                    <td class="px-4 py-3 text-right text-ink-secondary">
                        {{ euro(cost.standingEur) }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-ink">
                        {{ euro(cost.totalEur) }}
                    </td>
                    <td class="px-4 py-3 text-right text-ink-secondary">
                        {{ rate(cost.effectiveRateEur) }}/kWh
                    </td>
                    <td
                        :class="
                            cn(
                                'px-4 py-3 text-right',
                                index === 0
                                    ? 'text-good'
                                    : 'text-ink-secondary',
                            )
                        "
                    >
                        {{
                            index === 0
                                ? '—'
                                : `+${euro(cost.totalEur - cheapest)}`
                        }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
