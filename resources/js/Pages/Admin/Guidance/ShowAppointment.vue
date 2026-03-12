<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import HybridTextFileInput from '@/Components/HybridTextFileInput.vue';

const props = defineProps({
    appointment: {
        type: Object,
        required: true,
    },
});

const statusSteps = [
    { value: 'Pending', label: 'Pending' },
    { value: 'Approved', label: 'Approved' },
    { value: 'Completed', label: 'Completed' },
];

const terminalStatuses = ['Rejected', 'Cancelled'];

const updateAppointmentStatus = (newStatus) => {
    router.put(route('admin.guidance.appointments.updateStatus', props.appointment.appointment_id), {
        status: (newStatus || '').toLowerCase(),
    }, { preserveScroll: true });
};

const getStatusBadgeClass = (status) => {
    const s = (status || '').toLowerCase();
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return classes[s] || 'bg-gray-100 text-gray-800';
};

const getAppointmentTypeBadgeClass = (type) => {
    const classes = {
        counseling: 'bg-purple-100 text-purple-800',
        consultation: 'bg-indigo-100 text-indigo-800',
        referral: 'bg-pink-100 text-pink-800',
        other: 'bg-gray-100 text-gray-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

// --- Inline Narrative Report ---
const narrativeText = ref(props.appointment.narrative_report || '');
const narrativeFile = ref(null);
const removeNarrativeFile = ref(false);
const narrativeProcessing = ref(false);
const narrativeErrors = ref({});

watch(() => props.appointment, (a) => {
    narrativeText.value = a.narrative_report || '';
    narrativeFile.value = null;
    removeNarrativeFile.value = false;
}, { deep: true });

const saveNarrative = () => {
    narrativeProcessing.value = true;
    narrativeErrors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');

    // Carry forward required fields so validation passes
    formData.append('concern', props.appointment.concern || '');

    // Narrative fields
    formData.append('narrative_report', narrativeText.value || '');
    if (narrativeFile.value) {
        formData.append('narrative_report_file', narrativeFile.value);
    }
    if (removeNarrativeFile.value) {
        formData.append('remove_narrative_file', '1');
    }

    router.post(route('admin.guidance.appointments.update', props.appointment.appointment_id), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            narrativeFile.value = null;
            removeNarrativeFile.value = false;
        },
        onError: (errors) => {
            narrativeErrors.value = errors;
        },
        onFinish: () => {
            narrativeProcessing.value = false;
        },
    });
};
</script>

<template>

    <Head :title="`Appointment Details - Guidance`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Appointment Details
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                        {{ appointment.student_name }} ({{ appointment.student_id }})
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('admin.guidance.index')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm">
                        ← Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Appointment Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Appointment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Student</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.student_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.student_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Date & Time</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.appointment_date }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.appointment_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Type</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                            :class="getAppointmentTypeBadgeClass(appointment.appointment_type)">
                            {{ appointment.appointment_type }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Status</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusBadgeClass(appointment.status)">
                            {{ appointment.status }}
                        </span>
                    </div>

                    <!-- Status Progress Bar -->
                    <div class="md:col-span-2 lg:col-span-4 pt-2">
                        <StatusProgressBar :steps="statusSteps" :current-status="appointment.status"
                            :terminal-statuses="terminalStatuses" :editable="true"
                            @update:status="updateAppointmentStatus" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Created At</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.created_at }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Last Updated</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ appointment.updated_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Concern -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Concern</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ appointment.concern || '—' }}</p>
            </div>

            <!-- Notes -->
            <div v-if="appointment.notes" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notes</h3>
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ appointment.notes }}</p>
            </div>

            <!-- Narrative Report - Inline Editable -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Narrative Report</h3>
                    <PrimaryButton type="button" :disabled="narrativeProcessing" @click="saveNarrative">
                        {{ narrativeProcessing ? 'Saving...' : 'Save Narrative' }}
                    </PrimaryButton>
                </div>

                <HybridTextFileInput
                    label=""
                    :text="narrativeText"
                    :existing-file-url="appointment.narrative_report_file_url"
                    :existing-file-name="appointment.narrative_report_file_name"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                    placeholder="Type the narrative report here..."
                    help-text="You may type text, upload a document (.pdf, .doc, .docx, .jpg, .png - max 10MB), or do both."
                    :text-error="narrativeErrors.narrative_report"
                    :file-error="narrativeErrors.narrative_report_file"
                    @update:text="(val) => narrativeText = val"
                    @update:file="(file) => { narrativeFile = file; removeNarrativeFile = false; }"
                    @remove-file="() => { narrativeFile = null; removeNarrativeFile = true; }"
                />
            </div>

            <!-- Admin Response -->
            <div v-if="appointment.admin_remarks || appointment.approved_at || appointment.rejected_at"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Admin Response</h3>
                <div class="space-y-3">
                    <div v-if="appointment.approved_at" class="flex items-center space-x-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Approved
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ appointment.approved_at }} by {{ appointment.approver_name || 'N/A' }}
                        </span>
                    </div>
                    <div v-if="appointment.rejected_at" class="flex items-center space-x-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Rejected
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ appointment.rejected_at }} by {{ appointment.rejector_name || 'N/A' }}
                        </span>
                    </div>
                    <div v-if="appointment.admin_remarks">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-1">Remarks</label>
                        <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ appointment.admin_remarks }}</p>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="flex justify-end">
                <Link :href="route('admin.guidance.index')"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:bg-gray-900">
                    Back to Guidance Unit
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
