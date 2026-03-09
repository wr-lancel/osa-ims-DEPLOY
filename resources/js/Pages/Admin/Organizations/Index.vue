<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import OrganizationFormModal from '@/Components/Admin/OrganizationFormModal.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { useNotification } from '@/composables/useNotification';
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

const exportPdf = async () => {
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
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Organization Unit
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton v-if="activeTab === 'organizations'" @click="activeTab = 'events'">
                        View Events
                    </SecondaryButton>
                    <SecondaryButton v-else @click="activeTab = 'organizations'">
                        View Organizations
                    </SecondaryButton>
                    <Link v-if="activeTab === 'organizations'" :href="route('admin.organizations.candidacies.index')">
                        <SecondaryButton type="button">Candidacy Applications</SecondaryButton>
                    </Link>
                    <SecondaryButton v-if="activeTab === 'organizations'" @click="exportPdf">
                        Export PDF
                    </SecondaryButton>
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
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                Search
                            </label>
                            <input id="search" v-model="search" type="text"
                                placeholder="Organization name, code, or president..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                Type
                            </label>
                            <select id="type" v-model="type"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select id="status" v-model="status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>


                </div>

                <!-- Organizations Table -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Organization Name
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Leadership
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Members
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Established
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
                                <tr v-if="organizations.data && organizations.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No organizations found.
                                    </td>
                                </tr>
                                <tr v-for="org in organizations.data" :key="org.org_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('admin.organizations.show', org.org_id)"
                                            class="block hover:bg-gray-50">
                                            <div class="text-sm font-medium text-indigo-600 hover:text-indigo-900">{{
                                                org.org_name
                                            }}</div>
                                            <div class="text-sm text-gray-500">{{ org.org_code }}</div>
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ org.type || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div v-if="org.president_name || org.adviser_name">
                                            <div v-if="org.president_name" class="font-medium">
                                                Pres: {{ org.president_name }}
                                            </div>
                                            <div v-if="org.adviser_name" class="text-gray-500 text-xs">
                                                Adv: {{ org.adviser_name }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ org.members_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ org.established_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="{
                                            'bg-green-100 text-green-800': org.status === 'active',
                                            'bg-gray-100 text-gray-800': org.status === 'inactive',
                                        }">
                                            {{ org.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <Link :href="route('admin.organizations.show', org.org_id)"
                                                class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            <button @click="openEditModal(org)"
                                                class="text-indigo-600 hover:text-indigo-900">
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
