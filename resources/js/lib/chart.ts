import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';

// vue-chartjs does not register anything for you; without this the canvas
// renders blank with a "controller is not registered" error.
Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

Chart.defaults.font.family =
    'Instrument Sans, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.maintainAspectRatio = false;

export type ChartTheme = {
    surface: string;
    ink: string;
    inkSecondary: string;
    inkMuted: string;
    gridline: string;
    baseline: string;
    series1: string;
    series2: string;
    deEmphasis: string;
};

const TOKENS: Record<keyof ChartTheme, string> = {
    surface: '--surface',
    ink: '--ink',
    inkSecondary: '--ink-secondary',
    inkMuted: '--ink-muted',
    gridline: '--gridline',
    baseline: '--baseline',
    series1: '--series-1',
    series2: '--series-2',
    deEmphasis: '--de-emphasis',
};

/**
 * Read the chart palette off the document so the canvas matches the DOM.
 */
export function readChartTheme(): ChartTheme {
    const styles = getComputedStyle(document.documentElement);
    const read = (token: string) => styles.getPropertyValue(token).trim();

    return Object.fromEntries(
        Object.entries(TOKENS).map(([key, token]) => [key, read(token)]),
    ) as unknown as ChartTheme;
}

const FALLBACK_THEME: ChartTheme = {
    surface: '#fcfcfb',
    ink: '#0b0b0b',
    inkSecondary: '#52514e',
    inkMuted: '#898781',
    gridline: '#e1e0d9',
    baseline: '#c3c2b7',
    series1: '#2a78d6',
    series2: '#eb6834',
    deEmphasis: '#c3c2b7',
};

/**
 * A reactive chart palette that follows the operating system colour scheme.
 */
export function useChartTheme(): Ref<ChartTheme> {
    const theme = ref<ChartTheme>(FALLBACK_THEME);
    const query = window.matchMedia('(prefers-color-scheme: dark)');
    const sync = () => {
        theme.value = readChartTheme();
    };

    onMounted(() => {
        sync();
        query.addEventListener('change', sync);
    });

    onBeforeUnmount(() => {
        query.removeEventListener('change', sync);
    });

    return theme;
}

/**
 * Recessive axis and gridline styling shared by every chart.
 */
export function axisStyle(theme: ChartTheme) {
    return {
        grid: { color: theme.gridline, drawTicks: false },
        border: { color: theme.baseline },
        ticks: { color: theme.inkMuted, padding: 8 },
    };
}

export { Chart };
