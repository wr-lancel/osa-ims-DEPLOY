<script setup>
import { computed, ref, onMounted } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    label: { type: String, default: 'Count' },
    borderColor: { type: String, default: '#6366f1' },
    backgroundColor: { type: String, default: 'rgba(99, 102, 241, 0.15)' },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            fill: true,
            borderColor: props.borderColor,
            backgroundColor: props.backgroundColor,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: props.borderColor,
            pointBorderWidth: 2,
        },
    ],
}));

const chartOptions = computed(() => ({
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
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { color: '#6b7280', font: { size: 11 } },
        },
        y: {
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { color: '#6b7280', font: { size: 11 }, stepSize: 1 },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${maxHeight}px` }">
        <Chart type="line" :data="chartData" :options="chartOptions" />
    </div>
</template>
