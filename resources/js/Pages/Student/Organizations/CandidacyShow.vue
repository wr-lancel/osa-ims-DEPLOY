<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, confirmAction, closeNotification, handleConfirm } = useNotification();

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
            router.post(route('student.organizations.candidacy.withdraw', props.application.application_id));
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
                <h2 class="text-2xl font-semibold text-gray-900">
                    Candidacy Details
                </h2>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('student.organizations.candidacies.index')"
                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                        ← My Candidacies
                    </Link>
                    <button
                        v-if="canWithdraw()"
                        type="button"
                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                        @click="withdraw"
                    >
                        Withdraw
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Organization</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.org_name }}</p>
                        <p class="text-xs text-gray-500">{{ application.org_code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Position</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.position_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Term</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.term_label || '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <span
                            class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(application.status)"
                        >
                            {{ application.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Submitted</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.submitted_at || '—' }}</p>
                    </div>
                    <div v-if="application.reviewed_at">
                        <label class="block text-sm font-medium text-gray-500">Reviewed</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.reviewed_at }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Statement</h3>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ application.platform_statement || '—' }}</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Motivation</h3>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ application.motivation || '—' }}</p>
            </div>

            <div v-if="application.review_remarks" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Remarks</h3>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ application.review_remarks }}</p>
            </div>
        </div>
    </StudentLayout>

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
