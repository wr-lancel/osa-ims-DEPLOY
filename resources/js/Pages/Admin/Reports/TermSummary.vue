<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

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
    router.get(url, calendarId ? { calendar_id: calendarId } : {}, { preserveState: true });
}

function pdfUrl() {
    const base = route('admin.reports.term-summary.pdf');
    const params = props.selectedCalendarId ? `?calendar_id=${props.selectedCalendarId}` : '';
    return base + params;
}
</script>

<template>
    <Head title="Term Summary Report" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Term Summary Report
                </h2>
                <a
                    :href="pdfUrl()"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Download PDF
                </a>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <label for="calendar" class="block text-sm font-medium text-gray-700 mb-2">Academic term</label>
                <select
                    id="calendar"
                    :value="selectedCalendarId ?? ''"
                    class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <h3 class="text-lg font-semibold text-gray-900 px-6 py-4 border-b border-gray-200">
                    {{ summary.term_label || 'No term selected' }}
                </h3>
                <dl class="divide-y divide-gray-200">
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Enrolled students</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.total_students ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Discipline cases (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.discipline_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Complaints (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.complaints_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Guidance cases (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.guidance_cases_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Events (this term)</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.events_total ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Active organizations</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.active_organizations ?? 0 }}</dd>
                    </div>
                    <div class="px-6 py-4 flex justify-between items-center">
                        <dt class="text-sm font-medium text-gray-500">Pending candidacies</dt>
                        <dd class="text-sm font-semibold text-gray-900">{{ summary.pending_candidacies ?? 0 }}</dd>
                    </div>
                </dl>
                <p class="px-6 py-3 text-xs text-gray-500 border-t border-gray-100">
                    Generated {{ summary.generated_at }}
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
