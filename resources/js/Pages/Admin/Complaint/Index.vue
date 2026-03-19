<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SortableHeader from '@/Components/SortableHeader.vue';

const props = defineProps({
    complaints: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const status = ref(props.filters.status || '');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortDir = ref(props.filters.sort_dir || 'desc');

const applyFilters = () => {
    router.get(route('admin.discipline.complaints.index'), {
        search: search.value || undefined,
        category: category.value || undefined,
        status: status.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['complaints', 'filters', 'categories', 'statusOptions'],
    });
};

const handleSort = ({ column, dir }) => {
    sortBy.value = column;
    sortDir.value = dir;
    applyFilters();
};

let searchDebounce = null;
watch(search, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([category, status], () => applyFilters());



import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const getStatusColor = (s) => {
    if (s === 'resolved') return 'bg-green-100 text-green-800';
    if (s === 'dismissed') return 'bg-gray-100 text-gray-800';
    if (s === 'escalated') return 'bg-red-100 text-red-800';
    if (s === 'under_review') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

const formatStatus = (s) => s ? s.replace(/_/g, ' ') : '';

import { useNotification } from '@/composables/useNotification';
import ExportConfirmDialog from '@/Components/ExportConfirmDialog.vue';
const { notify } = useNotification();
const isExporting = ref(false);
const showExportDialog = ref(false);

const exportPdf = async () => {
    showExportDialog.value = false;
    isExporting.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (category.value) params.append('category', category.value);
        if (status.value) params.append('status', status.value);

        const response = await axios.get(route('admin.discipline.complaints.export.pdf') + (params.toString() ? '?' + params.toString() : ''), {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'complaints_report.pdf');
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

    <Head title="Complaints Inbox" />

    <AdminLayout>
        <LoadingOverlay :show="isExporting" message="Generating PDF... Please wait." />
        <ExportConfirmDialog :show="showExportDialog" @confirm="exportPdf" @cancel="showExportDialog = false" />
        <template #header>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Complaints Inbox
                </h2>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.discipline.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Discipline Unit
                    </Link>
                    <button
                        title="Export current filtered results to PDF"
                        @click="showExportDialog = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ $page.props.flash.success }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Search</label>
                        <input id="search" v-model="search" type="text"
                            placeholder="Student name, number, complaint ID, or subject..."
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Category</label>
                        <select id="category" v-model="category"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="c in categories" :key="c.value" :value="c.value">
                                {{ c.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select id="status" v-model="status"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                            <option v-for="s in statusOptions" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <tr>
                                <SortableHeader column="complaint_id" label="ID" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <th class="px-4 py-3 font-semibold whitespace-nowrap text-left">Complainant</th>
                                <SortableHeader column="category" label="Category" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <th class="px-4 py-3 font-semibold whitespace-nowrap text-left">Subject</th>
                                <SortableHeader column="created_at" label="Date Submitted" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <th class="px-6 py-3 font-semibold whitespace-nowrap text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="complaints.data && complaints.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    No complaints found.
                                </td>
                            </tr>
                            <tr v-for="c in complaints.data" :key="c.complaint_id" class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#{{
                                    c.complaint_id }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ c.complainant }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ c.category }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-xs truncate" :title="c.subject">{{
                                    c.subject }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ c.date_submitted }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                        :class="getStatusColor(c.status)">
                                        {{ formatStatus(c.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <Link :href="route('admin.discipline.complaints.show', c.complaint_id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="complaints.links && complaints.links.length > 3"
                    class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex gap-2">
                        <template v-for="(link, i) in complaints.links" :key="i">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded border"
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:bg-gray-900'"
                                v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
