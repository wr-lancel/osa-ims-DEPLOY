<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useNotification } from '@/composables/useNotification';
import SortableHeader from '@/Components/SortableHeader.vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import DisciplineFormModal from '@/Components/Admin/DisciplineFormModal.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    violations: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    dashboardStats: {
        type: Array,
        default: () => [],
    },
    enrollments: {
        type: Array,
        default: () => [],
    },
    terms: {
        type: Array,
        default: () => [],
    },
    workflowSteps: {
        type: Array,
        default: () => [],
    },
    violationTypes: {
        type: Array,
        default: () => [],
    },
    voidedCount: {
        type: Number,
        default: 0,
    },
});

const { notify } = useNotification();

const showModal = ref(false);
const selectedViolation = ref(null);

const search = ref(props.filters.search || '');
const severity = ref(props.filters.severity || '');
const status = ref(props.filters.status || '');
const acadId = ref(props.filters.acad_id || '');
const sortBy = ref(props.filters.sort_by || '');
const sortDir = ref(props.filters.sort_dir || 'desc');
const showVoided = ref(props.filters.show_voided === '1' || props.filters.show_voided === true);

const handleSort = ({ column, dir }) => {
    sortBy.value = column;
    sortDir.value = dir;
    applyFilters();
};

const severityOptions = [
    { value: '', label: 'All Severities' },
    { value: 'Minor', label: 'Minor' },
    { value: 'Moderate', label: 'Moderate' },
    { value: 'Major', label: 'Major' },
];

const statusOptions = computed(() => [
    { value: '', label: 'All Statuses' },
    ...props.workflowSteps.map(s => ({ value: s.value, label: s.label })),
]);

function applyFilters() {
    router.get(route('admin.discipline.index'), {
        search: search.value || undefined,
        severity: severity.value || undefined,
        status: status.value || undefined,
        acad_id: acadId.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
        show_voided: showVoided.value ? '1' : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['violations', 'filters', 'dashboardStats', 'enrollments', 'terms', 'workflowSteps', 'violationTypes'],
    });
}

let searchDebounce = null;
watch(search, (val) => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([severity, status, acadId, showVoided], () => applyFilters());



const openAddModal = () => {
    selectedViolation.value = null;
    showModal.value = true;
};

const openEditModal = (violation) => {
    selectedViolation.value = violation;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedViolation.value = null;
};

const handleSaved = () => {
    closeModal();
    router.reload({ only: ['violations', 'dashboardStats'] });
};

const goToDetail = (violation) => {
    router.visit(route('admin.discipline.show', violation.discipline_id));
};

const isExporting = ref(false);

const exportPdf = async () => {
    isExporting.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (severity.value) params.append('severity', severity.value);
        if (status.value) params.append('status', status.value);
        if (acadId.value) params.append('acad_id', acadId.value);

        const response = await axios.get(route('admin.discipline.export.pdf') + (params.toString() ? '?' + params.toString() : ''), {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'violations_report.pdf');
        document.body.appendChild(link);
        link.click();
        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export PDF:', error);
        notify('error', 'Failed to generate PDF.');
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>

    <Head title="Discipline Unit" />

    <AdminLayout>
        <LoadingOverlay :show="isExporting" message="Generating PDF... Please wait." />
        <template #header>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Discipline Unit
                </h2>
                <div class="flex items-center gap-2">
                    <!-- Secondary actions group -->
                    <Link :href="route('admin.discipline.complaints.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        Complaints Inbox
                    </Link>

                    <button type="button"
                        title="Show voided (invalidated) records"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border text-sm font-medium transition-colors"
                        :class="showVoided
                            ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-700 dark:text-red-300'
                            : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'"
                        @click="showVoided = !showVoided">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Voided
                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-xs font-bold"
                            :class="showVoided ? 'bg-red-200 dark:bg-red-800 text-red-700 dark:text-red-300' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'">
                            {{ voidedCount }}
                        </span>
                    </button>

                    <button type="button"
                        title="Export current filtered results to PDF"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        :disabled="isExporting"
                        @click="exportPdf">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export PDF
                    </button>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>

                    <PrimaryButton v-if="!showVoided" @click="openAddModal">
                        Add Violation
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Filters (auto-apply; debounced search) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Search</label>
                        <input id="search" v-model="search" type="text"
                            placeholder="Student name, ID, case ID, or violation..."
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Term</label>
                        <select id="term" v-model="acadId"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">All Terms</option>
                            <option v-for="t in terms" :key="t.calendar_id" :value="t.calendar_id">
                                {{ t.display_label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="severity" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Severity</label>
                        <select id="severity" v-model="severity"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select id="status" v-model="status"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Voided notice banner -->
            <div v-if="showVoided" class="flex items-center gap-2 px-4 py-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-sm text-red-700 dark:text-red-300">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                Showing voided (invalidated) violation records. These are kept for transparency only.
            </div>

            <!-- Violations Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Violation ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Student
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Violation Type
                                </th>
                                <SortableHeader column="violation_date" label="Date" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <SortableHeader column="severity" label="Severity" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <th v-if="showVoided" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Void Reason
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="violations.data && violations.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No violations found.
                                </td>
                            </tr>
                            <tr v-for="violation in violations.data" :key="violation.discipline_id"
                                class="hover:bg-gray-50 dark:bg-gray-900 cursor-pointer" @click="goToDetail(violation)">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    #{{ violation.discipline_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>
                                        <div class="font-medium">{{ violation.student_name }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs">{{ violation.student_number }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="max-w-xs truncate" :title="violation.violation_type">
                                        {{ violation.violation_type }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ violation.violation_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="violation.severity"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="{
                                            'bg-red-100 text-red-800': violation.severity === 'Major',
                                            'bg-yellow-100 text-yellow-800': violation.severity === 'Moderate',
                                            'bg-green-100 text-green-800': violation.severity === 'Minor',
                                        }">
                                        {{ violation.severity }}
                                    </span>
                                    <span v-else class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                    <span v-if="violation.voided_at"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                                        Voided
                                    </span>
                                    <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="violation.status_color === 'green'
                                            ? 'bg-green-100 text-green-800'
                                            : violation.status_color === 'yellow'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100'">
                                        {{ violation.status }}
                                    </span>
                                </td>
                                <td v-if="showVoided" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400" @click.stop>
                                    {{ violation.void_reason || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                    @click.stop>
                                    <Link :href="route('admin.discipline.show', violation.discipline_id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                        View
                                    </Link>
                                    <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        @click.stop="openEditModal(violation)">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :data="violations" />
            </div>
        </div>

        <!-- Modal -->
        <DisciplineFormModal :show="showModal" :violation="selectedViolation" :enrollments="enrollments"
            :status-options="workflowSteps"
            :violation-types="violationTypes"
            @close="closeModal" @saved="handleSaved" />
    </AdminLayout>
</template>
