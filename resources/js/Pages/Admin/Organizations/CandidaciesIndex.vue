<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    applications: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    stats: {
        type: Object,
        default: () => ({ submitted: 0, under_review: 0, approved: 0, rejected: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    organizations: {
        type: Array,
        default: () => [],
    },
    terms: {
        type: Array,
        default: () => [],
    },
    candidacyOpen: {
        type: Boolean,
        default: false,
    },
});

const statusFilter = ref(props.filters.status || '');
const orgFilter = ref(props.filters.org_id || '');
const termFilter = ref(props.filters.acad_id || '');

function applyFilters() {
    router.get(route('admin.organizations.candidacies.index'), {
        status: statusFilter.value || undefined,
        org_id: orgFilter.value || undefined,
        acad_id: termFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

watch([statusFilter, orgFilter, termFilter], () => {
    applyFilters();
});

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'under_review': return 'bg-blue-100 text-blue-800';
        case 'withdrawn': return 'bg-gray-100 text-gray-800';
        case 'submitted': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const toggling = ref(false);
const toggleCandidacy = () => {
    toggling.value = true;
    router.post(route('admin.organizations.candidacies.toggle'), {}, {
        preserveScroll: true,
        onFinish: () => { toggling.value = false; },
    });
};
</script>

<template>

    <Head title="Candidacy Applications" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Candidacy Applications
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">All candidacy applications across organizations</p>
                </div>
                <Link :href="route('admin.organizations.index')">
                    <SecondaryButton type="button">← Back to Organizations</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Global Candidacy Toggle -->
            <div class="bg-white rounded-lg border shadow-sm p-4"
                :class="candidacyOpen ? 'border-green-200' : 'border-gray-200'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="candidacyOpen ? 'bg-green-100' : 'bg-gray-100'">
                            <svg class="h-5 w-5" :class="candidacyOpen ? 'text-green-600' : 'text-gray-400'" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold"
                                :class="candidacyOpen ? 'text-green-900' : 'text-gray-900'">
                                Candidacy Submissions {{ candidacyOpen ? 'Open' : 'Closed' }}
                            </p>
                            <p class="text-xs" :class="candidacyOpen ? 'text-green-600' : 'text-gray-500'">
                                <template v-if="candidacyOpen">Students can submit candidacy applications to all
                                    organizations.</template>
                                <template v-else>Students cannot submit new candidacy applications.</template>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="toggleCandidacy" :disabled="toggling"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                        :class="candidacyOpen ? 'bg-green-500' : 'bg-gray-300'">
                        <span class="sr-only">Toggle candidacy</span>
                        <span
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="candidacyOpen ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <p class="text-sm font-medium text-gray-500">Submitted</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ stats.submitted }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <p class="text-sm font-medium text-gray-500">Under Review</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ stats.under_review }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="text-2xl font-semibold text-green-600">{{ stats.approved }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="text-2xl font-semibold text-red-600">{{ stats.rejected }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="org" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                        <select id="org" v-model="orgFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Organizations</option>
                            <option v-for="org in organizations" :key="org.org_id" :value="org.org_id">
                                {{ org.org_name }} ({{ org.org_code }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" v-model="statusFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                    </div>
                    <div>
                        <label for="term" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                        <select id="term" v-model="termFilter"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option v-for="t in terms" :key="t.calendar_id" :value="t.calendar_id">
                                {{ t.display_label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Applications</h3>

                <div v-if="applications.data && applications.data.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="app in applications.data" :key="app.application_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ app.applicant_name }}</div>
                                    <div class="text-xs text-gray-500">{{ app.student_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ app.org_name }}</div>
                                    <div class="text-xs text-gray-500">{{ app.org_code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ app.position_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.term_label || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.submitted_at || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusColor(app.status)">
                                        {{ app.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <Link :href="route('admin.organizations.candidacies.show', app.application_id)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="text-sm text-gray-500 py-4">No applications found.</p>

                <!-- Pagination -->
                <Pagination :data="applications" />
            </div>
        </div>
    </AdminLayout>
</template>
