<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    calendars: {
        type: Array,
        default: () => [],
    },
    selectedCalendarId: {
        type: [Number, String],
        default: null,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

function onCalendarChange(calendarId) {
    const url = route('admin.reports.term-summary');
    router.get(url, calendarId ? { calendar_id: calendarId } : {}, { 
        preserveState: true,
        showProgress: false,
    });
}

function pdfUrl() {
    const base = route('admin.reports.term-summary.pdf');
    const params = props.selectedCalendarId ? `?calendar_id=${props.selectedCalendarId}` : '';
    return base + params;
}

const isExporting = ref(false);

const exportPdf = async () => {
    isExporting.value = true;
    try {
        const response = await axios.get(pdfUrl(), {
            responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const termLabel = props.summary?.term_label ? props.summary.term_label.replace(/[^a-z0-9]/gi, '_').toLowerCase() : 'all';
        link.setAttribute('download', `term_summary_report_${termLabel}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export PDF:', error);
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>
    <Head title="Term Summary Report" />

    <AdminLayout>
        <LoadingOverlay :show="isExporting" message="Generating PDF... Please wait." />
        <template #header>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Term Summary Report
                </h2>
                <button
                    @click="exportPdf()"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    :disabled="isExporting"
                >
                    {{ isExporting ? 'Generating PDF...' : 'Download PDF' }}
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                <label for="calendar" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Academic term</label>
                <select
                    id="calendar"
                    :value="selectedCalendarId ?? ''"
                    class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                    @change="onCalendarChange($event.target.value ? Number($event.target.value) : null)"
                >
                    <option value="">Current active term</option>
                    <option
                        v-for="cal in calendars"
                        :key="cal.calendar_id"
                        :value="cal.calendar_id"
                    >
                        {{ cal.label }}
                    </option>
                </select>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    {{ summary.term_label || 'No term selected' }}
                </h3>
                <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Enrolled students</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.total_students ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Discipline cases (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.discipline_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Complaints (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.complaints_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Guidance cases (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.guidance_cases_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Events (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.events_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active organizations</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.active_organizations ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending candidacies</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ summary.pending_candidacies ?? 0 }}</dd>
                    </div>
                </dl>
                <p class="px-6 py-3 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700">
                    Generated {{ summary.generated_at }}
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
