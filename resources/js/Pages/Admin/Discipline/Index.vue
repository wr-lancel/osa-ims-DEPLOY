<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import DisciplineFormModal from '@/Components/Admin/DisciplineFormModal.vue';
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
});

const showModal = ref(false);
const selectedViolation = ref(null);

const search = ref(props.filters.search || '');
const severity = ref(props.filters.severity || '');
const status = ref(props.filters.status || '');
const acadId = ref(props.filters.acad_id || '');

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
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

let searchDebounce = null;
watch(search, (val) => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([severity, status, acadId], () => applyFilters());



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

const exportPdf = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (severity.value) params.append('severity', severity.value);
    if (status.value) params.append('status', status.value);
    if (acadId.value) params.append('acad_id', acadId.value);

    window.location.href = route('admin.discipline.export.pdf') + (params.toString() ? '?' + params.toString() : '');
};
</script>

<template>

    <Head title="Discipline Unit" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Discipline Unit
                </h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.discipline.complaints.index')"
                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                        Complaints Inbox
                    </Link>
                    <SecondaryButton @click="exportPdf">
                        Export PDF
                    </SecondaryButton>
                    <PrimaryButton @click="openAddModal">
                        Add Violation
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Filters (auto-apply; debounced search) -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input id="search" v-model="search" type="text"
                            placeholder="Student name, ID, case ID, or violation..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                        <select id="term" v-model="acadId"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Terms</option>
                            <option v-for="t in terms" :key="t.calendar_id" :value="t.calendar_id">
                                {{ t.display_label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                        <select id="severity" v-model="severity"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" v-model="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Violations Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Violation ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Violation Type
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Severity
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="violations.data && violations.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No violations found.
                                </td>
                            </tr>
                            <tr v-for="violation in violations.data" :key="violation.discipline_id"
                                class="hover:bg-gray-50 cursor-pointer" @click="goToDetail(violation)">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{{ violation.discipline_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium">{{ violation.student_name }}</div>
                                        <div class="text-gray-500 text-xs">{{ violation.student_number }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate" :title="violation.violation_type">
                                        {{ violation.violation_type }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="violation.status_color === 'green'
                                            ? 'bg-green-100 text-green-800'
                                            : violation.status_color === 'yellow'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-gray-100 text-gray-800'">
                                        {{ violation.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                    @click.stop>
                                    <Link :href="route('admin.discipline.show', violation.discipline_id)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        View
                                    </Link>
                                    <button type="button" class="text-indigo-600 hover:text-indigo-900"
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
