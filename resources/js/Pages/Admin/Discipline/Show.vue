<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DisciplineFormModal from '@/Components/Admin/DisciplineFormModal.vue';
import DisciplineMeetingModal from '@/Components/Admin/DisciplineMeetingModal.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import HybridTextFileInput from '@/Components/HybridTextFileInput.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    violation: {
        type: Object,
        required: true,
    },
    meetings: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
    workflowSteps: {
        type: Array,
        default: () => [],
    },
    terminalStatuses: {
        type: Array,
        default: () => [],
    },
    isRepeatOffender: {
        type: Boolean,
        default: false,
    },
    studentViolationCount: {
        type: Number,
        default: 0,
    },
});

const isProcessing = ref(false);
const showEditModal = ref(false);
const showMeetingModal = ref(false);
const selectedMeeting = ref(null);

const isVoided = computed(() => !!props.violation.voided_at);

// --- Void modal ---
const showVoidModal = ref(false);
const voidForm = useForm({
    void_reason: '',
    void_notes: '',
});
const voidReasons = [
    'Wrong Student',
    'Wrong Violation Type',
    'Duplicate Entry',
    'Data Entry Error',
    'Other',
];
const submitVoid = () => {
    voidForm.post(route('admin.discipline.void', props.violation.discipline_id), {
        preserveScroll: true,
        onSuccess: () => {
            showVoidModal.value = false;
            voidForm.reset();
        },
    });
};

// --- Delete modal ---
const showDeleteModal = ref(false);
const deleteForm = useForm({ password: '' });
const submitDelete = () => {
    deleteForm.delete(route('admin.discipline.destroy', props.violation.discipline_id), {
        onError: () => {},
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const editViolation = ref({ ...props.violation });

watch(() => props.violation, (v) => {
    editViolation.value = { ...v };
    narrativeText.value = v.narrative_report || '';
    narrativeFile.value = null;
    removeNarrativeFile.value = false;
}, { deep: true });

const openEditModal = () => {
    editViolation.value = { ...props.violation };
    showEditModal.value = true;
};

const openScheduleMeeting = () => {
    selectedMeeting.value = null;
    showMeetingModal.value = true;
};

const openEditMeeting = (meeting) => {
    selectedMeeting.value = meeting;
    showMeetingModal.value = true;
};

const handleSaved = () => {
    showEditModal.value = false;
    router.reload();
};

const handleMeetingSaved = () => {
    showMeetingModal.value = false;
    selectedMeeting.value = null;
    router.reload();
};

const getStatusColor = (status) => {
    if (status === 'resolved') return 'bg-green-100 text-green-800';
    if (status === 'under investigation') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const getMeetingStatusColor = (status) => {
    if (status === 'completed') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-red-100 text-red-800';
    if (status === 'rescheduled') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

// --- Inline Narrative Report ---
const narrativeText = ref(props.violation.narrative_report || '');
const narrativeFile = ref(null);
const removeNarrativeFile = ref(false);
const narrativeProcessing = ref(false);
const narrativeErrors = ref({});

const saveNarrative = () => {
    narrativeProcessing.value = true;
    narrativeErrors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');

    // Carry forward existing fields so validation passes
    formData.append('violation_date', props.violation.violation_date);
    formData.append('violation_type', props.violation.violation_type);
    formData.append('description', props.violation.description);
    formData.append('severity', props.violation.severity || '');
    formData.append('status', props.violation.status);
    formData.append('sanction', props.violation.sanction || '');
    formData.append('remarks', props.violation.remarks || '');
    formData.append('date_resolved', props.violation.date_resolved || '');

    // Narrative fields
    formData.append('narrative_report', narrativeText.value || '');
    if (narrativeFile.value) {
        formData.append('narrative_report_file', narrativeFile.value);
    }
    if (removeNarrativeFile.value) {
        formData.append('remove_narrative_file', '1');
    }

    router.post(route('admin.discipline.update', props.violation.discipline_id), formData, {
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

    <Head :title="`Case #${violation.discipline_id} - Discipline`" />

    <AdminLayout>
        <LoadingOverlay :show="isProcessing || narrativeProcessing" message="Processing... Please wait." />
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        Case #{{ violation.discipline_id }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ violation.student?.full_name }} ({{ violation.student?.student_number }})
                        <button v-if="isRepeatOffender"
                            type="button"
                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-amber-500"
                            @click="router.get(route('admin.discipline.index'), { search: violation.student?.student_number })">
                            Repeat offender ({{ studentViolationCount }} violations) – View all
                        </button>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <template v-if="!isVoided">
                        <SecondaryButton type="button" @click="openEditModal">
                            Edit Violation
                        </SecondaryButton>
                        <PrimaryButton type="button" @click="openScheduleMeeting">
                            Schedule Meeting
                        </PrimaryButton>
                        <button type="button"
                            class="text-sm text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 border border-amber-300 dark:border-amber-600 rounded px-3 py-1.5 transition-colors"
                            @click="showVoidModal = true">
                            Void Violation
                        </button>
                    </template>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        Voided
                    </span>
                    <Link :href="route('admin.discipline.index')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm">
                        ← Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Voided banner -->
            <div v-if="isVoided" class="rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4 flex items-start gap-3">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <div>
                    <p class="font-semibold text-red-700 dark:text-red-300">This violation has been voided</p>
                    <p class="mt-0.5 text-sm text-red-600 dark:text-red-400">
                        Reason: <span class="font-medium">{{ violation.void_reason }}</span>
                        <span v-if="violation.void_notes"> — {{ violation.void_notes }}</span>
                    </p>
                    <p class="mt-0.5 text-xs text-red-500 dark:text-red-500">
                        Voided on {{ violation.voided_at }} by {{ violation.voided_by?.email ?? 'unknown' }}
                    </p>
                </div>
            </div>

            <!-- Violation details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Violation Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Date Reported</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.violation_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Offense / Type</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.violation_type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Severity</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(violation.severity)">
                            {{ violation.severity || '—' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(violation.status)">
                            {{ violation.status }}
                        </span>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4 pt-2">
                        <StatusProgressBar :steps="workflowSteps"
                            :current-status="violation.status" :editable="true"
                            :terminal-statuses="terminalStatuses"
                            @update:status="(newStatus) => { isProcessing = true; router.put(route('admin.discipline.updateStatus', violation.discipline_id), { status: newStatus }, { preserveScroll: true, onFinish: () => { isProcessing = false; } }); }" />
                    </div>
                    <div v-if="violation.date_resolved">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Date Resolved</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ violation.date_resolved }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.description || '—' }}</p>
                    </div>
                    <div v-if="violation.sanction" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Sanction</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.sanction }}</p>
                    </div>
                    <div v-if="violation.remarks" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Remarks</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ violation.remarks }}</p>
                    </div>
                </div>
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
                    :existing-file-url="violation.narrative_report_file_url"
                    :existing-file-name="violation.narrative_report_file_name"
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

            <!-- Scheduled meetings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scheduled Meetings</h3>
                    <PrimaryButton type="button" @click="openScheduleMeeting">Schedule Meeting</PrimaryButton>
                </div>
                <div v-if="meetings && meetings.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Location
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="m in meetings" :key="m.meeting_id" class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ m.meeting_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ m.meeting_time || '—'
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ m.location || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getMeetingStatusColor(m.status)">
                                        {{ m.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        @click="openEditMeeting(m)">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No meetings scheduled.</p>
            </div>

            <!-- History / Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status History</h3>
                <div v-if="history && history.length > 0" class="space-y-3">
                    <div v-for="h in history" :key="h.history_id" class="flex items-start gap-3 text-sm">
                        <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ h.created_at }}</span>
                        <span class="text-gray-700 dark:text-gray-200">
                            {{ h.old_status || '—' }} → {{ h.new_status || '—' }}
                            <span v-if="h.note" class="text-gray-500 dark:text-gray-400">({{ h.note }})</span>
                            <span v-if="h.changed_by" class="text-gray-400 dark:text-gray-500"> by {{ h.changed_by }}</span>
                        </span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No status changes yet.</p>
            </div>
        </div>

        <!-- Subtle permanent delete link -->
        <div class="flex justify-end pt-2">
            <button type="button"
                class="text-xs text-gray-300 dark:text-gray-600 hover:text-red-400 dark:hover:text-red-500 transition-colors"
                @click="showDeleteModal = true">
                Permanently remove record
            </button>
        </div>

        <DisciplineFormModal :show="showEditModal" :violation="editViolation" :enrollments="[]"
            @close="showEditModal = false" @saved="handleSaved" />
        <DisciplineMeetingModal :show="showMeetingModal" :discipline-id="violation.discipline_id"
            :meeting="selectedMeeting" @close="showMeetingModal = false; selectedMeeting = null"
            @saved="handleMeetingSaved" />

        <!-- Void Modal -->
        <Teleport to="body">
            <div v-if="showVoidModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Void Violation</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Voiding keeps the record for transparency but marks it as invalid. Please provide a reason.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <select v-model="voidForm.void_reason"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Select a reason...</option>
                                <option v-for="r in voidReasons" :key="r" :value="r">{{ r }}</option>
                            </select>
                            <p v-if="voidForm.errors.void_reason" class="mt-1 text-xs text-red-600">{{ voidForm.errors.void_reason }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Additional Notes</label>
                            <textarea v-model="voidForm.void_notes" rows="3"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                placeholder="Optional — provide more context..."></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" @click="showVoidModal = false; voidForm.reset()">
                            Cancel
                        </SecondaryButton>
                        <button type="button"
                            class="px-4 py-2 rounded-md text-sm font-medium bg-amber-500 hover:bg-amber-600 text-white transition-colors disabled:opacity-50"
                            :disabled="!voidForm.void_reason || voidForm.processing"
                            @click="submitVoid">
                            {{ voidForm.processing ? 'Voiding...' : 'Void Violation' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Permanently Delete Record</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                This will permanently erase Case #{{ violation.discipline_id }} and all related meetings and history. <strong>This cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Enter your password to confirm
                        </label>
                        <input v-model="deleteForm.password" type="password"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Your admin password"
                            @keydown.enter="submitDelete" />
                        <p v-if="deleteForm.errors.password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ deleteForm.errors.password }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" @click="showDeleteModal = false; deleteForm.reset()">
                            Cancel
                        </SecondaryButton>
                        <button type="button"
                            class="px-4 py-2 rounded-md text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors disabled:opacity-50"
                            :disabled="!deleteForm.password || deleteForm.processing"
                            @click="submitDelete">
                            {{ deleteForm.processing ? 'Deleting...' : 'Permanently Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
