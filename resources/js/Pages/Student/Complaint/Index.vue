<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
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

const category = ref(props.filters.category || '');
const status = ref(props.filters.status || '');
const sortBy = ref(props.filters.sort_by || '');
const sortDir = ref(props.filters.sort_dir || 'desc');

const applyFilters = () => {
    router.get(route('student.discipline.complaints.index'), {
        category: category.value || undefined,
        status: status.value || undefined,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
    });
};

const handleSort = ({ column, dir }) => {
    sortBy.value = column;
    sortDir.value = dir;
    router.get(route('student.discipline.complaints.index'), {
        category: category.value || undefined,
        status: status.value || undefined,
        sort_by: column,
        sort_dir: dir,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([category, status], () => applyFilters());

const getStatusColor = (s) => {
    if (s === 'resolved') return 'bg-green-100 text-green-800';
    if (s === 'dismissed') return 'bg-gray-100 text-gray-800';
    if (s === 'escalated') return 'bg-red-100 text-red-800';
    if (s === 'under_review') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

const formatStatus = (s) => s ? s.replace(/_/g, ' ') : '';
</script>

<template>
    <Head title="My Complaints" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    My Complaints
                </h2>
                <div class="flex items-center gap-3 text-sm">
                    <Link
                        :href="route('student.discipline.complaints.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700"
                    >
                        Submit Complaint
                    </Link>
                    <Link
                        :href="route('student.discipline.index')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                    >
                        ← Discipline Unit
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex flex-wrap gap-4 mb-4">
                    <div>
                        <label for="filter-category" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Category</label>
                        <select
                            id="filter-category"
                            v-model="category"
                            class="block rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 max-w-xs dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option
                                v-for="c in categories"
                                :key="c.value"
                                :value="c.value"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select
                            id="filter-status"
                            v-model="status"
                            class="block rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 max-w-xs dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option
                                v-for="s in statusOptions"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div v-if="complaints.data && complaints.data.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Subject</th>
                                <SortableHeader column="category" label="Category" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <SortableHeader column="created_at" label="Date Submitted" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="c in complaints.data" :key="c.complaint_id" class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#{{ c.complaint_id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ c.subject }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ c.category }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ c.date_submitted }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                        :class="getStatusColor(c.status)"
                                    >
                                        {{ formatStatus(c.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <Link
                                        :href="route('student.discipline.complaints.show', c.complaint_id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :data="complaints" />
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">You have no complaints yet.</p>
                    <Link
                        :href="route('student.discipline.complaints.create')"
                        class="mt-2 inline-block text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm font-medium"
                    >
                        Submit a complaint
                    </Link>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
