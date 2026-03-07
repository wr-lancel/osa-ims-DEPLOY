<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    currentValues: { type: Array, default: () => [] },
    previousValues: { type: Array, default: () => [] },
    currentLabel: { type: String, default: 'Current term' },
    previousLabel: { type: String, default: 'Previous term' },
    currentColor: { type: String, default: '#6366f1' },
    previousColor: { type: String, default: '#cbd5e1' },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.currentLabel,
            data: props.currentValues,
            backgroundColor: props.currentColor,
            borderColor: props.currentColor,
            borderWidth: 1,
            borderRadius: 6,
        },
        {
            label: props.previousLabel,
            data: props.previousValues,
            backgroundColor: props.previousColor,
            borderColor: props.previousColor,
            borderWidth: 1,
            borderRadius: 6,
        },
    ],
}));

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
