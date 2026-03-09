<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import GuidanceCaseFormModal from '@/Components/Admin/GuidanceCaseFormModal.vue';
import GuidanceCaseActionModal from '@/Components/Admin/GuidanceCaseActionModal.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Textarea from '@/Components/Textarea.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import Pagination from '@/Components/Pagination.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, confirmAction, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    cases: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
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
    caseTypes: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
    employees: {
        type: Array,
        default: () => [],
    },
    dashboardStats: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('cases');
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedAppointmentForAction = ref(null);

const approveForm = useForm({
    admin_remarks: '',
});

const rejectForm = useForm({
    admin_remarks: '',
});

const showCaseModal = ref(false);
const showActionModal = ref(false);
const showCaseDetails = ref(false);
const showAppointmentDetails = ref(false);
const selectedCase = ref(null);
const selectedCaseForAction = ref(null);
const selectedAppointment = ref(null);

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const caseType = ref(props.filters.case_type || '');
const assignedStaffId = ref(props.filters.assigned_staff_id || '');

const applyFilters = () => {
    router.get(route('admin.guidance.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        case_type: caseType.value || undefined,
        assigned_staff_id: assignedStaffId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['cases', 'filters', 'caseTypes', 'statuses', 'employees'],
    });
};

let searchDebounce = null;
watch(search, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([status, caseType, assignedStaffId], () => applyFilters());

const openAddModal = () => {
    selectedCase.value = null;
    showCaseModal.value = true;
};

const openEditModal = (caseItem) => {
    selectedCase.value = { ...caseItem };
    showCaseModal.value = true;
};

const closeCaseModal = () => {
    showCaseModal.value = false;
    selectedCase.value = null;
};

const openActionModal = (caseItem) => {
    selectedCaseForAction.value = caseItem;
    showActionModal.value = true;
};

const closeActionModal = () => {
    showActionModal.value = false;
    selectedCaseForAction.value = null;
};

const viewCaseDetails = async (caseItem) => {
    try {
        const response = await axios.get(route('admin.guidance.show', caseItem.guidance_case_id));
        if (response.data.success) {
            selectedCase.value = response.data.case;
            showCaseDetails.value = true;
        }
    } catch (error) {
        console.error('Failed to load case details:', error);
        notify('error', error.response?.data?.message || 'Failed to load case details.');
    }
};

const closeCaseDetails = () => {
    showCaseDetails.value = false;
    selectedCase.value = null;
};

const viewAppointmentDetails = (appointment) => {
    router.visit(route('admin.guidance.appointments.show', appointment.appointment_id));
};

const handleCaseSaved = () => {
    router.reload({ only: ['cases'] });
};

const handleActionSaved = () => {
    router.reload({ only: ['cases'] });
    closeActionModal();
};

const deleteCase = async (caseItem) => {
    confirmAction(
        `Are you sure you want to delete case ${caseItem.case_no}?`,
        'Delete Case',
        async () => {
            try {
                const response = await axios.delete(route('admin.guidance.destroy', caseItem.guidance_case_id));
                if (response.data.success) {
                    notify('success', 'Case deleted successfully.');
                    router.reload({ only: ['cases'] });
                } else {
                    notify('error', response.data.message || 'Failed to delete case.');
                }
            } catch (error) {
                console.error('Failed to delete case:', error);
                notify('error', error.response?.data?.message || 'Failed to delete case.');
            }
        },
        { confirmLabel: 'Delete', cancelLabel: 'Cancel' }
    );
};

const getStatusBadgeClass = (status, statusColor = null) => {
    // If statusColor is provided (for appointments), use it
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

    // Otherwise, derive from status string (for cases)
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        ongoing: 'bg-blue-100 text-blue-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getCaseTypeBadgeClass = (type) => {
    const classes = {
        counseling: 'bg-purple-100 text-purple-800',
        consultation: 'bg-indigo-100 text-indigo-800',
        referral: 'bg-pink-100 text-pink-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
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

const isExporting = ref(false);

const exportPdf = async () => {
    isExporting.value = true;
    try {
        const params = new URLSearchParams();
        if (search.value) params.append('search', search.value);
        if (status.value) params.append('status', status.value);
        if (caseType.value) params.append('case_type', caseType.value);
        if (assignedStaffId.value) params.append('assigned_staff_id', assignedStaffId.value);

        const response = await axios.get(route('admin.guidance.export.pdf') + (params.toString() ? '?' + params.toString() : ''), {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'guidance_cases.pdf');
        document.body.appendChild(link);
        link.click();
        link.parentNode.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export PDF:', error);
        notify('error', 'Failed to generate PDF.');
    } finally {
        isExporting.value = false;
    }
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

    <Head title="Guidance Cases" />

    <AdminLayout>
        <LoadingOverlay :show="isExporting" message="Generating PDF... Please wait." />
        <template #header>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Guidance Unit
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton v-if="activeTab === 'appointments'" @click="activeTab = 'cases'">
                        View Cases
                    </SecondaryButton>
                    <SecondaryButton v-else @click="activeTab = 'appointments'">
                        View Appointments
                    </SecondaryButton>
                    <SecondaryButton v-if="activeTab === 'cases'" @click="exportPdf">
                        Export PDF
                    </SecondaryButton>
                    <PrimaryButton v-if="activeTab === 'cases'" @click="openAddModal">
                        Add Case
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Appointments Tab -->
            <div v-if="activeTab === 'appointments'">
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
                            class="bg-white rounded-lg border border-yellow-200 p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-sm font-semibold text-gray-900">Appointment Request #{{
                                            appointment.appointment_id }}</h4>
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-600">
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
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="appointment_search" class="block text-sm font-medium text-gray-700 mb-1">
                                Search
                            </label>
                            <input id="appointment_search" v-model="appointmentSearch" type="text"
                                placeholder="Student name, concern..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="appointment_status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select id="appointment_status" v-model="appointmentStatus"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                            <label for="appointment_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Type
                            </label>
                            <select id="appointment_type" v-model="appointmentType"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date & Time
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Concern
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Admin Action
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="appointments.data && appointments.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No appointments found.
                                    </td>
                                </tr>
                                <tr v-for="appointment in appointments.data" :key="appointment.appointment_id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="viewAppointmentDetails(appointment)">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-medium">{{ appointment.student_name }}</div>
                                        <div class="text-xs text-gray-500">{{ appointment.student_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>{{ appointment.appointment_date }}</div>
                                        <div class="text-xs">{{ appointment.appointment_time }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                        {{ appointment.appointment_type }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="truncate max-w-xs">{{ appointment.concern }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadgeClass(appointment.status, appointment.status_color)">
                                            {{ appointment.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div v-if="appointment.approver_name && appointment.approved_at"
                                            class="text-green-700">
                                            <div class="font-medium">Approved by:</div>
                                            <div class="text-xs">{{ appointment.approver_name }}</div>
                                            <div class="text-xs text-gray-500">{{ appointment.approved_at }}</div>
                                        </div>
                                        <div v-else-if="appointment.rejector_name && appointment.rejected_at"
                                            class="text-red-700">
                                            <div class="font-medium">Rejected by:</div>
                                            <div class="text-xs">{{ appointment.rejector_name }}</div>
                                            <div class="text-xs text-gray-500">{{ appointment.rejected_at }}</div>
                                        </div>
                                        <div v-else class="text-gray-400 text-xs">
                                            -
                                        </div>
                                        <div v-if="appointment.admin_remarks" class="mt-1 text-xs text-gray-500 italic">
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
                                                class="text-red-600 hover:text-red-900">
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

            <!-- Cases Tab -->
            <div v-if="activeTab === 'cases'">
                <!-- Filters -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                                Search
                            </label>
                            <input id="search" v-model="search" type="text" placeholder="Case number, student name..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select id="status" v-model="status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option v-for="s in statuses" :key="s" :value="s">
                                    {{ s.charAt(0).toUpperCase() + s.slice(1) }}
                                </option>
                            </select>
                        </div>

                        <!-- Case Type -->
                        <div>
                            <label for="case_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Case Type
                            </label>
                            <select id="case_type" v-model="caseType"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option v-for="type in caseTypes" :key="type" :value="type">
                                    {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                                </option>
                            </select>
                        </div>

                        <!-- Assigned Staff -->
                        <div>
                            <label for="assigned_staff_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Assigned Staff
                            </label>
                            <select id="assigned_staff_id" v-model="assignedStaffId"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option v-for="employee in employees" :key="employee.employee_id"
                                    :value="employee.employee_id">
                                    {{ employee.full_name }}
                                </option>
                            </select>
                        </div>
                    </div>


                </div>

                <!-- Cases Table -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Case No
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Section
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Case Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assigned Staff
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Requested At
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Options
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="cases.data.length === 0">
                                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No guidance cases found.
                                    </td>
                                </tr>
                                <tr v-for="caseItem in cases.data" :key="caseItem.guidance_case_id"
                                    class="hover:bg-gray-50 cursor-pointer" @click="viewCaseDetails(caseItem)">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ caseItem.case_no }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div v-if="caseItem.student">
                                            {{ caseItem.student.full_name }}
                                            <div class="text-xs text-gray-500">{{ caseItem.student.student_number }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ caseItem.section?.section_name || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getCaseTypeBadgeClass(caseItem.case_type)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ caseItem.case_type.charAt(0).toUpperCase() + caseItem.case_type.slice(1)
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" @click.stop>
                                        <span :class="getStatusBadgeClass(caseItem.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ caseItem.status.charAt(0).toUpperCase() + caseItem.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ caseItem.assigned_staff?.full_name || 'Unassigned' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ caseItem.requested_at ? new Date(caseItem.requested_at).toLocaleDateString()
                                        : 'N/A'
                                        }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ caseItem.actions_count || 0 }} action(s)
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                                        <button @click.stop="viewCaseDetails(caseItem)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            View
                                        </button>
                                        <button @click.stop="openActionModal(caseItem)"
                                            class="text-blue-600 hover:text-blue-900 mr-4">
                                            Add Action
                                        </button>
                                        <button @click.stop="openEditModal(caseItem)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            Edit
                                        </button>
                                        <button @click.stop="deleteCase(caseItem)"
                                            class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="cases" />
                </div>
            </div>
        </div>

        <!-- Approve Appointment Modal -->
        <div v-if="showApproveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Approve Appointment Request</h3>
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
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Appointment Request</h3>
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

        <!-- Modals -->
        <GuidanceCaseFormModal :show="showCaseModal" :case-item="selectedCase" :employees="employees"
            @close="closeCaseModal" @saved="handleCaseSaved" />

        <GuidanceCaseActionModal :show="showActionModal" :case-item="selectedCaseForAction" @close="closeActionModal"
            @saved="handleActionSaved" />

        <!-- Case Details Modal -->
        <div v-if="showCaseDetails && selectedCase" class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="closeCaseDetails"></div>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Case Details: {{ selectedCase.case_no }}
                            </h3>
                            <button @click="closeCaseDetails" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Student</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ selectedCase.student?.full_name || 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Case Type</label>
                                    <p class="mt-1 text-sm text-gray-900">{{
                                        selectedCase.case_type?.charAt(0).toUpperCase() +
                                        selectedCase.case_type?.slice(1) || 'N/A' }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <p class="text-sm text-gray-900 mb-2">{{
                                        selectedCase.status?.charAt(0).toUpperCase() +
                                        selectedCase.status?.slice(1) || 'N/A' }}</p>
                                    <StatusProgressBar :steps="[
                                        { value: 'pending', label: 'Pending' },
                                        { value: 'ongoing', label: 'Ongoing' },
                                        { value: 'resolved', label: 'Resolved' },
                                        { value: 'closed', label: 'Closed' },
                                    ]" :current-status="selectedCase.status" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Assigned Staff</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ selectedCase.assigned_staff?.full_name ||
                                        'Unassigned' }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Concern</label>
                                <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ selectedCase.concern ||
                                    'N/A' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Actions Timeline</label>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    <div v-for="action in selectedCase.actions" :key="action.action_id"
                                        class="border-l-4 border-indigo-500 pl-4 py-2 bg-gray-50 rounded">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-900">{{ action.note || 'No note' }}</p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    By {{ action.action_by_user?.display_name || 'Unknown' }} on {{ new
                                                    Date(action.created_at).toLocaleString() }}
                                                </p>
                                                <p v-if="action.action_status" class="text-xs mt-1">
                                                    <span :class="getStatusBadgeClass(action.action_status)"
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                                                        {{ action.action_status.charAt(0).toUpperCase() +
                                                        action.action_status.slice(1) }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!selectedCase.actions || selectedCase.actions.length === 0"
                                        class="text-sm text-gray-500">
                                        No actions recorded yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <SecondaryButton @click="closeCaseDetails" class="w-full sm:w-auto">
                            Close
                        </SecondaryButton>
                    </div>
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
    </AdminLayout>
</template>
