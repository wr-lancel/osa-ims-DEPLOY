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
    activeTab: {
        type: String,
        default: 'violations',
    },
    riskStudents: {
        type: Object,
        default: null,
    },
    riskStats: {
        type: Array,
        default: null,
    },
    riskFilters: {
        type: Object,
        default: () => ({}),
    },
});

const { notify } = useNotification();

// --- Tab ---
const currentTab = ref(props.activeTab || 'violations');

function switchTab(tab) {
    currentTab.value = tab;
    router.get(route('admin.discipline.index'), { tab }, {
        preserveState: false,
        replace: true,
    });
}

// --- Risk Assessment ---
const riskSearch = ref(props.riskFilters?.risk_search || '');
const riskLevel = ref(props.riskFilters?.risk_level || '');
const isComputingAll = ref(false);

let riskSearchDebounce = null;
watch(riskSearch, () => {
    clearTimeout(riskSearchDebounce);
    riskSearchDebounce = setTimeout(() => applyRiskFilters(), 350);
});
watch(riskLevel, () => applyRiskFilters());

function applyRiskFilters() {
    router.get(route('admin.discipline.index'), {
        tab: 'risk',
        risk_search: riskSearch.value || undefined,
        risk_level: riskLevel.value || undefined,
    }, { preserveState: true, replace: true });
}

function computeAllRisk() {
    isComputingAll.value = true;
    router.post(route('admin.discipline.risk.compute-all'), {}, {
        onFinish: () => { isComputingAll.value = false; },
    });
}

function computeOneRisk(studentNumber) {
    router.post(route('admin.discipline.risk.compute-one', { student: studentNumber }), {}, {
        preserveScroll: true,
    });
}

const riskLevelOptions = [
    { value: '', label: 'All Levels' },
    { value: 'High', label: 'High' },
    { value: 'Moderate', label: 'Moderate' },
    { value: 'Low', label: 'Low' },
];

const riskBadgeClass = (level) => {
    if (level === 'High')     return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
    if (level === 'Moderate') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
    if (level === 'Low')      return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
    return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
};

const riskBarColor = (level) => {
    if (level === 'High')     return 'bg-red-500';
    if (level === 'Moderate') return 'bg-yellow-500';
    if (level === 'Low')      return 'bg-green-500';
    return 'bg-gray-300 dark:bg-gray-600';
};

const statCardClass = (color) => {
    const map = {
        blue:   'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100',
        red:    'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100',
        yellow: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-900 dark:text-yellow-100',
        green:  'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100',
        gray:   'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100',
    };
    return map[color] || map.gray;
};

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

        <!-- Tab Switcher -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-6">
                <button
                    @click="switchTab('violations')"
                    class="pb-3 text-sm font-medium border-b-2 transition"
                    :class="currentTab === 'violations'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                >
                    Violations
                </button>
                <button
                    @click="switchTab('risk')"
                    class="pb-3 text-sm font-medium border-b-2 transition"
                    :class="currentTab === 'risk'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                >
                    Risk Assessment
                </button>
            </nav>
        </div>

        <!-- Violations Tab -->
        <div v-if="currentTab === 'violations'" class="space-y-6">
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
        </div><!-- end violations tab -->

        <!-- Risk Assessment Tab -->
        <div v-if="currentTab === 'risk'" class="space-y-6">
            <!-- Stats -->
            <div v-if="riskStats" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    v-for="stat in riskStats"
                    :key="stat.title"
                    class="rounded-lg border shadow-sm p-4"
                    :class="statCardClass(stat.color)"
                >
                    <p class="text-xs font-medium opacity-70">{{ stat.title }}</p>
                    <p class="text-2xl font-bold mt-1">{{ stat.value }}</p>
                </div>
            </div>

            <!-- Filters + Compute All -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input
                    v-model="riskSearch"
                    type="text"
                    placeholder="Search by name or student number..."
                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                />
                <select
                    v-model="riskLevel"
                    class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                >
                    <option v-for="opt in riskLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <button
                    @click="computeAllRisk"
                    :disabled="isComputingAll"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60 transition whitespace-nowrap"
                >
                    <svg class="h-4 w-4" :class="{ 'animate-spin': isComputingAll }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ isComputingAll ? 'Computing...' : 'Compute All Scores' }}
                </button>
            </div>

            <!-- Risk Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Course / Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-40">Risk Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Violations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Guidance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Computed</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template v-if="riskStudents && riskStudents.data.length">
                                <tr v-for="student in riskStudents.data" :key="student.student_number"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <!-- Student -->
                                    <td class="px-6 py-4">
                                        <Link
                                            :href="route('admin.students.profile', { student: student.student_number })"
                                            class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition"
                                            @click.stop
                                        >
                                            {{ student.student_name }}
                                        </Link>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ student.student_number }}</div>
                                    </td>
                                    <!-- Course/Year -->
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                        <div>{{ student.course || '—' }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">{{ student.year_level || '' }}</div>
                                    </td>
                                    <!-- Score bar -->
                                    <td class="px-6 py-4">
                                        <template v-if="student.risk_score !== null">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                                    <div
                                                        class="h-2 rounded-full transition-all"
                                                        :class="riskBarColor(student.risk_level)"
                                                        :style="{ width: Math.min(100, student.risk_score) + '%' }"
                                                    />
                                                </div>
                                                <span class="text-xs font-mono text-gray-700 dark:text-gray-300 w-8 text-right">
                                                    {{ parseFloat(student.risk_score).toFixed(1) }}
                                                </span>
                                            </div>
                                        </template>
                                        <span v-else class="text-xs text-gray-400 italic">Not computed</span>
                                    </td>
                                    <!-- Level badge -->
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="student.risk_level"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="riskBadgeClass(student.risk_level)"
                                        >
                                            {{ student.risk_level }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400">—</span>
                                    </td>
                                    <!-- Violations -->
                                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                                        <template v-if="student.factors">
                                            Minor: {{ student.factors.violation_history?.minor ?? 0 }} ·
                                            Mod: {{ student.factors.violation_history?.moderate ?? 0 }} ·
                                            Major: {{ student.factors.violation_history?.major ?? 0 }}
                                        </template>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <!-- Guidance -->
                                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                                        <template v-if="student.factors">
                                            Referral: {{ student.factors.guidance_incidents?.referral ?? 0 }} ·
                                            Other: {{ student.factors.guidance_incidents?.other ?? 0 }}
                                        </template>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <!-- Last computed -->
                                    <td class="px-6 py-4 text-xs text-gray-400 dark:text-gray-500">
                                        {{ student.last_computed_at ? new Date(student.last_computed_at).toLocaleDateString() : '—' }}
                                    </td>
                                    <!-- Action -->
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="computeOneRisk(student.student_number)"
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            Recompute
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                    No students found. Click "Compute All Scores" to generate risk scores.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <Pagination v-if="riskStudents" :data="riskStudents" />
                </div>
            </div>

            <!-- Formula notes -->
            
        </div><!-- end risk tab -->

        <!-- Modal -->
        <DisciplineFormModal :show="showModal" :violation="selectedViolation" :enrollments="enrollments"
            :status-options="workflowSteps"
            :violation-types="violationTypes"
            @close="closeModal" @saved="handleSaved" />
    </AdminLayout>
</template>
