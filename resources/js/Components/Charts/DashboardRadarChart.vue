<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    label: { type: String, default: 'Cases' },
    borderColor: { type: String, default: '#8b5cf6' },
    backgroundColor: { type: String, default: 'rgba(139, 92, 246, 0.2)' },
    maxHeight: { type: Number, default: 280 },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            borderColor: props.borderColor,
            backgroundColor: props.backgroundColor,
            borderWidth: 2,
            pointBackgroundColor: props.borderColor,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8,
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
        },
    },
    scales: {
        r: {
            grid: { color: 'rgba(0,0,0,0.06)' },
            angleLines: { color: 'rgba(0,0,0,0.06)' },
            ticks: {
                color: '#6b7280',
                font: { size: 10 },
                backdropColor: 'transparent',
                stepSize: 1,
            },
            pointLabels: {
                color: '#374151',
                font: { size: 12 },
            },
            beginAtZero: true,
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${maxHeight}px` }">
        <Chart type="radar" :data="chartData" :options="chartOptions" />
    </div>
</template>
