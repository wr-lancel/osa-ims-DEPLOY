<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    appointments: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    appointment_date: new Date().toISOString().split('T')[0],
    appointment_time: '',
    concern: '',
    appointment_type: 'consultation',
    notes: '',
});

const statusFilter = ref(props.filters.status || '');

const submitForm = () => {
    form.post(route('student.guidance.appointments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.appointment_date = new Date().toISOString().split('T')[0];
            form.appointment_type = 'consultation';
        },
    });
};

const filterByStatus = (status) => {
    statusFilter.value = status;
    router.get(route('student.guidance.index'), { status: status || null }, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
    });
};

const getStatusBadgeClass = (status, statusColor) => {
    const colorMap = {
        'yellow': 'bg-yellow-100 text-yellow-800',
        'green': 'bg-green-100 text-green-800',
        'red': 'bg-red-100 text-red-800',
        'blue': 'bg-blue-100 text-blue-800',
        'gray': 'bg-gray-100 text-gray-800',
    };
    return colorMap[statusColor] || 'bg-gray-100 text-gray-800';
};

const goToDetail = (appointment) => {
    router.visit(route('student.guidance.appointments.show', appointment.appointment_id));
};
</script>

<template>
    <Head title="Guidance Unit - Appointments" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Guidance Unit - Appointments
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ $page.props.flash.success }}</p>
            </div>

            <!-- Error Message -->
            <div v-if="$page.props.flash?.error || form.errors.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ $page.props.flash?.error || form.errors.error }}</p>
            </div>

            <!-- Appointment Request Form -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Appointment</h3>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="appointment_date" value="Appointment Date *" />
                                <TextInput
                                    id="appointment_date"
                                    v-model="form.appointment_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    :min="new Date().toISOString().split('T')[0]"
                                    required
                                />
                                <InputError :message="form.errors.appointment_date" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="appointment_time" value="Appointment Time *" />
                                <TextInput
                                    id="appointment_time"
                                    v-model="form.appointment_time"
                                    type="time"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.appointment_time" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="appointment_type" value="Appointment Type *" />
                            <select
                                id="appointment_type"
                                v-model="form.appointment_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="consultation">Consultation</option>
                                <option value="counseling">Counseling</option>
                                <option value="referral">Referral</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError :message="form.errors.appointment_type" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="concern" value="Concern *" />
                            <Textarea
                                id="concern"
                                v-model="form.concern"
                                class="mt-1 block w-full"
                                rows="4"
                                placeholder="Please describe your concern or reason for the appointment..."
                                required
                            />
                            <InputError :message="form.errors.concern" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="notes" value="Additional Notes" />
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                                placeholder="Any additional information or special requests..."
                            />
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Appointment History -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">My Appointment History</h3>
                        
                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label for="status_filter" class="text-sm text-gray-700">Filter:</label>
                            <select
                                id="status_filter"
                                v-model="statusFilter"
                                @change="filterByStatus(statusFilter)"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <!-- Appointments Table -->
                    <div v-if="appointments.data && appointments.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date & Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Concern
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="appointment in appointments.data"
                                    :key="appointment.appointment_id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="goToDetail(appointment)"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ appointment.appointment_date }}</div>
                                        <div class="text-sm text-gray-500">{{ appointment.appointment_time }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 capitalize">{{ appointment.appointment_type }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 truncate max-w-xs">{{ appointment.concern }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadgeClass(appointment.status, appointment.status_color)"
                                        >
                                            {{ appointment.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" @click.stop>
                                        <Link
                                            :href="route('student.guidance.appointments.show', appointment.appointment_id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Details
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <Pagination :data="appointments" />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No appointments found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by submitting an appointment request above.</p>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
