<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

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
const editForm = useForm({
    description: props.organization.description || '',
    adviser_name: props.organization.adviser_name || '',
});

const openEditModal = () => {
    editForm.description = props.organization.description || '';
    editForm.adviser_name = props.organization.adviser_name || '';
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    editForm.put(route('student.organizations.update', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

// Create Event Modal
const showEventModal = ref(false);
const eventForm = useForm({
    event_name: '',
    description: '',
    event_date: new Date().toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    venue: '',
    status: 'Planning',
});

const statusOptions = [
    { value: 'Planning', label: 'Planning' },
    { value: 'Upcoming', label: 'Upcoming' },
];

const openEventModal = () => {
    eventForm.reset();
    eventForm.event_date = new Date().toISOString().split('T')[0];
    eventForm.status = 'Planning';
    showEventModal.value = true;
};

const closeEventModal = () => {
    showEventModal.value = false;
    eventForm.reset();
};

const submitEvent = () => {
    eventForm.post(route('student.organizations.events.store', props.organization.org_id), {
        preserveScroll: true,
        onSuccess: () => closeEventModal(),
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
    <Head :title="`${organization.org_name} - Organization`" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        {{ organization.org_name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ organization.org_code }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span
                        v-if="isOfficer"
                        class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-indigo-100 text-indigo-800"
                    >
                        {{ officerRole }}
                    </span>
                    <Link
                        :href="route('student.organizations.index')"
                        class="text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                        ← Back to Organizations
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Officer Actions Banner -->
            <div v-if="isOfficer" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        <div>
                            <p class="font-medium text-indigo-900">You are an officer of this organization</p>
                            <p class="text-sm text-indigo-700">You can edit organization details and create events.</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <SecondaryButton @click="openEditModal">
                            Edit Details
                        </SecondaryButton>
                        <PrimaryButton @click="openEventModal">
                            Create Event
                        </PrimaryButton>
                    </div>
                </div>
            </div>

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
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Officers</h3>
                <div v-if="organization.officers && organization.officers.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Since</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="officer in organization.officers" :key="officer.officer_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ officer.student_name }}</div>
                                    <div class="text-xs text-gray-500">{{ officer.student_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                        {{ officer.position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ officer.start_date }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No officers assigned.</p>
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
                    <PrimaryButton v-if="isOfficer" @click="openEventModal">
                        Create Event
                    </PrimaryButton>
                </div>
                
                <div v-if="organization.events && organization.events.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="event in organization.events" :key="event.event_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ event.event_name }}</div>
                                    <div v-if="event.description" class="text-xs text-gray-500 line-clamp-1">
                                        {{ event.description }}
                                    </div>
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
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">No events scheduled.</p>
            </div>
        </div>

        <!-- Edit Organization Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Edit Organization</h2>

                <form @submit.prevent="submitEdit">
                    <!-- Description -->
                    <div class="mb-4">
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="editForm.description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <!-- Adviser Name -->
                    <div class="mb-6">
                        <InputLabel for="adviser_name" value="Adviser Name" />
                        <TextInput
                            id="adviser_name"
                            v-model="editForm.adviser_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter adviser name..."
                        />
                        <InputError :message="editForm.errors.adviser_name" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
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

        <!-- Create Event Modal -->
        <Modal :show="showEventModal" @close="closeEventModal">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Create Event</h2>

                <form @submit.prevent="submitEvent">
                    <!-- Event Name -->
                    <div class="mb-4">
                        <InputLabel for="event_name" value="Event Name" />
                        <TextInput
                            id="event_name"
                            v-model="eventForm.event_name"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-300': eventForm.errors.event_name }"
                            required
                        />
                        <InputError :message="eventForm.errors.event_name" />
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <InputLabel for="event_description" value="Description" />
                        <textarea
                            id="event_description"
                            v-model="eventForm.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError :message="eventForm.errors.description" />
                    </div>

                    <!-- Event Date -->
                    <div class="mb-4">
                        <InputLabel for="event_date" value="Event Date" />
                        <TextInput
                            id="event_date"
                            v-model="eventForm.event_date"
                            type="date"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-300': eventForm.errors.event_date }"
                            required
                        />
                        <InputError :message="eventForm.errors.event_date" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Start Time -->
                        <div>
                            <InputLabel for="start_time" value="Start Time" />
                            <TextInput
                                id="start_time"
                                v-model="eventForm.start_time"
                                type="time"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="eventForm.errors.start_time" />
                        </div>

                        <!-- End Time -->
                        <div>
                            <InputLabel for="end_time" value="End Time" />
                            <TextInput
                                id="end_time"
                                v-model="eventForm.end_time"
                                type="time"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="eventForm.errors.end_time" />
                        </div>
                    </div>

                    <!-- Venue -->
                    <div class="mb-4">
                        <InputLabel for="venue" value="Venue" />
                        <TextInput
                            id="venue"
                            v-model="eventForm.venue"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Gymnasium, Auditorium..."
                        />
                        <InputError :message="eventForm.errors.venue" />
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="eventForm.status"
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
            </div>
        </Modal>
    </StudentLayout>
</template>

