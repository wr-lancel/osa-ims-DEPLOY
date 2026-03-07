<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, confirmAction, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    candidacies: {
        type: Array,
        default: () => [],
    },
    candidacyOpen: {
        type: Boolean,
        default: true,
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

const canWithdraw = (status) => status === 'submitted' || status === 'under_review';

const withdraw = (application) => {
    confirmAction(
        'Are you sure you want to withdraw this candidacy? This action cannot be undone.',
        'Withdraw Candidacy',
        () => {
            router.post(route('student.organizations.candidacy.withdraw', application.application_id));
        },
        { confirmLabel: 'Withdraw', cancelLabel: 'Cancel' }
    );
};
</script>

<template>
    <Head title="My Candidacies" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    My Candidacies
                </h2>
                <Link :href="route('student.organizations.candidacy.create')">
                    <PrimaryButton>Submit Candidacy</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Candidacy Closed Warning -->
            <div v-if="!candidacyOpen" class="bg-amber-50 border border-amber-300 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-amber-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800">Candidacy Submissions Closed</h3>
                        <p class="text-sm text-amber-700 mt-1">
                            Certificate of Candidacy submissions are currently not open. You cannot submit new candidacies at this time.
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificate of Candidacy Applications</h3>

                <div v-if="candidacies.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="app in candidacies" :key="app.application_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ app.org_name }}</div>
                                    <div class="text-xs text-gray-500">{{ app.org_code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ app.position_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.term_label || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusColor(app.status)"
                                    >
                                        {{ app.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.submitted_at || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <Link
                                        :href="route('student.organizations.candidacy.show', app.application_id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        View
                                    </Link>
                                    <button
                                        v-if="canWithdraw(app.status)"
                                        type="button"
                                        class="text-red-600 hover:text-red-900"
                                        @click="withdraw(app)"
                                    >
                                        Withdraw
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500">You have not submitted any candidacies yet.</p>
                    <Link
                        :href="route('student.organizations.candidacy.create')"
                        class="mt-3 inline-flex text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                        Submit your first candidacy
                    </Link>
                </div>
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
