<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    complaint: {
        type: Object,
        required: true,
    },
    history: {
        type: Array,
        default: () => [],
    },
});

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
    <Head :title="`Complaint #${complaint.complaint_id}`" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Complaint #{{ complaint.complaint_id }}
                </h2>
                <Link
                    :href="route('student.discipline.complaints.index')"
                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                >
                    ← My Complaints
                </Link>
            </div>
        </template>

        <div class="space-y-6 max-w-4xl">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Subject</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.category }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date Submitted</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.date_submitted }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                        <dd class="mt-1">
                            <span
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                :class="getStatusColor(complaint.status)"
                            >
                                {{ formatStatus(complaint.status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Incident Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.incident_date }}</dd>
                    </div>
                    <div class="sm:col-span-2" v-if="complaint.location">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.location }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ complaint.description }}</dd>
                    </div>
                </dl>
            </div>

            <div v-if="history.length > 0" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status History</h3>
                <ul class="space-y-4">
                    <li
                        v-for="(h, i) in history"
                        :key="i"
                        class="flex gap-4 border-l-2 border-gray-200 pl-4 py-2"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">
                                <span v-if="h.old_status" class="capitalize">{{ formatStatus(h.old_status) }}</span>
                                <span v-if="h.old_status"> → </span>
                                <span class="font-medium capitalize">{{ formatStatus(h.new_status) }}</span>
                            </p>
                            <p v-if="h.remarks" class="text-sm text-gray-600 mt-1">{{ h.remarks }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ h.created_at }} <span v-if="h.changed_by">· {{ h.changed_by }}</span></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </StudentLayout>
</template>
