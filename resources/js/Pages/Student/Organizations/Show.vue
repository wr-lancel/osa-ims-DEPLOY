<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import HybridTextFileInput from '@/Components/HybridTextFileInput.vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    isOfficer: {
        type: Boolean,
        default: false,
    },
    officerRole: {
        type: String,
        default: null,
    },
});

// Edit Organization Modal
const showEditModal = ref(false);
const editProcessing = ref(false);
const editErrors = ref({});

const editDescription = ref(props.organization.description || '');
const editAdviserName = ref(props.organization.adviser_name || '');
const editMission = ref(props.organization.mission || '');
const editMissionFile = ref(null);
const removeMissionFile = ref(false);
const editVision = ref(props.organization.vision || '');
const editVisionFile = ref(null);
const removeVisionFile = ref(false);
const editGoals = ref(props.organization.goals || '');
const editGoalsFile = ref(null);
const removeGoalsFile = ref(false);
const editConstitution = ref(props.organization.constitution_bylaws || '');
const editConstitutionFile = ref(null);
const removeConstitutionFile = ref(false);

const openEditModal = () => {
    editDescription.value = props.organization.description || '';
    editAdviserName.value = props.organization.adviser_name || '';
    editMission.value = props.organization.mission || '';
    editMissionFile.value = null;
    removeMissionFile.value = false;
    editVision.value = props.organization.vision || '';
    editVisionFile.value = null;
    removeVisionFile.value = false;
    editGoals.value = props.organization.goals || '';
    editGoalsFile.value = null;
    removeGoalsFile.value = false;
    editConstitution.value = props.organization.constitution_bylaws || '';
    editConstitutionFile.value = null;
    removeConstitutionFile.value = false;
    editErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    editProcessing.value = true;
    editErrors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('description', editDescription.value || '');
    formData.append('adviser_name', editAdviserName.value || '');

    // Mission
    formData.append('mission', editMission.value || '');
    if (editMissionFile.value) formData.append('mission_file', editMissionFile.value);
    if (removeMissionFile.value) formData.append('remove_mission_file', '1');

    // Vision
    formData.append('vision', editVision.value || '');
    if (editVisionFile.value) formData.append('vision_file', editVisionFile.value);
    if (removeVisionFile.value) formData.append('remove_vision_file', '1');

    // Goals
    formData.append('goals', editGoals.value || '');
    if (editGoalsFile.value) formData.append('goals_file', editGoalsFile.value);
    if (removeGoalsFile.value) formData.append('remove_goals_file', '1');

    // Constitution & By-Laws
    formData.append('constitution_bylaws', editConstitution.value || '');
    if (editConstitutionFile.value) formData.append('constitution_bylaws_file', editConstitutionFile.value);
    if (removeConstitutionFile.value) formData.append('remove_constitution_bylaws_file', '1');

    router.post(route('student.organizations.update', props.organization.org_id), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeEditModal(),
        onError: (errors) => { editErrors.value = errors; },
        onFinish: () => { editProcessing.value = false; },
    });
};

// Shared office hours & weekend validation helpers
const officeHours = [
    { value: '08:00', label: '8:00 AM' },
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    { value: '12:00', label: '12:00 PM' },
    { value: '13:00', label: '1:00 PM' },
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
    { value: '17:00', label: '5:00 PM' },
];

const isWeekend = (dateStr) => {
    if (!dateStr) return false;
    const [year, month, day] = dateStr.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    return d.getDay() === 0 || d.getDay() === 6;
};

// Create Event Modal
const showEventModal = ref(false);
const showEventConflictDialog = ref(false);
const eventConflictMessage = ref('');
const eventWeekendError = ref('');

const onEventDateChange = () => {
    if (isWeekend(eventForm.event_date)) {
        eventForm.event_date = '';
        eventWeekendError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        eventWeekendError.value = '';
    }
};
const eventForm = useForm({
    event_name: '',
    description: '',
    event_date: new Date().toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    venue: '',
    status: 'Planning',
    confirm_date_conflict: '',
});

const statusOptions = [
    { value: 'Planning', label: 'Planning' },
    { value: 'Upcoming', label: 'Upcoming' },
];

const openEventModal = () => {
    eventForm.reset();
    eventForm.event_date = new Date().toISOString().split('T')[0];
    eventForm.status = 'Planning';
    eventForm.confirm_date_conflict = '';
    showEventModal.value = true;
};

const closeEventModal = () => {
    showEventModal.value = false;
    eventForm.reset();
    eventWeekendError.value = '';
};

const doSubmitEvent = () => {
    eventForm.post(route('student.organizations.events.store', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => {
            eventForm.confirm_date_conflict = '';
            closeEventModal();
        },
        onError: (errors) => {
            if (errors.date_conflict && Array.isArray(errors.date_conflict) && errors.date_conflict[0]) {
                eventConflictMessage.value = errors.date_conflict[0];
                showEventConflictDialog.value = true;
            }
        },
    });
};

const submitEvent = () => {
    doSubmitEvent();
};

const confirmEventConflictAndSubmit = () => {
    showEventConflictDialog.value = false;
    eventForm.confirm_date_conflict = '1';
    doSubmitEvent();
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'Upcoming': return 'bg-blue-100 text-blue-800';
        case 'Planning': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Call Meeting Modal
const meetingWeekendError = ref('');

const onMeetingDateChange = () => {
    if (isWeekend(meetingForm.meeting_date)) {
        meetingForm.meeting_date = '';
        meetingWeekendError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        meetingWeekendError.value = '';
    }
};

const showMeetingModal = ref(false);
const meetingForm = useForm({
    title: '',
    description: '',
    meeting_date: new Date().toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    venue: '',
    target_audience: 'all',
});

const audienceOptions = [
    { value: 'all', label: 'All (Officers & Members)' },
    { value: 'officers', label: 'Officers Only' },
    { value: 'members', label: 'Members Only' },
];

const openMeetingModal = () => {
    meetingForm.reset();
    meetingForm.meeting_date = new Date().toISOString().split('T')[0];
    meetingForm.target_audience = 'all';
    showMeetingModal.value = true;
};

const closeMeetingModal = () => {
    showMeetingModal.value = false;
    meetingForm.reset();
    meetingWeekendError.value = '';
};

const submitMeeting = () => {
    meetingForm.post(route('student.organizations.meetings.store', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeMeetingModal(),
    });
};

const getMeetingStatusColor = (status) => {
    switch (status) {
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'completed': return 'bg-green-100 text-green-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getAudienceLabel = (audience) => {
    switch (audience) {
        case 'officers': return 'Officers';
        case 'members': return 'Members';
        case 'all': return 'All';
        default: return audience;
    }
};
</script>

<template>

    <Head :title="`${organization.org_name} - Organization`" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white transition-colors">
                        {{ organization.org_name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ organization.org_code }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="isOfficer"
                        class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 transition-colors">
                        {{ officerRole }}
                    </span>
                    <Link :href="route('student.organizations.index')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm transition-colors">
                        ← Back to Organizations
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Officer Actions Banner -->
            <div v-if="isOfficer" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-4 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        <div>
                            <p class="font-medium text-indigo-900 dark:text-indigo-300 transition-colors">You are an officer of this organization</p>
                            <p class="text-sm text-indigo-700 dark:text-indigo-400 transition-colors">You can edit organization details and create events.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <SecondaryButton @click="openEditModal">
                            Edit Details
                        </SecondaryButton>
                        <SecondaryButton @click="openMeetingModal">
                            📢 Call Meeting
                        </SecondaryButton>
                        <PrimaryButton @click="openEventModal">
                            Create Event
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Organization Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">Organization Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors">Type</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 transition-colors">{{ organization.type || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors">Status</label>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full transition-colors" :class="{
                            'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400': organization.status === 'active',
                            'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300': organization.status === 'inactive',
                        }">
                            {{ organization.status }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors">Adviser</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 transition-colors">{{ organization.adviser_name || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors">Total Officers</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 transition-colors">{{ organization.officers?.length || 0 }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 transition-colors">Description</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-200 transition-colors">{{ organization.description || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- About the Organization (Mission, Vision, Goals, Constitution & By-Laws) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">About the Organization</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mission -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 transition-colors">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2 transition-colors">
                            <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Mission
                        </h4>
                        <div v-if="organization.mission || organization.mission_file_url">
                            <p v-if="organization.mission" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-2 transition-colors">{{
                                organization.mission }}</p>
                            <a v-if="organization.mission_file_url" :href="organization.mission_file_url"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                {{ organization.mission_file_name }}
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 italic transition-colors">No mission uploaded yet.</p>
                    </div>

                    <!-- Vision -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 transition-colors">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2 transition-colors">
                            <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Vision
                        </h4>
                        <div v-if="organization.vision || organization.vision_file_url">
                            <p v-if="organization.vision" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-2 transition-colors">{{
                                organization.vision }}</p>
                            <a v-if="organization.vision_file_url" :href="organization.vision_file_url" target="_blank"
                                class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                {{ organization.vision_file_name }}
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 italic transition-colors">No vision uploaded yet.</p>
                    </div>

                    <!-- Goals -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 transition-colors">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2 transition-colors">
                            <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Goals
                        </h4>
                        <div v-if="organization.goals || organization.goals_file_url">
                            <p v-if="organization.goals" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mb-2 transition-colors">{{
                                organization.goals }}</p>
                            <a v-if="organization.goals_file_url" :href="organization.goals_file_url" target="_blank"
                                class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                {{ organization.goals_file_name }}
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 italic transition-colors">No goals uploaded yet.</p>
                    </div>

                    <!-- Constitution & By-Laws -->
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 transition-colors">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2 transition-colors">
                            <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Constitution & By-Laws
                        </h4>
                        <div v-if="organization.constitution_bylaws_file_url">
                            <a :href="organization.constitution_bylaws_file_url" target="_blank"
                                class="flex items-center gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors group">
                                <div
                                    class="flex-shrink-0 w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/60 rounded-lg flex items-center justify-center transition-colors">
                                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-indigo-900 dark:text-indigo-300 truncate transition-colors">{{
                                        organization.constitution_bylaws_file_name }}</p>
                                    <p class="text-xs text-indigo-500 dark:text-indigo-400 transition-colors">Click to download</p>
                                </div>
                                <span
                                    class="flex-shrink-0 inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-600 dark:bg-indigo-500 text-white transition-colors">
                                    Download
                                </span>
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 italic transition-colors">No constitution & by-laws uploaded yet.</p>
                    </div>
                </div>
            </div>

            <!-- Officers Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">Officers</h3>
                <div v-if="organization.officers && organization.officers.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Position
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Since</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="officer in organization.officers" :key="officer.officer_id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200 transition-colors">{{ officer.student_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 transition-colors">{{ officer.student_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 transition-colors">
                                        {{ officer.position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ officer.start_date }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No officers assigned.</p>
            </div>

            <!-- Members Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">Members</h3>
                <div v-if="organization.members && organization.members.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Student No.
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Join Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="member in organization.members" :key="member.member_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 transition-colors">
                                    {{ member.student_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ member.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ member.join_date }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No members registered.</p>
            </div>

            <!-- Events Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 transition-colors">Events</h3>
                    <PrimaryButton v-if="isOfficer" @click="openEventModal">
                        Create Event
                    </PrimaryButton>
                </div>

                <div v-if="organization.events && organization.events.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Event Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Date & Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="event in organization.events" :key="event.event_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200 transition-colors">{{ event.event_name }}</div>
                                    <div v-if="event.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 transition-colors">
                                        {{ event.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    <div>{{ event.event_date }}</div>
                                    <div v-if="event.start_time" class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ event.start_time }}
                                        <span v-if="event.end_time"> - {{ event.end_time }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ event.venue || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full dark:bg-opacity-20 transition-colors"
                                        :class="getStatusColor(event.status)">
                                        {{ event.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No events scheduled.</p>
            </div>

            <!-- Meetings Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 transition-colors">Meetings</h3>
                    <PrimaryButton v-if="isOfficer" @click="openMeetingModal">
                        📢 Call Meeting
                    </PrimaryButton>
                </div>

                <div v-if="organization.meetings && organization.meetings.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Date & Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Audience
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Called By
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="meeting in organization.meetings" :key="meeting.meeting_id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200 transition-colors">{{ meeting.title }}</div>
                                    <div v-if="meeting.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 transition-colors">
                                        {{ meeting.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    <div>{{ meeting.meeting_date }}</div>
                                    <div v-if="meeting.start_time" class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">
                                        {{ meeting.start_time }}
                                        <span v-if="meeting.end_time"> - {{ meeting.end_time }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ meeting.venue || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 transition-colors">
                                        {{ getAudienceLabel(meeting.target_audience) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full dark:bg-opacity-20 transition-colors"
                                        :class="getMeetingStatusColor(meeting.status)">
                                        {{ meeting.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ meeting.called_by_name || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No meetings scheduled.</p>
            </div>
        </div>

        <!-- Edit Organization Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">Edit Organization</h2>

                <form @submit.prevent="submitEdit">
                    <!-- Description -->
                    <div class="mb-4">
                        <InputLabel for="description" value="Description" />
                        <textarea id="description" v-model="editDescription" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="editErrors.description" />
                    </div>

                    <!-- Adviser Name -->
                    <div class="mb-4">
                        <InputLabel for="adviser_name" value="Adviser Name" />
                        <TextInput id="adviser_name" v-model="editAdviserName" type="text" class="mt-1 block w-full"
                            placeholder="Enter adviser name..." />
                        <InputError :message="editErrors.adviser_name" />
                    </div>

                    <!-- Mission -->
                    <div class="mb-4">
                        <HybridTextFileInput label="Mission" :text="editMission"
                            :existing-file-url="organization.mission_file_url"
                            :existing-file-name="organization.mission_file_name" accept=".pdf,.doc,.docx"
                            placeholder="Type the mission statement..." :text-error="editErrors.mission"
                            :file-error="editErrors.mission_file" @update:text="(val) => editMission = val"
                            @update:file="(file) => { editMissionFile = file; removeMissionFile = false; }"
                            @remove-file="() => { editMissionFile = null; removeMissionFile = true; }" />
                    </div>

                    <!-- Vision -->
                    <div class="mb-4">
                        <HybridTextFileInput label="Vision" :text="editVision"
                            :existing-file-url="organization.vision_file_url"
                            :existing-file-name="organization.vision_file_name" accept=".pdf,.doc,.docx"
                            placeholder="Type the vision statement..." :text-error="editErrors.vision"
                            :file-error="editErrors.vision_file" @update:text="(val) => editVision = val"
                            @update:file="(file) => { editVisionFile = file; removeVisionFile = false; }"
                            @remove-file="() => { editVisionFile = null; removeVisionFile = true; }" />
                    </div>

                    <!-- Goals -->
                    <div class="mb-4">
                        <HybridTextFileInput label="Goals" :text="editGoals"
                            :existing-file-url="organization.goals_file_url"
                            :existing-file-name="organization.goals_file_name" accept=".pdf,.doc,.docx"
                            placeholder="Type the organization's goals..." :text-error="editErrors.goals"
                            :file-error="editErrors.goals_file" @update:text="(val) => editGoals = val"
                            @update:file="(file) => { editGoalsFile = file; removeGoalsFile = false; }"
                            @remove-file="() => { editGoalsFile = null; removeGoalsFile = true; }" />
                    </div>

                    <!-- Constitution & By-Laws (file only) -->
                    <div class="mb-6">
                        <InputLabel value="Constitution & By-Laws" />
                        <div v-if="organization.constitution_bylaws_file_url && !removeConstitutionFile"
                            class="mt-1 mb-2 flex items-center gap-2 text-sm">
                            <svg class="h-4 w-4 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <a :href="organization.constitution_bylaws_file_url" target="_blank"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 underline">
                                {{ organization.constitution_bylaws_file_name }}
                            </a>
                            <button type="button" class="text-red-500 hover:text-red-700 text-xs ml-2"
                                @click="removeConstitutionFile = true; editConstitutionFile = null">
                                Remove
                            </button>
                        </div>
                        <input type="file" accept=".pdf,.doc,.docx"
                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/50 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800/60 transition-colors"
                            @change="(e) => { editConstitutionFile = e.target.files[0]; removeConstitutionFile = false; }" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">Upload a .pdf, .doc, or .docx file (max 10MB).</p>
                        <InputError :message="editErrors.constitution_bylaws_file" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeEditModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editProcessing">
                            {{ editProcessing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create Event Modal -->
        <Modal :show="showEventModal" @close="closeEventModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">Create Event</h2>

                <form @submit.prevent="submitEvent">
                    <!-- Event Name -->
                    <div class="mb-4">
                        <InputLabel for="event_name" value="Event Name" />
                        <TextInput id="event_name" v-model="eventForm.event_name" type="text" class="mt-1 block w-full"
                            :class="{ 'border-red-300 dark:border-red-600': eventForm.errors.event_name }" required />
                        <InputError :message="eventForm.errors.event_name" />
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <InputLabel for="event_description" value="Description" />
                        <textarea id="event_description" v-model="eventForm.description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100" />
                        <InputError :message="eventForm.errors.description" />
                    </div>

                    <!-- Event Date -->
                    <div class="mb-4">
                        <InputLabel for="event_date" value="Event Date" />
                        <TextInput id="event_date" v-model="eventForm.event_date" type="date" class="mt-1 block w-full"
                            :class="{ 'border-red-300 dark:border-red-600': eventForm.errors.event_date }" required
                            :min="new Date().toISOString().split('T')[0]"
                            @change="onEventDateChange" />
                        <p v-if="eventWeekendError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ eventWeekendError }}</p>
                        <InputError :message="eventForm.errors.event_date" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Start Time -->
                        <div>
                            <InputLabel for="start_time" value="Start Time" />
                            <select id="start_time" v-model="eventForm.start_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Select —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="eventForm.errors.start_time" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <InputLabel for="end_time" value="End Time" />
                            <select id="end_time" v-model="eventForm.end_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Select —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="eventForm.errors.end_time" />
                        </div>
                    </div>

                    <!-- Venue -->
                    <div class="mb-4">
                        <InputLabel for="venue" value="Venue" />
                        <TextInput id="venue" v-model="eventForm.venue" type="text" class="mt-1 block w-full"
                            placeholder="e.g., Gymnasium, Auditorium..." />
                        <InputError :message="eventForm.errors.venue" />
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <InputLabel for="status" value="Status" />
                        <select id="status" v-model="eventForm.status"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                            required>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="eventForm.errors.status" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeEventModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="eventForm.processing">
                            {{ eventForm.processing ? 'Creating...' : 'Create Event' }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Event date conflict confirmation -->
                <div
                    v-if="showEventConflictDialog"
                    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 dark:bg-black/70 backdrop-blur-sm transition-colors"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="student-conflict-dialog-title"
                >
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg max-w-md w-full p-6 transition-colors border border-transparent dark:border-gray-700">
                        <h3 id="student-conflict-dialog-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 transition-colors">
                            Event date conflict
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 transition-colors">
                            {{ eventConflictMessage }}
                        </p>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="showEventConflictDialog = false">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="button" @click="confirmEventConflictAndSubmit">
                                Continue anyway
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Call Meeting Modal -->
        <Modal :show="showMeetingModal" @close="closeMeetingModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">📢 Call Meeting</h2>

                <form @submit.prevent="submitMeeting">
                    <!-- Meeting Title -->
                    <div class="mb-4">
                        <InputLabel for="meeting_title" value="Meeting Title" />
                        <TextInput id="meeting_title" v-model="meetingForm.title" type="text" class="mt-1 block w-full"
                            :class="{ 'border-red-300 dark:border-red-600': meetingForm.errors.title }"
                            placeholder="e.g., General Assembly, Board Meeting..." required />
                        <InputError :message="meetingForm.errors.title" />
                    </div>

                    <!-- Meeting Date -->
                    <div class="mb-4">
                        <InputLabel for="meeting_date" value="Meeting Date" />
                        <TextInput id="meeting_date" v-model="meetingForm.meeting_date" type="date"
                            class="mt-1 block w-full" :class="{ 'border-red-300 dark:border-red-600': meetingForm.errors.meeting_date }"
                            :min="new Date().toISOString().split('T')[0]"
                            required @change="onMeetingDateChange" />
                        <p v-if="meetingWeekendError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ meetingWeekendError }}</p>
                        <InputError :message="meetingForm.errors.meeting_date" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Start Time -->
                        <div>
                            <InputLabel for="meeting_start_time" value="Start Time" />
                            <select id="meeting_start_time" v-model="meetingForm.start_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                required>
                                <option value="">— Select —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="meetingForm.errors.start_time" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <InputLabel for="meeting_end_time" value="End Time" />
                            <select id="meeting_end_time" v-model="meetingForm.end_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Select —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="meetingForm.errors.end_time" />
                        </div>
                    </div>

                    <!-- Venue -->
                    <div class="mb-4">
                        <InputLabel for="meeting_venue" value="Venue" />
                        <TextInput id="meeting_venue" v-model="meetingForm.venue" type="text" class="mt-1 block w-full"
                            placeholder="e.g., Room 101, Gymnasium..." />
                        <InputError :message="meetingForm.errors.venue" />
                    </div>

                    <!-- Target Audience -->
                    <div class="mb-4">
                        <InputLabel for="meeting_target_audience" value="Notify" />
                        <select id="meeting_target_audience" v-model="meetingForm.target_audience"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                            required>
                            <option v-for="option in audienceOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="meetingForm.errors.target_audience" />
                    </div>

                    <!-- Description / Agenda -->
                    <div class="mb-6">
                        <InputLabel for="meeting_description" value="Agenda / Description (optional)" />
                        <textarea id="meeting_description" v-model="meetingForm.description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Brief description or agenda items..." />
                        <InputError :message="meetingForm.errors.description" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeMeetingModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="meetingForm.processing">
                            {{ meetingForm.processing ? 'Scheduling...' : 'Schedule Meeting' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </StudentLayout>
</template>
