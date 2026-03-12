<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Textarea from '@/Components/Textarea.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    appointments: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    pendingAppointments: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    dashboardStats: {
        type: Array,
        default: () => [],
    },
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedAppointmentForAction = ref(null);

const approveForm = useForm({
    admin_remarks: '',
});

const rejectForm = useForm({
    admin_remarks: '',
});

const getStatusBadgeClass = (status, statusColor = null) => {
    if (statusColor) {
        const colorMap = {
            'yellow': 'bg-yellow-100 text-yellow-800',
            'green': 'bg-green-100 text-green-800',
            'red': 'bg-red-100 text-red-800',
            'blue': 'bg-blue-100 text-blue-800',
            'gray': 'bg-gray-100 text-gray-800',
        };
        return colorMap[statusColor] || 'bg-gray-100 text-gray-800';
    }

    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        ongoing: 'bg-blue-100 text-blue-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const appointmentSearch = ref(props.filters.appointment_search || '');
const appointmentStatus = ref(props.filters.appointment_status || '');
const appointmentType = ref(props.filters.appointment_type || '');

const applyAppointmentFilters = () => {
    router.get(route('admin.guidance.index'), {
        appointment_search: appointmentSearch.value || undefined,
        appointment_status: appointmentStatus.value || undefined,
        appointment_type: appointmentType.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
    });
};

let appointmentSearchDebounce = null;
watch(appointmentSearch, () => {
    if (appointmentSearchDebounce) clearTimeout(appointmentSearchDebounce);
    appointmentSearchDebounce = setTimeout(() => applyAppointmentFilters(), 350);
});
watch([appointmentStatus, appointmentType], () => applyAppointmentFilters());

const viewAppointmentDetails = (appointment) => {
    router.visit(route('admin.guidance.appointments.show', appointment.appointment_id));
};

const openApproveModal = (appointment) => {
    selectedAppointmentForAction.value = appointment;
    approveForm.reset();
    showApproveModal.value = true;
};

const openRejectModal = (appointment) => {
    selectedAppointmentForAction.value = appointment;
    rejectForm.reset();
    showRejectModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
    selectedAppointmentForAction.value = null;
    approveForm.reset();
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    selectedAppointmentForAction.value = null;
    rejectForm.reset();
};

const approveAppointment = () => {
    if (!selectedAppointmentForAction.value) return;

    approveForm.post(route('admin.guidance.appointments.approve', selectedAppointmentForAction.value.appointment_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeApproveModal();
            router.reload({ only: ['appointments', 'pendingAppointments', 'dashboardStats'] });
        },
    });
};

const rejectAppointment = () => {
    if (!selectedAppointmentForAction.value) return;

    rejectForm.post(route('admin.guidance.appointments.reject', selectedAppointmentForAction.value.appointment_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
            router.reload({ only: ['appointments', 'pendingAppointments', 'dashboardStats'] });
        },
    });
};
</script>

<template>
    <Head title="Guidance Unit" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Guidance Unit
                </h2>
                <div class="flex space-x-3"></div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Appointments Tab -->
            <div>
                <!-- Pending Appointments Section -->
                <div v-if="pendingAppointments && pendingAppointments.length > 0"
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-yellow-900">
                            Pending Appointment Requests ({{ pendingAppointments.length }})
                        </h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="appointment in pendingAppointments" :key="appointment.appointment_id"
                            class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Appointment Request #{{
                                            appointment.appointment_id }}</h4>
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <div>
                                            <span class="font-medium">Student:</span> {{ appointment.student_name }} ({{
                                            appointment.student_id }})
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span> {{ appointment.appointment_date }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Time:</span> {{ appointment.appointment_time }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Type:</span> <span class="capitalize">{{
                                                appointment.appointment_type }}</span>
                                        </div>
                                        <div class="md:col-span-4">
                                            <span class="font-medium">Concern:</span> {{ appointment.concern }}
                                        </div>
                                        <div v-if="appointment.approver_name && appointment.approved_at"
                                            class="md:col-span-4 text-green-700">
                                            <span class="font-medium">Approved by:</span> {{ appointment.approver_name
                                            }} on {{
                                            appointment.approved_at }}
                                        </div>
                                        <div v-if="appointment.rejector_name && appointment.rejected_at"
                                            class="md:col-span-4 text-red-700">
                                            <span class="font-medium">Rejected by:</span> {{ appointment.rejector_name
                                            }} on {{
                                            appointment.rejected_at }}
                                        </div>
                                        <div v-if="appointment.admin_remarks" class="md:col-span-4">
                                            <span class="font-medium">Admin Remarks:</span> {{ appointment.admin_remarks
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <PrimaryButton @click="openApproveModal(appointment)"
                                        class="bg-green-600 hover:bg-green-700">
                                        Approve
                                    </PrimaryButton>
                                    <button @click="openRejectModal(appointment)"
                                        class="inline-flex items-center rounded-md border border-red-600 bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appointment Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="appointment_search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Search
                            </label>
                            <input id="appointment_search" v-model="appointmentSearch" type="text"
                                placeholder="Student name, concern..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="appointment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Status
                            </label>
                            <select id="appointment_status" v-model="appointmentStatus"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="appointment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Type
                            </label>
                            <select id="appointment_type" v-model="appointmentType"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">All Types</option>
                                <option value="counseling">Counseling</option>
                                <option value="consultation">Consultation</option>
                                <option value="referral">Referral</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mt-4">
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Date & Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Concern
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Admin Action
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="appointments.data && appointments.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No appointments found.
                                    </td>
                                </tr>
                                <tr v-for="appointment in appointments.data" :key="appointment.appointment_id"
                                    class="hover:bg-gray-50 dark:bg-gray-900 cursor-pointer"
                                    @click="viewAppointmentDetails(appointment)">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ appointment.student_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.student_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        <div>{{ appointment.appointment_date }}</div>
                                        <div class="text-xs">{{ appointment.appointment_time }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 capitalize">
                                        {{ appointment.appointment_type }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        <div class="truncate max-w-xs">{{ appointment.concern }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadgeClass(appointment.status, appointment.status_color)">
                                            {{ appointment.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <div v-if="appointment.approver_name && appointment.approved_at"
                                            class="text-green-700">
                                            <div class="font-medium">Approved by:</div>
                                            <div class="text-xs">{{ appointment.approver_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.approved_at }}</div>
                                        </div>
                                        <div v-else-if="appointment.rejector_name && appointment.rejected_at"
                                            class="text-red-700">
                                            <div class="font-medium">Rejected by:</div>
                                            <div class="text-xs">{{ appointment.rejector_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ appointment.rejected_at }}</div>
                                        </div>
                                        <div v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-xs">
                                            -
                                        </div>
                                        <div v-if="appointment.admin_remarks" class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 italic">
                                            "{{ appointment.admin_remarks }}"
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                                        <div class="flex justify-end space-x-2">
                                            <button
                                                v-if="appointment.status === 'Pending' || appointment.status === 'pending'"
                                                @click.stop="openApproveModal(appointment)"
                                                class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                            <button
                                                v-if="appointment.status === 'Pending' || appointment.status === 'pending'"
                                                @click.stop="openRejectModal(appointment)"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900">
                                                Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="appointments" />
                </div>
            </div>

            <!-- Approve Appointment Modal -->
        <div v-if="showApproveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approve Appointment Request</h3>
                    <form @submit.prevent="approveAppointment">
                        <div class="mb-4">
                            <InputLabel for="approve_remarks" value="Admin Remarks (Optional)" />
                            <Textarea id="approve_remarks" v-model="approveForm.admin_remarks" class="mt-1 block w-full"
                                rows="3" placeholder="Any additional notes or instructions..." />
                            <InputError :message="approveForm.errors.admin_remarks" class="mt-2" />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeApproveModal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" :disabled="approveForm.processing">
                                {{ approveForm.processing ? 'Approving...' : 'Approve' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Appointment Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Reject Appointment Request</h3>
                    <form @submit.prevent="rejectAppointment">
                        <div class="mb-4">
                            <InputLabel for="reject_remarks" value="Rejection Reason *" />
                            <Textarea id="reject_remarks" v-model="rejectForm.admin_remarks" class="mt-1 block w-full"
                                rows="4" placeholder="Please provide a reason for rejection..." required />
                            <InputError :message="rejectForm.errors.admin_remarks" class="mt-2" />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeRejectModal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" :disabled="rejectForm.processing"
                                class="bg-red-600 hover:bg-red-700">
                                {{ rejectForm.processing ? 'Rejecting...' : 'Reject' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
        </div>
    </AdminLayout>
</template>
