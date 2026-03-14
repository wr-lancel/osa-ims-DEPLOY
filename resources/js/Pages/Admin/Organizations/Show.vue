<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, confirmAction, closeNotification, handleConfirm } = useNotification();
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import HybridTextFileInput from '@/Components/HybridTextFileInput.vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    enrolledStudents: {
        type: Array,
        default: () => [],
    },
    organizationTypes: {
        type: Array,
        default: () => ['Academic', 'Cultural', 'Governance', 'Special Interest'],
    },
});

const isProcessing = ref(false);

// Officer form
const showOfficerModal = ref(false);
const officerForm = useForm({
    student_number: '',
    position: '',
    start_date: new Date().toISOString().split('T')[0],
});

const studentOptions = computed(() => {
    return props.enrolledStudents.map(s => ({
        value: s.student_number,
        label: `${s.full_name} (${s.student_number}) - ${s.course_code || ''} ${s.year_level || ''}`.trim(),
        full_name: s.full_name,
        student_number: s.student_number,
        course_code: s.course_code,
        year_level: s.year_level,
    }));
});

const selectedStudent = computed(() => {
    if (!officerForm.student_number) return null;
    return props.enrolledStudents.find(s => s.student_number == officerForm.student_number);
});

const openOfficerModal = () => {
    officerForm.reset();
    officerForm.start_date = new Date().toISOString().split('T')[0];
    showOfficerModal.value = true;
};

const closeOfficerModal = () => {
    showOfficerModal.value = false;
    officerForm.reset();
};

const submitOfficer = () => {
    officerForm.post(route('admin.organizations.officers.add', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeOfficerModal(),
    });
};

const removeOfficer = (officer) => {
    confirmAction(
        `Are you sure you want to remove ${officer.student_name} as ${officer.position}?`,
        'Remove Officer',
        () => {
            isProcessing.value = true;
            router.delete(route('admin.organizations.officers.remove', [props.organization.org_id, officer.officer_id]), {
                preserveScroll: true,
                onFinish: () => { isProcessing.value = false; },
            });
        },
        { confirmLabel: 'Remove', cancelLabel: 'Cancel' }
    );
};

// Adviser form
const adviserForm = useForm({
    adviser_name: props.organization.adviser_name || '',
});

const saveAdviser = () => {
    adviserForm.put(route('admin.organizations.adviser.update', props.organization.org_id), {
        preserveScroll: true,
    });
};

// Common officer positions for suggestions
const positionSuggestions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'PIO',
    'Business Manager',
    'Sergeant-at-Arms',
];

// Edit organization
const showEditModal = ref(false);
const editForm = ref({
    org_name: props.organization.org_name,
    org_code: props.organization.org_code,
    description: props.organization.description || '',
    type: props.organization.type || '',
    status: props.organization.status,
    adviser_name: props.organization.adviser_name || '',
    mission: props.organization.mission || '',
    mission_file: null,
    remove_mission_file: false,
    vision: props.organization.vision || '',
    vision_file: null,
    remove_vision_file: false,
    goals: props.organization.goals || '',
    goals_file: null,
    remove_goals_file: false,
    constitution_bylaws: props.organization.constitution_bylaws || '',
    constitution_bylaws_file: null,
    remove_constitution_bylaws_file: false,
});
const editFormErrors = ref({});
const editFormProcessing = ref(false);

const typeOptions = computed(() => [
    { value: '', label: 'Select Type' },
    ...props.organizationTypes.map(t => ({ value: t, label: t })),
]);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const openEditModal = () => {
    editForm.value = {
        org_name: props.organization.org_name,
        org_code: props.organization.org_code,
        description: props.organization.description || '',
        type: props.organization.type || '',
        status: props.organization.status,
        adviser_name: props.organization.adviser_name || '',
        mission: props.organization.mission || '',
        mission_file: null,
        remove_mission_file: false,
        vision: props.organization.vision || '',
        vision_file: null,
        remove_vision_file: false,
        goals: props.organization.goals || '',
        goals_file: null,
        remove_goals_file: false,
        constitution_bylaws: props.organization.constitution_bylaws || '',
        constitution_bylaws_file: null,
        remove_constitution_bylaws_file: false,
    };
    editFormErrors.value = {};
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    editFormProcessing.value = true;
    editFormErrors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('org_name', editForm.value.org_name);
    formData.append('org_code', editForm.value.org_code);
    formData.append('description', editForm.value.description || '');
    formData.append('type', editForm.value.type || '');
    formData.append('status', editForm.value.status);
    formData.append('adviser_name', editForm.value.adviser_name || '');
    formData.append('mission', editForm.value.mission || '');
    formData.append('vision', editForm.value.vision || '');
    formData.append('goals', editForm.value.goals || '');
    formData.append('constitution_bylaws', editForm.value.constitution_bylaws || '');

    // File uploads
    if (editForm.value.mission_file) formData.append('mission_file', editForm.value.mission_file);
    if (editForm.value.vision_file) formData.append('vision_file', editForm.value.vision_file);
    if (editForm.value.goals_file) formData.append('goals_file', editForm.value.goals_file);
    if (editForm.value.constitution_bylaws_file) formData.append('constitution_bylaws_file', editForm.value.constitution_bylaws_file);

    // Remove flags
    if (editForm.value.remove_mission_file) formData.append('remove_mission_file', '1');
    if (editForm.value.remove_vision_file) formData.append('remove_vision_file', '1');
    if (editForm.value.remove_goals_file) formData.append('remove_goals_file', '1');
    if (editForm.value.remove_constitution_bylaws_file) formData.append('remove_constitution_bylaws_file', '1');

    router.post(route('admin.organizations.update', props.organization.org_id), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeEditModal();
        },
        onError: (errors) => {
            editFormErrors.value = errors;
        },
        onFinish: () => {
            editFormProcessing.value = false;
        },
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'Upcoming': return 'bg-blue-100 text-blue-800';
        case 'Planning': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Meeting form
const meetingWeekendError = ref('');

const officeHours = [
    { value: '08:00', label: '8:00 AM' },
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    { value: '13:00', label: '1:00 PM' },
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
];

const onMeetingDateChange = () => {
    if (!meetingForm.meeting_date) return;
    const [year, month, day] = meetingForm.meeting_date.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    if (d.getDay() === 0 || d.getDay() === 6) {
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

const openMeetingModal = () => {
    meetingForm.reset();
    meetingForm.meeting_date = new Date().toISOString().split('T')[0];
    showMeetingModal.value = true;
};

const closeMeetingModal = () => {
    showMeetingModal.value = false;
    meetingForm.reset();
    meetingWeekendError.value = '';
};

const submitMeeting = () => {
    meetingForm.post(route('admin.organizations.meetings.store', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeMeetingModal(),
    });
};

const updateMeetingStatus = (meeting, status) => {
    const label = status === 'completed' ? 'mark as completed' : 'cancel';
    confirmAction(
        `Are you sure you want to ${label} this meeting?`,
        status === 'completed' ? 'Complete Meeting' : 'Cancel Meeting',
        () => {
            isProcessing.value = true;
            router.put(route('admin.organizations.meetings.updateStatus', [props.organization.org_id, meeting.meeting_id]), {
                status: status,
            }, {
                preserveScroll: true,
                onFinish: () => { isProcessing.value = false; },
            });
        },
        { confirmLabel: status === 'completed' ? 'Complete' : 'Cancel Meeting', cancelLabel: 'Go Back' }
    );
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
        case 'officers': return 'Officers Only';
        case 'members': return 'Members Only';
        case 'all': return 'All (Officers & Members)';
        default: return audience;
    }
};

// Helper to check if about section has any content
const hasAboutContent = computed(() => {
    const o = props.organization;
    return o.mission || o.mission_file_url || o.vision || o.vision_file_url
        || o.goals || o.goals_file_url || o.constitution_bylaws || o.constitution_bylaws_file_url;
});
</script>

<template>

    <Head :title="`${organization.org_name} - Organization Details`" />

    <AdminLayout>
        <LoadingOverlay :show="isProcessing || editFormProcessing || officerForm.processing || adviserForm.processing || meetingForm.processing" message="Processing... Please wait." />
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 flex-shrink-0">
                        <img v-if="organization.logo_url" :src="organization.logo_url" class="h-16 w-16 rounded-xl object-contain bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 transition-colors" alt="Organization Logo" />
                        <div v-else class="h-16 w-16 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center border border-indigo-200 dark:border-indigo-800/50 shadow-sm transition-colors">
                            <span class="text-indigo-700 dark:text-indigo-300 font-bold text-xl transition-colors">{{ organization.org_code.substring(0, 2).toUpperCase() }}</span>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 transition-colors">
                            {{ organization.org_name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ organization.org_code }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <SecondaryButton @click="openMeetingModal">
                        Call Meeting
                    </SecondaryButton>
                    <Link :href="route('admin.organizations.candidacies.index', organization.org_id)">
                        <SecondaryButton type="button">Candidacy Applications</SecondaryButton>
                    </Link>
                    <SecondaryButton @click="openEditModal">
                        Edit Organization
                    </SecondaryButton>
                    <Link :href="route('admin.organizations.index')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm transition-colors">
                        ← Back to Organizations
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
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
            <div v-if="hasAboutContent" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">About the Organization</h3>
                <div class="space-y-5">
                    <!-- Mission -->
                    <div v-if="organization.mission || organization.mission_file_url">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 transition-colors">Mission</h4>
                        <p v-if="organization.mission" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap transition-colors">{{ organization.mission }}</p>
                        <div v-if="organization.mission_file_url" class="flex items-center gap-2 mt-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 dark:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <a :href="organization.mission_file_url" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 underline transition-colors">
                                {{ organization.mission_file_name || 'Download' }}
                            </a>
                        </div>
                    </div>

                    <!-- Vision -->
                    <div v-if="organization.vision || organization.vision_file_url">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 transition-colors">Vision</h4>
                        <p v-if="organization.vision" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap transition-colors">{{ organization.vision }}</p>
                        <div v-if="organization.vision_file_url" class="flex items-center gap-2 mt-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 dark:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <a :href="organization.vision_file_url" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 underline transition-colors">
                                {{ organization.vision_file_name || 'Download' }}
                            </a>
                        </div>
                    </div>

                    <!-- Goals -->
                    <div v-if="organization.goals || organization.goals_file_url">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 transition-colors">Goals</h4>
                        <p v-if="organization.goals" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap transition-colors">{{ organization.goals }}</p>
                        <div v-if="organization.goals_file_url" class="flex items-center gap-2 mt-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 dark:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <a :href="organization.goals_file_url" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 underline transition-colors">
                                {{ organization.goals_file_name || 'Download' }}
                            </a>
                        </div>
                    </div>

                    <!-- Constitution & By-Laws -->
                    <div v-if="organization.constitution_bylaws || organization.constitution_bylaws_file_url">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1 transition-colors">Constitution & By-Laws</h4>
                        <p v-if="organization.constitution_bylaws" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap transition-colors">{{ organization.constitution_bylaws }}</p>
                        <div v-if="organization.constitution_bylaws_file_url" class="flex items-center gap-2 mt-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500 dark:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <a :href="organization.constitution_bylaws_file_url" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 underline transition-colors">
                                {{ organization.constitution_bylaws_file_name || 'Download' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Officers Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 transition-colors">Officers</h3>
                    <PrimaryButton @click="openOfficerModal">
                        Add Officer
                    </PrimaryButton>
                </div>

                <div v-if="organization.officers && organization.officers.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Student No.
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Position
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Start Date
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase transition-colors">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="officer in organization.officers" :key="officer.officer_id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 transition-colors">
                                    {{ officer.student_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ officer.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-400 transition-colors">
                                        {{ officer.position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ officer.start_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="removeOfficer(officer)" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No officers assigned yet.</p>
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
                    <Link :href="route('admin.organizations.events.index') + '?org_id=' + organization.org_id">
                        <SecondaryButton>
                            Manage Events
                        </SecondaryButton>
                    </Link>
                </div>

                <div v-if="organization.events && organization.events.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Event Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Date & Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Created By
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                            <tr v-for="event in organization.events" :key="event.event_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200 transition-colors">
                                    {{ event.event_name }}
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
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full transition-colors"
                                        :class="getStatusColor(event.status)">
                                        {{ event.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                    {{ event.created_by_name || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No events scheduled.</p>
            </div>
        </div>

        <!-- Meetings Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 transition-colors">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 transition-colors">Meetings</h3>
                <SecondaryButton @click="openMeetingModal">
                    Schedule Meeting
                </SecondaryButton>
            </div>

            <div v-if="organization.meetings && organization.meetings.length > 0" class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 transition-colors">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Audience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Called By</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider transition-colors">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 transition-colors">
                        <tr v-for="meeting in organization.meetings" :key="meeting.meeting_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-200 transition-colors">{{ meeting.title }}</div>
                                <div v-if="meeting.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs truncate transition-colors">{{
                                    meeting.description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                <div>{{ meeting.meeting_date }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">
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
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full transition-colors"
                                    :class="getMeetingStatusColor(meeting.status)">
                                    {{ meeting.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 transition-colors">
                                {{ meeting.called_by_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button v-if="meeting.status === 'scheduled'"
                                    @click="updateMeetingStatus(meeting, 'completed')"
                                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors">
                                    Complete
                                </button>
                                <button v-if="meeting.status === 'scheduled'"
                                    @click="updateMeetingStatus(meeting, 'cancelled')"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                    Cancel
                                </button>
                                <span v-if="meeting.status !== 'scheduled'" class="text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400 transition-colors">No meetings scheduled yet.</p>
        </div>

        <!-- Add Officer Modal -->
        <Modal :show="showOfficerModal" @close="closeOfficerModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">Add Officer</h2>

                <form @submit.prevent="submitOfficer">
                    <!-- Student Selection -->
                    <div class="mb-4">
                        <InputLabel for="student_number" value="Select Student" />
                        <SearchableSelect v-model="officerForm.student_number" :options="studentOptions"
                            placeholder="Search by name or student number..."
                            :error="officerForm.errors.student_number" />
                    </div>

                    <!-- Position -->
                    <div class="mb-4">
                        <InputLabel for="position" value="Position" />
                        <TextInput id="position" v-model="officerForm.position" type="text" class="mt-1 block w-full"
                            :class="{ 'border-red-300': officerForm.errors.position }"
                            placeholder="e.g., President, Vice President, Secretary..." list="position-suggestions"
                            required />
                        <datalist id="position-suggestions">
                            <option v-for="pos in positionSuggestions" :key="pos" :value="pos" />
                        </datalist>
                        <InputError :message="officerForm.errors.position" />
                    </div>

                    <!-- Start Date -->
                    <div class="mb-6">
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput id="start_date" v-model="officerForm.start_date" type="date"
                            class="mt-1 block w-full" :class="{ 'border-red-300': officerForm.errors.start_date }"
                            required />
                        <InputError :message="officerForm.errors.start_date" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="closeOfficerModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="officerForm.processing">
                            {{ officerForm.processing ? 'Adding...' : 'Add Officer' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Organization Modal -->
        <Modal :show="showEditModal" @close="closeEditModal" max-width="3xl">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">Edit Organization</h2>

                <form @submit.prevent="submitEdit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Organization Name -->
                        <div class="md:col-span-2">
                            <InputLabel for="edit_org_name" value="Organization Name" />
                            <TextInput id="edit_org_name" v-model="editForm.org_name" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-300': editFormErrors.org_name }"
                                required />
                            <InputError :message="editFormErrors.org_name" />
                        </div>

                        <!-- Organization Code -->
                        <div>
                            <InputLabel for="edit_org_code" value="Organization Code" />
                            <TextInput id="edit_org_code" v-model="editForm.org_code" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-300': editFormErrors.org_code }"
                                required />
                            <InputError :message="editFormErrors.org_code" />
                        </div>

                        <!-- Type -->
                        <div>
                            <InputLabel for="edit_type" value="Type" />
                            <select id="edit_type" v-model="editForm.type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="option in typeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="editFormErrors.type" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="edit_status" value="Status" />
                            <select id="edit_status" v-model="editForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                                required>
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="editFormErrors.status" />
                        </div>

                        <!-- Adviser Name -->
                        <div>
                            <InputLabel for="edit_adviser_name" value="Adviser Name" />
                            <TextInput id="edit_adviser_name" v-model="editForm.adviser_name" type="text"
                                class="mt-1 block w-full" placeholder="Enter adviser name..." />
                            <InputError :message="editFormErrors.adviser_name" />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <InputLabel for="edit_description" value="Description" />
                            <textarea id="edit_description" v-model="editForm.description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100" />
                            <InputError :message="editFormErrors.description" />
                        </div>
                    </div>

                    <!-- Divider -->
                    <hr class="my-6 border-gray-200 dark:border-gray-700 transition-colors" />

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">Mission, Vision, Goals & Constitution</h3>

                    <div class="space-y-5">
                        <!-- Mission -->
                        <HybridTextFileInput
                            label="Mission"
                            :text="editForm.mission"
                            :existing-file-url="organization.mission_file_url"
                            :existing-file-name="organization.mission_file_name"
                            accept=".pdf,.doc,.docx"
                            placeholder="Enter the organization's mission statement..."
                            :text-error="editFormErrors.mission"
                            :file-error="editFormErrors.mission_file"
                            @update:text="(val) => editForm.mission = val"
                            @update:file="(file) => { editForm.mission_file = file; editForm.remove_mission_file = false; }"
                            @remove-file="() => { editForm.mission_file = null; editForm.remove_mission_file = true; }"
                        />

                        <!-- Vision -->
                        <HybridTextFileInput
                            label="Vision"
                            :text="editForm.vision"
                            :existing-file-url="organization.vision_file_url"
                            :existing-file-name="organization.vision_file_name"
                            accept=".pdf,.doc,.docx"
                            placeholder="Enter the organization's vision statement..."
                            :text-error="editFormErrors.vision"
                            :file-error="editFormErrors.vision_file"
                            @update:text="(val) => editForm.vision = val"
                            @update:file="(file) => { editForm.vision_file = file; editForm.remove_vision_file = false; }"
                            @remove-file="() => { editForm.vision_file = null; editForm.remove_vision_file = true; }"
                        />

                        <!-- Goals -->
                        <HybridTextFileInput
                            label="Goals"
                            :text="editForm.goals"
                            :existing-file-url="organization.goals_file_url"
                            :existing-file-name="organization.goals_file_name"
                            accept=".pdf,.doc,.docx"
                            placeholder="Enter the organization's goals..."
                            :text-error="editFormErrors.goals"
                            :file-error="editFormErrors.goals_file"
                            @update:text="(val) => editForm.goals = val"
                            @update:file="(file) => { editForm.goals_file = file; editForm.remove_goals_file = false; }"
                            @remove-file="() => { editForm.goals_file = null; editForm.remove_goals_file = true; }"
                        />

                        <!-- Constitution & By-Laws -->
                        <HybridTextFileInput
                            label="Constitution & By-Laws"
                            :text="editForm.constitution_bylaws"
                            :existing-file-url="organization.constitution_bylaws_file_url"
                            :existing-file-name="organization.constitution_bylaws_file_name"
                            accept=".pdf,.doc,.docx"
                            placeholder="Enter or summarize the constitution and by-laws..."
                            :text-error="editFormErrors.constitution_bylaws"
                            :file-error="editFormErrors.constitution_bylaws_file"
                            @update:text="(val) => editForm.constitution_bylaws = val"
                            @update:file="(file) => { editForm.constitution_bylaws_file = file; editForm.remove_constitution_bylaws_file = false; }"
                            @remove-file="() => { editForm.constitution_bylaws_file = null; editForm.remove_constitution_bylaws_file = true; }"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <SecondaryButton type="button" @click="closeEditModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editFormProcessing">
                            {{ editFormProcessing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Call Meeting Modal -->
        <Modal :show="showMeetingModal" @close="closeMeetingModal" max-width="2xl">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6 transition-colors">Call Meeting</h2>

                <form @submit.prevent="submitMeeting">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Title -->
                        <div class="md:col-span-2">
                            <InputLabel for="meeting_title" value="Meeting Title" />
                            <TextInput id="meeting_title" v-model="meetingForm.title" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-300': meetingForm.errors.title }"
                                placeholder="e.g., General Assembly, Officers Briefing..." required />
                            <InputError :message="meetingForm.errors.title" />
                        </div>

                        <!-- Date -->
                        <div>
                            <InputLabel for="meeting_date" value="Meeting Date" />
                            <TextInput id="meeting_date" v-model="meetingForm.meeting_date" type="date"
                                class="mt-1 block w-full" :class="{ 'border-red-300': meetingForm.errors.meeting_date }"
                                :min="new Date().toISOString().split('T')[0]"
                                required @change="onMeetingDateChange" />
                            <p v-if="meetingWeekendError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ meetingWeekendError }}</p>
                            <InputError :message="meetingForm.errors.meeting_date" />
                        </div>

                        <!-- Venue -->
                        <div>
                            <InputLabel for="meeting_venue" value="Venue" />
                            <TextInput id="meeting_venue" v-model="meetingForm.venue" type="text"
                                class="mt-1 block w-full" placeholder="e.g., Room 201, Auditorium..." />
                            <InputError :message="meetingForm.errors.venue" />
                        </div>

                        <!-- Start Time -->
                        <div>
                            <InputLabel for="meeting_start_time" value="Start Time" />
                            <select id="meeting_start_time" v-model="meetingForm.start_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-300': meetingForm.errors.start_time }"
                                required>
                                <option value="">— Select Time —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="meetingForm.errors.start_time" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <InputLabel for="meeting_end_time" value="End Time (Optional)" />
                            <select id="meeting_end_time" v-model="meetingForm.end_time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Select Time —</option>
                                <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                            </select>
                            <InputError :message="meetingForm.errors.end_time" />
                        </div>

                        <!-- Target Audience -->
                        <div class="md:col-span-2">
                            <InputLabel for="target_audience" value="Who should attend?" />
                            <select id="target_audience" v-model="meetingForm.target_audience"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                                required>
                                <option value="all">All (Officers & Members)</option>
                                <option value="officers">Officers Only</option>
                                <option value="members">Members Only</option>
                            </select>
                            <InputError :message="meetingForm.errors.target_audience" />
                        </div>

                        <!-- Description / Agenda -->
                        <div class="md:col-span-2">
                            <InputLabel for="meeting_description" value="Agenda / Description (Optional)" />
                            <textarea id="meeting_description" v-model="meetingForm.description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Describe the purpose and agenda of the meeting..." />
                            <InputError :message="meetingForm.errors.description" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
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
    </AdminLayout>
</template>
