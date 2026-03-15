<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';

const props = defineProps({
    appointment: {
        type: Object,
        required: true,
    },
});

const getStatusBadgeClass = (status, statusColor) => {
    const colorMap = {
        'yellow': 'bg-yellow-100 text-yellow-800',
        'green': 'bg-green-100 text-green-800',
        'red': 'bg-red-100 text-red-800',
        'blue': 'bg-blue-100 text-blue-800',
        'gray': 'bg-gray-100 text-gray-800',
    };
    return colorMap[statusColor] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Appointment Details" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Appointment Details
                </h2>
                <Link
                    :href="route('student.guidance.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Appointments
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Appointment Request</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Request ID: #{{ appointment.appointment_id }}</p>
                    </div>
                    <span
                        class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                        :class="getStatusBadgeClass(appointment.status, appointment.status_color)"
                    >
                        {{ appointment.status }}
                    </span>
                </div>
                <StatusProgressBar
                    :steps="[
                        { value: 'pending', label: 'Pending' },
                        { value: 'approved', label: 'Approved' },
                        { value: 'completed', label: 'Completed' },
                    ]"
                    :current-status="appointment.status"
                    :terminal-statuses="['rejected', 'cancelled']"
                />
            </div>

            <!-- Appointment Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Appointment Information</h3>
                    
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Appointment Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.appointment_date }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Appointment Time</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.appointment_time }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Appointment Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ appointment.appointment_type }}</dd>
                        </div>

                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Concern</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ appointment.concern }}</dd>
                        </div>

                        <div v-if="appointment.notes" class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Additional Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ appointment.notes }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Admin Response -->
            <div v-if="appointment.admin_remarks || appointment.approved_at || appointment.rejected_at" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Admin Response</h3>
                    
                    <dl class="space-y-4">
                        <div v-if="appointment.approved_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Approved On</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ appointment.approved_at }}
                                <span v-if="appointment.approver_name" class="text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    by {{ appointment.approver_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="appointment.rejected_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Rejected On</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ appointment.rejected_at }}
                                <span v-if="appointment.rejector_name" class="text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    by {{ appointment.rejector_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="appointment.admin_remarks">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Admin Remarks</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ appointment.admin_remarks }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Timeline</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Request Submitted</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.created_at }}</p>
                            </div>
                        </div>

                        <div v-if="appointment.approved_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Request Approved</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.approved_at }}</p>
                            </div>
                        </div>

                        <div v-if="appointment.rejected_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Request Rejected</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.rejected_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="flex justify-end">
                <Link
                    :href="route('student.guidance.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Back to History
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>

