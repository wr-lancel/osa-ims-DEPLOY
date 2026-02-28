<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

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

const applyTermFilter = () => {
    router.get(route('student.discipline.index'), {
        acad_id: acadId.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const getStatusColor = (status) => {
    if (status === 'Resolved') return 'bg-green-100 text-green-800';
    if (status === 'Under Investigation') return 'bg-yellow-100 text-yellow-800';
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
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Discipline Unit
                </h2>
                <div class="flex items-center flex-wrap gap-4">
                    <Link
                        :href="route('student.discipline.complaints.create')"
                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                    >
                        Submit Complaint
                    </Link>
                    <Link
                        :href="route('student.discipline.complaints.index')"
                        class="relative inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                    >
                        My Complaints
                        <span
                            v-if="complaintUnreadCount > 0"
                            class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800"
                        >
                            {{ complaintUnreadCount }}
                        </span>
                    </Link>
                    <Link
                        :href="route('student.discipline.notifications.index')"
                        class="relative inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                    >
                        Notifications
                        <span
                            v-if="unreadNotificationsCount > 0"
                            class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800"
                        >
                            {{ unreadNotificationsCount }}
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">My Violations</h3>

                <div class="mb-4">
                    <label for="term" class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                    <select
                        id="term"
                        v-model="acadId"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 max-w-xs"
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
                            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 active:bg-gray-100 transition cursor-pointer"
                            @click="goToDetail(v)"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-900">#{{ v.discipline_id }}</span>
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                    :class="getStatusColor(v.status)"
                                >
                                    {{ v.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 mb-1">{{ v.violation_type }}</p>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
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
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Case ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offense</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="v in violations.data"
                                    :key="v.discipline_id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="goToDetail(v)"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ v.discipline_id }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ v.violation_type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ v.term_label || '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ v.violation_date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="v.severity"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getSeverityColor(v.severity)"
                                        >
                                            {{ v.severity }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs">—</span>
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
                                            class="text-indigo-600 hover:text-indigo-900"
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
                    <p class="text-sm text-gray-500">You have no violation records.</p>
                </div>
            </div>

            <!-- Student Code of Conduct -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Student Code of Conduct</h3>
                <p class="text-sm text-gray-600">
                    Review the categories and topics below. Each item links to more detail.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="section in codeOfConductSections"
                        :key="section.id"
                        class="bg-white rounded-lg border border-gray-200 shadow-sm p-6"
                    >
                        <h4 class="text-base font-semibold text-gray-900 mb-4">{{ section.title }}</h4>
                        <ul class="space-y-2">
                            <li
                                v-for="item in section.items"
                                :key="item.slug"
                                class="flex items-center"
                            >
                                <span class="text-gray-400 mr-2">•</span>
                                <Link
                                    :href="route('student.discipline.code-of-conduct.show', item.slug)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm"
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
