<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    enrolledStudents: {
        type: Array,
        default: () => [],
    },
});

// Officer form
const showOfficerModal = ref(false);
const officerForm = useForm({
    student_number: '',
    position: '',
    start_date: new Date().toISOString().split('T')[0],
});

const studentSearch = ref('');
const filteredStudents = computed(() => {
    if (!studentSearch.value) return props.enrolledStudents.slice(0, 20);
    const search = studentSearch.value.toLowerCase();
    return props.enrolledStudents.filter(s => 
        s.full_name.toLowerCase().includes(search) ||
        s.student_number.toLowerCase().includes(search)
    ).slice(0, 20);
});

const selectedStudent = computed(() => {
    if (!officerForm.student_number) return null;
    return props.enrolledStudents.find(s => s.student_number == officerForm.student_number);
});

const openOfficerModal = () => {
    officerForm.reset();
    officerForm.start_date = new Date().toISOString().split('T')[0];
    studentSearch.value = '';
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
    if (confirm(`Are you sure you want to remove ${officer.student_name} as ${officer.position}?`)) {
        router.delete(route('admin.organizations.officers.remove', [props.organization.org_id, officer.officer_id]), {
            preserveScroll: true,
        });
    }
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
const editForm = useForm({
    org_name: props.organization.org_name,
    org_code: props.organization.org_code,
    description: props.organization.description || '',
    type: props.organization.type || '',
    status: props.organization.status,
    adviser_name: props.organization.adviser_name || '',
});

const typeOptions = [
    { value: '', label: 'Select Type' },
    { value: 'Academic', label: 'Academic' },
    { value: 'Cultural', label: 'Cultural' },
    { value: 'Governance', label: 'Governance' },
    { value: 'Special Interest', label: 'Special Interest' },
];

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const openEditModal = () => {
    editForm.org_name = props.organization.org_name;
    editForm.org_code = props.organization.org_code;
    editForm.description = props.organization.description || '';
    editForm.type = props.organization.type || '';
    editForm.status = props.organization.status;
    editForm.adviser_name = props.organization.adviser_name || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    editForm.put(route('admin.organizations.update', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
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
</script>

<template>
    <Head :title="`${organization.org_name} - Organization Details`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        {{ organization.org_name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ organization.org_code }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <SecondaryButton @click="openEditModal">
                        Edit Organization
                    </SecondaryButton>
                    <Link
                        :href="route('admin.organizations.index')"
                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                        ← Back to Organizations
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Organization Info -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Organization Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Type</label>
                        <p class="mt-1 text-sm text-gray-900">{{ organization.type || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <span
                            class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="{
                                'bg-green-100 text-green-800': organization.status === 'active',
                                'bg-gray-100 text-gray-800': organization.status === 'inactive',
                            }"
                        >
                            {{ organization.status }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Adviser</label>
                        <p class="mt-1 text-sm text-gray-900">{{ organization.adviser_name || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Total Officers</label>
                        <p class="mt-1 text-sm text-gray-900">{{ organization.officers?.length || 0 }}</p>
                    </div>
                    <div class="md:col-span-2 lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-500">Description</label>
                        <p class="mt-1 text-sm text-gray-900">{{ organization.description || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Officers Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Officers</h3>
                    <PrimaryButton @click="openOfficerModal">
                        Add Officer
                    </PrimaryButton>
                </div>
                
                <div v-if="organization.officers && organization.officers.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="officer in organization.officers" :key="officer.officer_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ officer.student_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ officer.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                        {{ officer.position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ officer.start_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        @click="removeOfficer(officer)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No officers assigned yet.</p>
            </div>

            <!-- Members Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Members</h3>
                <div v-if="organization.members && organization.members.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Join Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="member in organization.members" :key="member.member_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ member.student_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ member.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ member.join_date }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No members registered.</p>
            </div>

            <!-- Events Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Events</h3>
                    <Link :href="route('admin.organizations.events.index') + '?org_id=' + organization.org_id">
                        <SecondaryButton>
                            Manage Events
                        </SecondaryButton>
                    </Link>
                </div>
                
                <div v-if="organization.events && organization.events.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="event in organization.events" :key="event.event_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ event.event_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ event.event_date }}</div>
                                    <div v-if="event.start_time" class="text-xs text-gray-400">
                                        {{ event.start_time }}
                                        <span v-if="event.end_time"> - {{ event.end_time }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ event.venue || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusColor(event.status)"
                                    >
                                        {{ event.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ event.created_by_name || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No events scheduled.</p>
            </div>
        </div>

        <!-- Add Officer Modal -->
        <Modal :show="showOfficerModal" @close="closeOfficerModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Add Officer</h2>

                <form @submit.prevent="submitOfficer">
                    <!-- Student Search -->
                    <div class="mb-4">
                        <InputLabel for="student_search" value="Search Student" />
                        <TextInput
                            id="student_search"
                            v-model="studentSearch"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Search by name or student number..."
                        />
                    </div>

                    <!-- Student Selection -->
                    <div class="mb-4">
                        <InputLabel for="student_number" value="Select Student" />
                        <select
                            id="student_number"
                            v-model="officerForm.student_number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="{ 'border-red-300': officerForm.errors.student_number }"
                            required
                        >
                            <option value="">Select a student...</option>
                            <option
                                v-for="student in filteredStudents"
                                :key="student.student_number"
                                :value="student.student_number"
                            >
                                {{ student.full_name }} ({{ student.student_number }}) - {{ student.course_code }} {{ student.year_level }}
                            </option>
                        </select>
                        <InputError :message="officerForm.errors.student_number" />
                    </div>

                    <!-- Selected Student Info -->
                    <div v-if="selectedStudent" class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600">
                            <strong>Selected:</strong> {{ selectedStudent.full_name }} ({{ selectedStudent.student_number }})
                        </p>
                    </div>

                    <!-- Position -->
                    <div class="mb-4">
                        <InputLabel for="position" value="Position" />
                        <TextInput
                            id="position"
                            v-model="officerForm.position"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-300': officerForm.errors.position }"
                            placeholder="e.g., President, Vice President, Secretary..."
                            list="position-suggestions"
                            required
                        />
                        <datalist id="position-suggestions">
                            <option v-for="pos in positionSuggestions" :key="pos" :value="pos" />
                        </datalist>
                        <InputError :message="officerForm.errors.position" />
                    </div>

                    <!-- Start Date -->
                    <div class="mb-6">
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput
                            id="start_date"
                            v-model="officerForm.start_date"
                            type="date"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-300': officerForm.errors.start_date }"
                            required
                        />
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
        <Modal :show="showEditModal" @close="closeEditModal" max-width="2xl">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Edit Organization</h2>

                <form @submit.prevent="submitEdit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Organization Name -->
                        <div class="md:col-span-2">
                            <InputLabel for="edit_org_name" value="Organization Name" />
                            <TextInput
                                id="edit_org_name"
                                v-model="editForm.org_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-300': editForm.errors.org_name }"
                                required
                            />
                            <InputError :message="editForm.errors.org_name" />
                        </div>

                        <!-- Organization Code -->
                        <div>
                            <InputLabel for="edit_org_code" value="Organization Code" />
                            <TextInput
                                id="edit_org_code"
                                v-model="editForm.org_code"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-300': editForm.errors.org_code }"
                                required
                            />
                            <InputError :message="editForm.errors.org_code" />
                        </div>

                        <!-- Type -->
                        <div>
                            <InputLabel for="edit_type" value="Type" />
                            <select
                                id="edit_type"
                                v-model="editForm.type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.type" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="edit_status" value="Status" />
                            <select
                                id="edit_status"
                                v-model="editForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.status" />
                        </div>

                        <!-- Adviser Name -->
                        <div>
                            <InputLabel for="edit_adviser_name" value="Adviser Name" />
                            <TextInput
                                id="edit_adviser_name"
                                v-model="editForm.adviser_name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Enter adviser name..."
                            />
                            <InputError :message="editForm.errors.adviser_name" />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <InputLabel for="edit_description" value="Description" />
                            <textarea
                                id="edit_description"
                                v-model="editForm.description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="editForm.errors.description" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <SecondaryButton type="button" @click="closeEditModal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
