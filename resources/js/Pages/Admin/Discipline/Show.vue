<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DisciplineFormModal from '@/Components/Admin/DisciplineFormModal.vue';
import DisciplineMeetingModal from '@/Components/Admin/DisciplineMeetingModal.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import HybridTextFileInput from '@/Components/HybridTextFileInput.vue';

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
});

const showEditModal = ref(false);
const showMeetingModal = ref(false);
const selectedMeeting = ref(null);

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
    if (status === 'Resolved') return 'bg-green-100 text-green-800';
    if (status === 'Under Investigation') return 'bg-yellow-100 text-yellow-800';
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
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Case #{{ violation.discipline_id }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ violation.student?.full_name }} ({{ violation.student?.student_number }})
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <SecondaryButton type="button" @click="openEditModal">
                        Edit Violation
                    </SecondaryButton>
                    <PrimaryButton type="button" @click="openScheduleMeeting">
                        Schedule Meeting
                    </PrimaryButton>
                    <Link :href="route('admin.discipline.index')" class="text-indigo-600 hover:text-indigo-900 text-sm">
                        ← Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Violation details -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Violation Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Date Reported</label>
                        <p class="mt-1 text-sm text-gray-900">{{ violation.violation_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Offense / Type</label>
                        <p class="mt-1 text-sm text-gray-900">{{ violation.violation_type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Severity</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(violation.severity)">
                            {{ violation.severity || '—' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusColor(violation.status)">
                            {{ violation.status }}
                        </span>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4 pt-2">
                        <StatusProgressBar :steps="workflowSteps"
                            :current-status="violation.status" :editable="true"
                            :terminal-statuses="terminalStatuses"
                            @update:status="(newStatus) => router.put(route('admin.discipline.updateStatus', violation.discipline_id), { status: newStatus }, { preserveScroll: true })" />
                    </div>
                    <div v-if="violation.date_resolved">
                        <label class="block text-sm font-medium text-gray-500">Date Resolved</label>
                        <p class="mt-1 text-sm text-gray-900">{{ violation.date_resolved }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500">Description</label>
                        <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">{{ violation.description || '—' }}</p>
                    </div>
                    <div v-if="violation.sanction" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500">Sanction</label>
                        <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">{{ violation.sanction }}</p>
                    </div>
                    <div v-if="violation.remarks" class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500">Remarks</label>
                        <p class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">{{ violation.remarks }}</p>
                    </div>
                </div>
            </div>

            <!-- Narrative Report - Inline Editable -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Narrative Report</h3>
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
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Scheduled Meetings</h3>
                    <PrimaryButton type="button" @click="openScheduleMeeting">Schedule Meeting</PrimaryButton>
                </div>
                <div v-if="meetings && meetings.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="m in meetings" :key="m.meeting_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ m.meeting_date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ m.meeting_time || '—'
                                    }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ m.location || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getMeetingStatusColor(m.status)">
                                        {{ m.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-900"
                                        @click="openEditMeeting(m)">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No meetings scheduled.</p>
            </div>

            <!-- History / Timeline -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status History</h3>
                <div v-if="history && history.length > 0" class="space-y-3">
                    <div v-for="h in history" :key="h.history_id" class="flex items-start gap-3 text-sm">
                        <span class="text-gray-500 shrink-0">{{ h.created_at }}</span>
                        <span class="text-gray-700">
                            {{ h.old_status || '—' }} → {{ h.new_status || '—' }}
                            <span v-if="h.note" class="text-gray-500">({{ h.note }})</span>
                            <span v-if="h.changed_by" class="text-gray-400"> by {{ h.changed_by }}</span>
                        </span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">No status changes yet.</p>
            </div>
        </div>

        <DisciplineFormModal :show="showEditModal" :violation="editViolation" :enrollments="[]"
            @close="showEditModal = false" @saved="handleSaved" />
        <DisciplineMeetingModal :show="showMeetingModal" :discipline-id="violation.discipline_id"
            :meeting="selectedMeeting" @close="showMeetingModal = false; selectedMeeting = null"
            @saved="handleMeetingSaved" />
    </AdminLayout>
</template>
