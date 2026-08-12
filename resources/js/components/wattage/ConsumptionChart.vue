<script setup lang="ts">
import type { ChartData, ChartOptions } from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { axisStyle, useChartTheme } from '@/lib/chart';
import { kwh } from '@/lib/format';
import type { ConsumptionSeries, ConsumptionStats } from '@/types';

const props = defineProps<{
    series: ConsumptionSeries;
    stats: ConsumptionStats;
}>();

const theme = useChartTheme();

const data = computed<ChartData<'line'>>(() => ({
    labels: props.series.labels,
    datasets: [
        {
            label: 'Consumption',
            data: props.series.kwhPerDay,
            borderColor: theme.value.series1,
            backgroundColor: `color-mix(in srgb, ${theme.value.series1} 10%, transparent)`,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointHitRadius: 16,
            pointBackgroundColor: theme.value.series1,
            pointBorderColor: theme.value.surface,
            pointBorderWidth: 2,
            fill: true,
            tension: 0.25,
        },
        {
            label: 'Median',
            data: props.series.labels.map(() => props.stats.medianKwhPerDay),
            borderColor: theme.value.deEmphasis,
            borderWidth: 2,
            borderDash: [4, 4],
            pointRadius: 0,
            pointHitRadius: 0,
            fill: false,
        },
    ],
}));

const options = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
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
            displayColors: true,
            usePointStyle: true,
            callbacks: {
                label: (context) =>
                    ` ${context.dataset.label}: ${kwh(Number(context.parsed.y), 2)} kWh/day`,
                afterBody: (items) => {
                    const interval = props.series.kwh[items[0].dataIndex];

                    return interval === undefined
                        ? ''
                        : `Total for period: ${kwh(interval)} kWh`;
                },
            },
        },
    },
    scales: {
        x: axisStyle(theme.value),
        y: {
            ...axisStyle(theme.value),
            beginAtZero: true,
            title: {
                display: true,
                text: 'kWh per day',
                color: theme.value.inkMuted,
            },
        },
    },
}));
</script>

<template>
    <div class="h-72">
        <Line :data="data" :options="options" />
    </div>
</template>
