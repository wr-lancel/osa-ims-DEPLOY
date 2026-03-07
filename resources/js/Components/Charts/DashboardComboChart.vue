<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    barLabel: { type: String, default: 'Violations' },
    lineLabel: { type: String, default: 'Average' },
    barColors: {
        type: Array,
        default: () => ['#14b8a6', '#0d9488', '#0ea5e9', '#0284c7', '#10b981', '#059669', '#6366f1', '#4f46e5', '#f59e0b', '#d97706'],
    },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => {
    const total = props.values.reduce((a, b) => a + b, 0);
    const avg = props.values.length ? total / props.values.length : 0;
    const avgLine = props.values.map(() => Math.round(avg * 100) / 100);

    return {
        labels: props.labels,
        datasets: [
            {
                type: 'bar',
                label: props.barLabel,
                data: props.values,
                backgroundColor: props.values.map((_, i) => props.barColors[i % props.barColors.length]),
                borderColor: props.values.map((_, i) => props.barColors[i % props.barColors.length]),
                borderWidth: 1,
                borderRadius: 6,
                order: 2,
            },
            {
                type: 'line',
                label: props.lineLabel,
                data: avgLine,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                fill: false,
                tension: 0,
                order: 1,
            },
        ],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                padding: 16,
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
            mode: 'index',
            intersect: false,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#6b7280', font: { size: 11 } },
        },
        y: {
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: { color: '#6b7280', font: { size: 11 }, stepSize: 1 },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${maxHeight}px` }">
        <Chart type="bar" :data="chartData" :options="chartOptions" />
    </div>
</template>
