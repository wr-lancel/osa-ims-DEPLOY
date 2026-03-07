<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
});

const showStatusModal = ref(false);
const statusForm = useForm({
    status: '',
    review_remarks: '',
});

const openStatusModal = (newStatus) => {
    statusForm.status = newStatus;
    statusForm.review_remarks = '';
    statusForm.clearErrors();
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    statusForm.reset();
};

const submitStatus = () => {
    statusForm.post(
        route('admin.organizations.candidacies.updateStatus', props.application.application_id),
        {
            preserveScroll: true,
            onSuccess: () => closeStatusModal(),
        }
    );
};

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
</script>

<template>

    <Head :title="`Application - ${application.org_name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Candidacy Application
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ application.org_name }} ({{ application.org_code }})</p>
                </div>
                <Link :href="route('admin.organizations.candidacies.index')">
                    <SecondaryButton type="button">← Back to Applications</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Applicant & Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Applicant</label>
                        <p class="mt-1 text-sm text-gray-900">{{ application.applicant_name }}</p>
                        <p class="text-xs text-gray-500">{{ application.student_number }}</p>
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
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(application.status)">
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

                <div v-if="['submitted', 'under_review'].includes(application.status)"
                    class="mt-6 pt-4 border-t border-gray-200 flex flex-wrap gap-2">
                    <PrimaryButton type="button" @click="openStatusModal('under_review')">
                        Set Under Review
                    </PrimaryButton>
                    <PrimaryButton type="button" class="!bg-green-600 hover:!bg-green-700"
                        @click="openStatusModal('approved')">
                        Approve
                    </PrimaryButton>
                    <DangerButton type="button" @click="openStatusModal('rejected')">
                        Reject
                    </DangerButton>
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

        <!-- Status update modal -->
        <Modal :show="showStatusModal" @close="closeStatusModal">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <template v-if="statusForm.status === 'approved'">Approve</template>
                    <template v-else-if="statusForm.status === 'rejected'">Reject</template>
                    <template v-else>Set Under Review</template>
                    Application
                </h2>

                <form @submit.prevent="submitStatus" class="space-y-4">
                    <div>
                        <InputLabel for="review_remarks" value="Remarks (optional)" />
                        <textarea id="review_remarks" v-model="statusForm.review_remarks" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Add any remarks for the applicant..." />
                        <InputError :message="statusForm.errors.review_remarks" />
                    </div>

                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeStatusModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton v-if="statusForm.status === 'approved'" type="submit"
                            class="!bg-green-600 hover:!bg-green-700" :disabled="statusForm.processing">
                            {{ statusForm.processing ? 'Saving...' : 'Approve' }}
                        </PrimaryButton>
                        <DangerButton v-else-if="statusForm.status === 'rejected'" type="submit"
                            :disabled="statusForm.processing">
                            {{ statusForm.processing ? 'Saving...' : 'Reject' }}
                        </DangerButton>
                        <PrimaryButton v-else type="submit" :disabled="statusForm.processing">
                            {{ statusForm.processing ? 'Saving...' : 'Set Under Review' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
