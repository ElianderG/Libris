<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const props = defineProps<{
    type: 'bar' | 'doughnut';
    labels: string[];
    values: number[];
    label: string;
    money?: boolean;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
const { resolvedAppearance } = useAppearance();

let chart: { destroy: () => void } | null = null;
let request = 0;
let registered = false;

function paint(name: string, fallback: string): string {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value || fallback;
}

async function draw(): Promise<void> {
    const current = ++request;
    const {
        Chart,
        ArcElement,
        BarController,
        BarElement,
        CategoryScale,
        DoughnutController,
        Legend,
        LinearScale,
        Tooltip,
    } = await import('chart.js');

    if (current !== request || canvas.value === null) {
        return;
    }

    if (!registered) {
        Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend, LinearScale, Tooltip);
        registered = true;
    }

    chart?.destroy();

    const foreground = paint('--foreground', '#3f2a22');
    const border = paint('--border', '#e7dfd4');
    const background = paint('--background', '#faf7f2');
    const palette = ['--chart-1', '--chart-2', '--chart-3', '--chart-4', '--chart-5'].map((name, index) => paint(name, ['#c46b4a', '#3d7a72', '#355c7d', '#d4a017', '#c47b2b'][index] ?? '#c46b4a'));
    const data = props.money ? props.values.map((value) => value / 100) : props.values;

    chart = new Chart(canvas.value, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: [
                {
                    label: props.label,
                    data,
                    backgroundColor: props.type === 'doughnut' ? palette : palette[0],
                    borderColor: background,
                    borderWidth: props.type === 'doughnut' ? 2 : 0,
                    borderRadius: props.type === 'bar' ? 6 : 0,
                    maxBarThickness: 36,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: props.type === 'doughnut',
                    labels: { color: foreground },
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value = Number(context.raw);
                            const text = props.money
                                ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
                                : String(value);

                            return `${context.dataset.label ?? ''} ${text}`.trim();
                        },
                    },
                },
            },
            scales: props.type === 'bar'
                ? {
                    x: { ticks: { color: foreground }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { color: foreground },
                        grid: { color: border },
                    },
                }
                : undefined,
        },
    });
}

onMounted(() => {
    void draw();
});

watch(() => [props.labels, props.values, resolvedAppearance.value], () => {
    void draw();
});

onUnmounted(() => {
    request += 1;
    chart?.destroy();
});
</script>

<template>
    <div class="relative h-56">
        <canvas ref="canvas" role="img" :aria-label="label" />
    </div>
</template>
