<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { dashboard } from '@/routes';
import type { Period, PeriodPreset } from '@/types';

const props = defineProps<{
    period: Period;
}>();

const PRESETS: { value: PeriodPreset; label: string }[] = [
    { value: '30d', label: '30 days' },
    { value: '3m', label: '3 months' },
    { value: '12m', label: '12 months' },
    { value: 'ytd', label: 'Year to date' },
    { value: 'all', label: 'All time' },
];

const from = ref(props.period.from);
const to = ref(props.period.to);

watch(
    () => props.period,
    (period) => {
        from.value = period.from;
        to.value = period.to;
    },
);

/**
 * Partial reload: the chart, stats and comparison refresh while the entry
 * form and scroll position stay untouched.
 */
function visit(query: Record<string, string>): void {
    router.get(dashboard.url({ query }), undefined, {
        preserveState: true,
        preserveScroll: true,
        only: ['period', 'series', 'stats', 'intervals', 'planCosts'],
    });
}

function selectPreset(preset: PeriodPreset): void {
    visit({ preset });
}

function applyCustom(): void {
    visit({ from: from.value, to: to.value });
}
</script>

<template>
    <div
        class="flex flex-wrap items-end justify-between gap-4 rounded-lg border border-hairline bg-surface p-4"
    >
        <div class="flex flex-wrap gap-2">
            <button
                v-for="preset in PRESETS"
                :key="preset.value"
                type="button"
                class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                :class="
                    period.preset === preset.value
                        ? 'border-series-1 bg-series-1 font-medium text-white'
                        : 'border-hairline text-ink-secondary hover:text-ink'
                "
                @click="selectPreset(preset.value)"
            >
                {{ preset.label }}
            </button>
        </div>

        <div class="flex flex-wrap items-end gap-2">
            <label class="flex flex-col gap-1 text-xs text-ink-muted">
                From
                <input
                    v-model="from"
                    type="date"
                    class="rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                />
            </label>
            <label class="flex flex-col gap-1 text-xs text-ink-muted">
                To
                <input
                    v-model="to"
                    type="date"
                    class="rounded-md border border-hairline bg-plane px-2 py-1.5 text-sm text-ink"
                />
            </label>
            <button
                type="button"
                class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                :class="
                    period.preset === 'custom'
                        ? 'border-series-1 text-series-1'
                        : 'border-hairline text-ink-secondary hover:text-ink'
                "
                @click="applyCustom"
            >
                Apply
            </button>
        </div>
    </div>
</template>
