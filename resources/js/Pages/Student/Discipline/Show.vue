<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';

const props = defineProps({
    violation: {
        type: Object,
        required: true,
    },
    meetings: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
    workflowSteps: {
        type: Array,
        default: () => [],
    },
    terminalStatuses: {
        type: Array,
        default: () => [],
    },
});

const getStatusColor = (status) => {
    if (status === 'Resolved') return 'bg-green-100 text-green-800';
    if (status === 'Under Investigation') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const getSeverityColor = (severity) => {
    if (severity === 'Major') return 'bg-red-100 text-red-800';
    if (severity === 'Minor') return 'bg-green-100 text-green-800';
    return 'bg-gray-100 text-gray-800';
};

const getMeetingStatusColor = (status) => {
    if (status === 'completed') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-red-100 text-red-800';
    if (status === 'rescheduled') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};
</script>

<template>
    <Head :title="`Case #${violation.discipline_id} - Discipline`" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Case #{{ violation.discipline_id }}
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors self-start sm:self-auto"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    My Violations
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Violation Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Date Reported</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.violation_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Offense / Type</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.violation_type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Severity</label>
                        <span
                            v-if="violation.severity"
                            class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getSeverityColor(violation.severity)"
                        >
                            {{ violation.severity }}
                        </span>
                        <span v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400">—</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Status</label>
                        <span
                            class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(violation.status)"
                        >
                            {{ violation.status }}
                        </span>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4 pt-2">
                        <StatusProgressBar
                            :steps="workflowSteps"
                            :current-status="violation.status"
                            :terminal-statuses="terminalStatuses"
                        />
                    </div>
                    <div v-if="violation.date_resolved">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Date Resolved</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.date_resolved }}</p>
                    </div>
                    <div v-if="violation.term_label">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Term</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.term_label }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Description</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.description || '—' }}</p>
                    </div>
                    <div v-if="violation.sanction" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Sanction</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.sanction }}</p>
                    </div>
                    <div v-if="violation.remarks" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Remarks</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.remarks }}</p>
                    </div>
                </div>
            </div>

            <!-- Narrative Report (read-only) -->
            <div v-if="violation.narrative_report || violation.narrative_report_file_url" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Narrative Report</h3>

                <p v-if="violation.narrative_report" class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap mb-4">{{ violation.narrative_report }}</p>

                <div v-if="violation.narrative_report_file_url" class="mt-2">
                    <a :href="violation.narrative_report_file_url" target="_blank"
                        class="inline-flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:bg-gray-800 transition-colors group">
                        <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                            <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ violation.narrative_report_file_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Click to download</p>
                        </div>
                    </a>
                </div>
            </div>

            <div v-if="meetings && meetings.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scheduled Meetings</h3>
                <div class="overflow-x-auto -mx-6 px-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="m in meetings" :key="m.meeting_id" class="hover:bg-gray-50 dark:bg-gray-900">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ m.meeting_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ m.meeting_time || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ m.location || '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getMeetingStatusColor(m.status)"
                                >
                                    {{ m.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <div v-if="history && history.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status History</h3>
                <div class="space-y-3">
                    <div
                        v-for="(h, i) in history"
                        :key="i"
                        class="flex items-start gap-3 text-sm"
                    >
                        <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400 shrink-0">{{ h.created_at }}</span>
                        <span class="text-gray-700 dark:text-gray-200">
                            {{ h.old_status || '—' }} → {{ h.new_status || '—' }}
                            <span v-if="h.note" class="text-gray-500 dark:text-gray-400 dark:text-gray-400">({{ h.note }})</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
