<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    label: { type: String, default: 'Count' },
    horizontal: { type: Boolean, default: false },
    colors: { type: Array, default: () => ['#14b8a6', '#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#f43f5e', '#6366f1', '#ec4899', '#06b6d4', '#eab308'] },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            backgroundColor: props.values.map((_, i) => props.colors[i % props.colors.length]),
            borderColor: props.values.map((_, i) => props.colors[i % props.colors.length]),
            borderWidth: 1,
            borderRadius: 6,
        },
    ],
}));

const chartOptions = computed(() => ({
    indexAxis: props.horizontal ? 'y' : 'x',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
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
            grid: { display: props.horizontal, color: 'rgba(0,0,0,0.05)' },
            ticks: { color: '#6b7280', font: { size: 11 } },
            beginAtZero: true,
        },
        y: {
            grid: { display: !props.horizontal, color: 'rgba(0,0,0,0.05)' },
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
