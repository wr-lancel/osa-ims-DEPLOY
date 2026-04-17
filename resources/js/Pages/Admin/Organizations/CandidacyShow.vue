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

const ordinalYear = (level) => {
    if (!level) return '—';
    const map = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year', 5: '5th Year' };
    return map[level] || `${level}th Year`;
};

const printPage = () => window.print();

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
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Candidacy Application
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">{{ application.org_name }} ({{ application.org_code }})</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors print:hidden"
                        @click="printPage"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                    <Link :href="route('admin.organizations.candidacies.index')">
                        <SecondaryButton type="button">← Back to Applications</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Status & Actions Banner (hidden on print) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 print:hidden">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="getStatusColor(application.status)">
                            {{ application.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Submitted</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.submitted_at || '—' }}</p>
                    </div>
                    <div v-if="application.reviewed_at">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Reviewed</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ application.reviewed_at }}</p>
                    </div>
                </div>

                <div v-if="['submitted', 'under_review'].includes(application.status)"
                    class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
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

            <!-- Certificate of Candidacy Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                <!-- Document Header -->
                <div class="text-center border-b border-gray-200 dark:border-gray-700 px-6 py-5 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                        Office of the Student Affairs
                    </p>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white mt-1 uppercase tracking-wide">
                        Application for Supreme Student Council
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 italic">Certificate of Candidacy</p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">

                    <!-- Personal Information -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-6 gap-4">
                            <div class="col-span-6 sm:col-span-4">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Name (Surname, First, M.I.)</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.applicant_name || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Age</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.age || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Student No.</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.student_number || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Course</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.course || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Current Year</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ ordinalYear(application.year_level) }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">No. Unit Load</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.unit_load || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Present / Home Address</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.address || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Cellphone Number</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.phone || '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Candidacy Details -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Candidacy Details
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Organization</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.org_name }} ({{ application.org_code }})
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">School Year / Term</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.term_label || '—' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Position Applied</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.position_name || '—' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Political Party Affiliation</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ application.party_affiliation || '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform & Motivation -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Platform & Motivation
                        </h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Platform Statement</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px] whitespace-pre-wrap">{{ application.platform_statement || '—' }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Motivation / Why you want to run</label>
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px] whitespace-pre-wrap">{{ application.motivation || '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Note -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            <span class="font-semibold">Note:</span>
                            Misrepresentation of facts or tampering with / falsifying official records will be a basis for disqualification as a candidate.
                            After filing this application, no candidate can change party affiliation before or during the election period.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Review Remarks -->
            <div v-if="application.review_remarks" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review Remarks</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ application.review_remarks }}</p>
            </div>
        </div>

        <!-- Status update modal -->
        <Modal :show="showStatusModal" @close="closeStatusModal">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    <template v-if="statusForm.status === 'approved'">Approve</template>
                    <template v-else-if="statusForm.status === 'rejected'">Reject</template>
                    <template v-else>Set Under Review</template>
                    Application
                </h2>

                <form @submit.prevent="submitStatus" class="space-y-4">
                    <div>
                        <InputLabel for="review_remarks" value="Remarks (optional)" />
                        <textarea id="review_remarks" v-model="statusForm.review_remarks" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
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
