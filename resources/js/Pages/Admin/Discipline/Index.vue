<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import DisciplineFormModal from '@/Components/Admin/DisciplineFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

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
    students: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const selectedViolation = ref(null);

const search = ref(props.filters.search || '');
const severity = ref(props.filters.severity || '');
const status = ref(props.filters.status || '');

const severityOptions = [
    { value: '', label: 'All Severities' },
    { value: 'Minor', label: 'Minor' },
    { value: 'Moderate', label: 'Moderate' },
    { value: 'Major', label: 'Major' },
];

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'Pending', label: 'Pending' },
    { value: 'Under Investigation', label: 'Under Investigation' },
    { value: 'Resolved', label: 'Resolved' },
];

const applyFilters = () => {
    router.get(route('admin.discipline.index'), {
        search: search.value || undefined,
        severity: severity.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    severity.value = '';
    status.value = '';
    router.get(route('admin.discipline.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
</script>

<template>
    <Head title="Discipline Unit" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Discipline Unit
                </h2>
                <PrimaryButton @click="openAddModal">
                    Add Violation
                </PrimaryButton>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Filters -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>
                        <input
                            id="search"
                            v-model="search"
                            type="text"
                            placeholder="Student name, ID, or violation..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Severity Filter -->
                    <div>
                        <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">
                            Severity
                        </label>
                        <select
                            id="severity"
                            v-model="severity"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="option in severityOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select
                            id="status"
                            v-model="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end space-x-3">
                    <SecondaryButton @click="resetFilters">
                        Reset
                    </SecondaryButton>
                    <PrimaryButton @click="applyFilters">
                        Apply Filters
                    </PrimaryButton>
                </div>
            </div>

            <!-- Violations Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Violation ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Violation Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Severity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                            <tr
                                v-for="violation in violations.data"
                                :key="violation.discipline_id"
                                class="hover:bg-gray-50"
                            >
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
                                    <span
                                        v-if="violation.severity"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-red-100 text-red-800': violation.severity === 'Major',
                                            'bg-yellow-100 text-yellow-800': violation.severity === 'Moderate',
                                            'bg-green-100 text-green-800': violation.severity === 'Minor',
                                        }"
                                    >
                                        {{ violation.severity }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': violation.status === 'Resolved',
                                            'bg-yellow-100 text-yellow-800': violation.status === 'Under Investigation',
                                            'bg-gray-100 text-gray-800': violation.status === 'Pending',
                                        }"
                                    >
                                        {{ violation.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        @click="openEditModal(violation)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="violations.links && violations.links.length > 3"
                    class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a
                                v-if="violations.links[0].url"
                                :href="violations.links[0].url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </a>
                            <a
                                v-if="violations.links[violations.links.length - 1].url"
                                :href="violations.links[violations.links.length - 1].url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ violations.meta.from || 0 }}</span>
                                    to
                                    <span class="font-medium">{{ violations.meta.to || 0 }}</span>
                                    of
                                    <span class="font-medium">{{ violations.meta.total || 0 }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a
                                        v-for="(link, index) in violations.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === violations.links.length - 1 ? 'rounded-r-md' : '',
                                            !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <DisciplineFormModal
            :show="showModal"
            :violation="selectedViolation"
            :students="students"
            @close="closeModal"
            @saved="handleSaved"
        />
    </AdminLayout>
</template>
