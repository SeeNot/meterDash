<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { axisStyle, useChartTheme } from '@/lib/chart';
import { euro } from '@/lib/format';
import type { PlanCost } from '@/types';

const props = defineProps<{
    planCosts: PlanCost[];
}>();

const theme = useChartTheme();

/**
 * Split the bill so a low-rate plan with a heavy monthly fee is visibly
 * different from a high-rate plan with none.
 */
const data = computed<ChartData<'bar'>>(() => ({
    labels: props.planCosts.map((cost) => cost.planName),
    datasets: [
        {
            label: 'Energy',
            data: props.planCosts.map((cost) => cost.energyEur),
            backgroundColor: theme.value.series1,
            borderRadius: 4,
            borderSkipped: 'start',
            barThickness: 24,
            // A 2px surface gap keeps the two segments reading as separate.
            borderColor: theme.value.surface,
            borderWidth: { right: 2, top: 0, bottom: 0, left: 0 },
        },
        {
            label: 'Standing charge',
            data: props.planCosts.map((cost) => cost.standingEur),
            backgroundColor: theme.value.series2,
            borderRadius: 4,
            borderSkipped: 'start',
            barThickness: 24,
        },
    ],
}));

const options = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    indexAxis: 'y',
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            display: true,
            position: 'top',
            align: 'end',
            labels: {
                color: theme.value.inkSecondary,
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: 'circle',
            },
        },
        tooltip: {
            backgroundColor: theme.value.surface,
            titleColor: theme.value.ink,
            bodyColor: theme.value.inkSecondary,
            borderColor: theme.value.gridline,
            borderWidth: 1,
            padding: 10,
            usePointStyle: true,
            callbacks: {
                label: (context) =>
                    ` ${context.dataset.label}: ${euro(Number(context.parsed.x))}`,
                footer: (items) => {
                    const cost = props.planCosts[items[0].dataIndex];

                    return cost === undefined
                        ? ''
                        : `Total ${euro(cost.totalEur)} · ${cost.typeLabel}`;
                },
            },
        },
    },
    scales: {
        x: {
            ...axisStyle(theme.value),
            stacked: true,
            beginAtZero: true,
            ticks: {
                ...axisStyle(theme.value).ticks,
                callback: (value) => euro(Number(value)),
            },
        },
        y: {
            ...axisStyle(theme.value),
            stacked: true,
            grid: { display: false },
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <Bar :data="data" :options="options" />
    </div>
</template>
