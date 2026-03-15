<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';

const props = defineProps({
    borrowing: {
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
    <Head title="Borrowing Details" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Borrowing Details
                </h2>
                <Link
                    :href="route('student.sports.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Borrowing History
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ borrowing.item_name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Request ID: #{{ borrowing.borrowing_id }}</p>
                    </div>
                    <span
                        class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                        :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)"
                    >
                        {{ borrowing.status }}
                    </span>
                </div>
                <StatusProgressBar
                    :steps="[
                        { value: 'pending', label: 'Pending' },
                        { value: 'approved', label: 'Approved' },
                        { value: 'borrowed', label: 'Borrowed' },
                        { value: 'returned', label: 'Returned' },
                    ]"
                    :current-status="borrowing.status"
                    :terminal-statuses="['rejected', 'overdue']"
                />
            </div>

            <!-- Request Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Request Information</h3>
                    
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Item Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.item_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Borrow Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.borrow_date }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Expected Return Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.expected_return_date }}</dd>
                        </div>

                        <div v-if="borrowing.return_date">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Return Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.return_date }}</dd>
                        </div>

                        <div v-if="borrowing.description" class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.description }}</dd>
                        </div>

                        <div v-if="borrowing.notes" class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Your Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ borrowing.notes }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Admin Response -->
            <div v-if="borrowing.admin_remarks || borrowing.approved_at || borrowing.rejected_at" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Admin Response</h3>
                    
                    <dl class="space-y-4">
                        <div v-if="borrowing.approved_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Approved On</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ borrowing.approved_at }}
                                <span v-if="borrowing.approver_name" class="text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    by {{ borrowing.approver_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="borrowing.rejected_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Rejected On</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ borrowing.rejected_at }}
                                <span v-if="borrowing.rejector_name" class="text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    by {{ borrowing.rejector_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="borrowing.admin_remarks">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Admin Remarks</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ borrowing.admin_remarks }}</dd>
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
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ borrowing.created_at }}</p>
                            </div>
                        </div>

                        <div v-if="borrowing.approved_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Request Approved</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ borrowing.approved_at }}</p>
                            </div>
                        </div>

                        <div v-if="borrowing.rejected_at" class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Request Rejected</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ borrowing.rejected_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="flex justify-end">
                <Link
                    :href="route('student.sports.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Back to History
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>

