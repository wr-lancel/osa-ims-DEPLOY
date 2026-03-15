<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, confirmAction, closeNotification, handleConfirm } = useNotification();

const isProcessing = ref(false);

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
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

const canWithdraw = () =>
    props.application.status === 'submitted' || props.application.status === 'under_review';

const withdraw = () => {
    confirmAction(
        'Are you sure you want to withdraw this candidacy? This action cannot be undone.',
        'Withdraw Candidacy',
        () => {
            isProcessing.value = true;
            router.post(route('student.organizations.candidacy.withdraw', props.application.application_id), {}, {
                onFinish: () => { isProcessing.value = false; },
            });
        },
        { confirmLabel: 'Withdraw', cancelLabel: 'Cancel' }
    );
};
</script>

<template>
    <Head title="Candidacy Details" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Candidacy Details
                </h2>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('student.organizations.candidacies.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        My Candidacies
                    </Link>
                    <button
                        v-if="canWithdraw()"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-red-300 dark:border-red-700 bg-white dark:bg-gray-700 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        @click="withdraw"
                    >
                        Withdraw
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Application Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Organization</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.org_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ application.org_code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Position</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.position_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Term</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.term_label || '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Status</label>
                        <span
                            class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(application.status)"
                        >
                            {{ application.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Submitted</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.submitted_at || '—' }}</p>
                    </div>
                    <div v-if="application.reviewed_at">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Reviewed</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.reviewed_at }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Platform Statement</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ application.platform_statement || '—' }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Motivation</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ application.motivation || '—' }}</p>
            </div>

            <div v-if="application.review_remarks" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review Remarks</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ application.review_remarks }}</p>
            </div>
        </div>
    </StudentLayout>

    <LoadingOverlay :show="isProcessing" message="Processing... Please wait." />

    <NotificationDialog
        :show="notification.show"
        :type="notification.type"
        :title="notification.title"
        :message="notification.message"
        :confirm-label="notification.confirmLabel"
        :cancel-label="notification.cancelLabel"
        @close="closeNotification"
        @confirm="handleConfirm"
    />
</template>
