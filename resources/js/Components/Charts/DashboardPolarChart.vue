<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    colors: {
        type: Array,
        default: () => ['#8b5cf6', '#06b6d4', '#f97316', '#10b981', '#f43f5e', '#eab308', '#6366f1', '#ec4899', '#14b8a6', '#a855f7'],
    },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.values.map((_, i) => {
                const color = props.colors[i % props.colors.length];
                return color + '99'; // add alpha
            }),
            borderColor: props.values.map((_, i) => props.colors[i % props.colors.length]),
            borderWidth: 2,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                padding: 14,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 12 },
                color: '#374151',
            },
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#f9fafb',
            bodyColor: '#d1d5db',
            borderColor: 'rgba(75, 85, 99, 0.3)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (context) => {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const pct = total ? ((context.raw / total) * 100).toFixed(1) : 0;
                    return `${context.label}: ${context.raw} (${pct}%)`;
                },
            },
        },
    },
    scales: {
        r: {
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { display: false },
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${maxHeight}px` }">
        <Chart type="polarArea" :data="chartData" :options="chartOptions" />
    </div>
</template>
