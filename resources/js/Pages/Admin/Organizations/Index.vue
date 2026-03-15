<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SortableHeader from '@/Components/SortableHeader.vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import OrganizationFormModal from '@/Components/Admin/OrganizationFormModal.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { useNotification } from '@/composables/useNotification';
import ExportConfirmDialog from '@/Components/ExportConfirmDialog.vue';
import axios from 'axios';

const props = defineProps({
    organizations: {
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
    organizationTypes: {
        type: Array,
        default: () => ['Academic', 'Cultural', 'Governance', 'Special Interest'],
    },
});

const activeTab = ref('organizations');
const showModal = ref(false);
const selectedOrganization = ref(null);

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');
const sortBy = ref(props.filters.sort_by || '');
const sortDir = ref(props.filters.sort_dir || 'desc');

const handleSort = ({ column, dir }) => {
    sortBy.value = column;
    sortDir.value = dir;
    applyFilters();
};

const typeOptions = computed(() => [
    { value: '', label: 'All Types' },
    ...props.organizationTypes.map(t => ({ value: t, label: t })),
]);

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const applyFilters = () => {
    router.get(route('admin.organizations.index'), {
        search: search.value || undefined,
        type: type.value || undefined,
        status: status.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['organizations', 'filters', 'dashboardStats', 'organizationTypes'],
    });
};

let searchDebounce = null;
watch(search, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([type, status], () => applyFilters());

const openAddModal = () => {
    selectedOrganization.value = null;
    showModal.value = true;
};

const openEditModal = (organization) => {
    selectedOrganization.value = organization;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedOrganization.value = null;
};

const handleSaved = () => {
    closeModal();
    router.reload({ only: ['organizations', 'dashboardStats'] });
};

const { notify } = useNotification();
const isExporting = ref(false);
const showExportDialog = ref(false);

const exportPdf = async () => {
    showExportDialog.value = false;
    isExporting.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (type.value) params.append('type', type.value);
        if (status.value) params.append('status', status.value);

        const response = await axios.get(route('admin.organizations.export.pdf') + (params.toString() ? '?' + params.toString() : ''), {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'organizations_list.pdf');
        document.body.appendChild(link);
        link.click();
        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export PDF:', error);
        notify('error', 'Failed to generate PDF. Please try again.');
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>

    <Head title="Organization Unit" />

    <AdminLayout>
        <LoadingOverlay :show="isExporting" message="Generating PDF... Please wait." />
        <ExportConfirmDialog :show="showExportDialog" @confirm="exportPdf" @cancel="showExportDialog = false" />
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 transition-colors">
                    Organization Unit
                </h2>
                <div class="flex items-center gap-2">
                    <button
                        v-if="activeTab === 'organizations'"
                        @click="activeTab = 'events'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        View Events
                    </button>
                    <button
                        v-else
                        @click="activeTab = 'organizations'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        View Organizations
                    </button>
                    <Link
                        v-if="activeTab === 'organizations'"
                        :href="route('admin.organizations.candidacies.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Candidacy Applications
                    </Link>
                    <button
                        v-if="activeTab === 'organizations'"
                        title="Export current filtered results to PDF"
                        @click="showExportDialog = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export PDF
                    </button>
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
                    <PrimaryButton v-if="activeTab === 'organizations'" @click="openAddModal">
                        Add Organization
                    </PrimaryButton>
                    <Link v-else :href="route('admin.organizations.events.index')">
                        <PrimaryButton>
                            Manage Events
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Organizations Tab -->
            <div v-if="activeTab === 'organizations'">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 transition-colors">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors">
                                Search
                            </label>
                            <input id="search" v-model="search" type="text"
                                placeholder="Organization name, code, or president..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100" />
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors">
                                Type
                            </label>
                            <select id="type" v-model="type"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors">
                                Status
                            </label>
                            <select id="status" v-model="status"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>


                </div>

                <!-- Organizations Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden transition-colors">
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                                <tr>
                                    <SortableHeader column="org_name" label="Organization Name" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <SortableHeader column="type" label="Type" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">
                                        Leadership
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">
                                        Members
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">
                                        Established
                                    </th>
                                    <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                                <tr v-if="organizations.data && organizations.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                        No organizations found.
                                    </td>
                                </tr>
                                <tr v-for="org in organizations.data" :key="org.org_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('admin.organizations.show', org.org_id)"
                                            class="flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img v-if="org.logo_url" :src="org.logo_url" class="h-10 w-10 rounded-full object-cover border border-gray-200 dark:border-gray-600 transition-colors" alt="Logo" />
                                                <div v-else class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center border border-indigo-200 dark:border-indigo-800/50 transition-colors">
                                                    <span class="text-indigo-700 dark:text-indigo-300 font-bold text-sm transition-colors">{{ org.org_code.substring(0, 2).toUpperCase() }}</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">{{ org.org_name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 transition-colors">{{ org.org_code }}</div>
                                            </div>
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ org.type || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200 transition-colors">
                                        <div v-if="org.president_name || org.adviser_name">
                                            <div v-if="org.president_name" class="font-medium">
                                                Pres: {{ org.president_name }}
                                            </div>
                                            <div v-if="org.adviser_name" class="text-gray-500 dark:text-gray-400 text-xs transition-colors">
                                                Adv: {{ org.adviser_name }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ org.members_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ org.established_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full transition-colors" :class="{
                                            'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400': org.status === 'active',
                                            'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300': org.status === 'inactive',
                                        }">
                                            {{ org.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link :href="route('admin.organizations.show', org.org_id)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                                View
                                            </Link>
                                            <button @click="openEditModal(org)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="organizations" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <OrganizationFormModal :show="showModal" :organization="selectedOrganization" @close="closeModal"
            @saved="handleSaved" />
    </AdminLayout>
</template>
