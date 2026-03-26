<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SortableHeader from '@/Components/SortableHeader.vue';

const props = defineProps({
    violations: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    unreadNotificationsCount: {
        type: Number,
        default: 0,
    },
    complaintUnreadCount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    terms: {
        type: Array,
        default: () => [],
    },
    codeOfConductSections: {
        type: Array,
        default: () => [],
    },
});

const acadId = ref(props.filters.acad_id || '');
const sortBy = ref(props.filters.sort_by || '');
const sortDir = ref(props.filters.sort_dir || 'desc');

const applyTermFilter = () => {
    router.get(route('student.discipline.index'), {
        acad_id: acadId.value || undefined,
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
    router.get(route('student.discipline.index'), {
        acad_id: acadId.value || undefined,
        sort_by: column,
        sort_dir: dir,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusColor = (status) => {
    if (status === 'resolved') return 'bg-green-100 text-green-800';
    if (status === 'under investigation') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const getSeverityColor = (severity) => {
    if (severity === 'Major') return 'bg-red-100 text-red-800';
    if (severity === 'Moderate') return 'bg-yellow-100 text-yellow-800';
    if (severity === 'Minor') return 'bg-green-100 text-green-800';
    return 'bg-gray-100 text-gray-800';
};

const goToDetail = (v) => {
    router.visit(route('student.discipline.show', v.discipline_id));
};
</script>

<template>
    <Head title="Discipline Unit" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Discipline Unit
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('student.discipline.complaints.index')"
                        class="relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        My Complaints
                        <span
                            v-if="complaintUnreadCount > 0"
                            class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-red-500 text-white"
                        >
                            {{ complaintUnreadCount }}
                        </span>
                    </Link>
                    <Link
                        :href="route('student.discipline.notifications.index')"
                        class="relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Notifications
                        <span
                            v-if="unreadNotificationsCount > 0"
                            class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-red-500 text-white"
                        >
                            {{ unreadNotificationsCount }}
                        </span>
                    </Link>
                    <div class="hidden sm:block h-6 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
                    <Link
                        :href="route('student.discipline.complaints.create')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-sm font-medium text-white transition-colors"
                    >
                        Submit Complaint
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">My Violations</h3>

                <div class="mb-4">
                    <label for="term" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Term</label>
                    <select
                        id="term"
                        v-model="acadId"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 max-w-xs dark:bg-gray-700 dark:text-gray-100"
                        @change="applyTermFilter"
                    >
                        <option value="">All Terms</option>
                        <option
                            v-for="t in terms"
                            :key="t.calendar_id"
                            :value="t.calendar_id"
                        >
                            {{ t.display_label }}
                        </option>
                    </select>
                </div>

                <div v-if="violations.data && violations.data.length > 0">
                    <!-- Mobile Card Layout -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="v in violations.data"
                            :key="'m-' + v.discipline_id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:bg-gray-900 active:bg-gray-100 dark:bg-gray-800 transition cursor-pointer"
                            @click="goToDetail(v)"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">#{{ v.discipline_id }}</span>
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                    :class="getStatusColor(v.status)"
                                >
                                    {{ v.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 dark:text-white mb-1">{{ v.violation_type }}</p>
                            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                <span>{{ v.violation_date }}</span>
                                <span v-if="v.term_label">• {{ v.term_label }}</span>
                            </div>
                            <div v-if="v.severity" class="mt-2">
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                    :class="getSeverityColor(v.severity)"
                                >
                                    {{ v.severity }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table Layout -->
                    <div class="hidden md:block overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Case ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Offense</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Term</th>
                                    <SortableHeader column="violation_date" label="Date" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <SortableHeader column="severity" label="Severity" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="v in violations.data"
                                    :key="v.discipline_id"
                                    class="hover:bg-gray-50 dark:bg-gray-900 cursor-pointer"
                                    @click="goToDetail(v)"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#{{ v.discipline_id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ v.violation_type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ v.term_label || '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ v.violation_date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="v.severity"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getSeverityColor(v.severity)"
                                        >
                                            {{ v.severity }}
                                        </span>
                                        <span v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-xs">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusColor(v.status)"
                                        >
                                            {{ v.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm" @click.stop>
                                        <Link
                                            :href="route('student.discipline.show', v.discipline_id)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="violations" />
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">You have no violation records.</p>
                </div>
            </div>

            <!-- Student Code of Conduct -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Student Code of Conduct</h3>
                    <Link
                        :href="route('student.discipline.code-of-conduct.index')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm"
                    >
                        View All &rarr;
                    </Link>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Review the student code of conduct below. Click on any topic to learn more.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div
                        v-for="section in codeOfConductSections"
                        :key="section.id"
                        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                    >
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ section.title }}</h4>
                        <ul class="space-y-2">
                            <li
                                v-for="item in section.items"
                                :key="item.slug"
                                class="flex items-start"
                            >
                                <span class="text-gray-400 dark:text-gray-500 mr-2 mt-0.5">•</span>
                                <Link
                                    :href="route('student.discipline.code-of-conduct.show', item.slug)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm hover:underline"
                                >
                                    {{ item.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
