<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import SportsBorrowingFormModal from '@/Components/Admin/SportsBorrowingFormModal.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Textarea from '@/Components/Textarea.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    borrowings: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    students: {
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
    pendingBorrowings: {
        type: Array,
        default: () => [],
    },
});


const isProcessing = ref(false);
const showModal = ref(false);
const selectedBorrowing = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedBorrowingForAction = ref(null);

const approveForm = useForm({
    status: 'approved',
    admin_remarks: '',
});

const rejectForm = useForm({
    admin_remarks: '',
});


const borrowingSearch = ref(props.filters.borrowing_search || '');
const borrowingStatus = ref(props.filters.borrowing_status || '');

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'borrowed', label: 'Borrowed' },
    { value: 'returned', label: 'Returned' },
    { value: 'overdue', label: 'Overdue' },
];

const applyFilters = () => {
    const params = {};
    if (borrowingSearch.value) params.borrowing_search = borrowingSearch.value;
    if (borrowingStatus.value) params.borrowing_status = borrowingStatus.value;

    router.get(route('admin.sports.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        only: ['borrowings', 'filters', 'dashboardStats', 'pendingBorrowings', 'students', 'employees'],
    });
};

let borrowingSearchDebounce = null;
watch(borrowingSearch, () => {
    if (borrowingSearchDebounce) clearTimeout(borrowingSearchDebounce);
    borrowingSearchDebounce = setTimeout(() => applyFilters(), 350);
});
watch(borrowingStatus, () => applyFilters());

const openAddModal = () => {
    selectedBorrowing.value = null;
    showModal.value = true;
};

const openEditModal = (borrowing) => {
    selectedBorrowing.value = borrowing;
    showModal.value = true;
};

const markAsReturned = (borrowing) => {
    isProcessing.value = true;
    router.put(route('admin.sports.borrowings.update', borrowing.borrowing_id), {
        ...borrowing,
        status: 'returned',
        return_date: new Date().toISOString().split('T')[0],
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { isProcessing.value = false; },
    });
};

const closeModal = () => {
    showModal.value = false;
    selectedBorrowing.value = null;
};

const handleSaved = () => {
    closeModal();
    router.reload({ only: ['borrowings', 'dashboardStats', 'pendingBorrowings'] });
};

const openApproveModal = (borrowing) => {
    selectedBorrowingForAction.value = borrowing;
    approveForm.reset();
    approveForm.status = 'approved';
    showApproveModal.value = true;
};

const openRejectModal = (borrowing) => {
    selectedBorrowingForAction.value = borrowing;
    rejectForm.reset();
    showRejectModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
    selectedBorrowingForAction.value = null;
    approveForm.reset();
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    selectedBorrowingForAction.value = null;
    rejectForm.reset();
};

const approveBorrowing = () => {
    if (!selectedBorrowingForAction.value) return;

    approveForm.post(route('admin.sports.borrowings.approve', selectedBorrowingForAction.value.borrowing_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeApproveModal();
            router.reload({ only: ['borrowings', 'dashboardStats', 'pendingBorrowings'] });
        },
    });
};

const rejectBorrowing = () => {
    if (!selectedBorrowingForAction.value) return;

    rejectForm.post(route('admin.sports.borrowings.reject', selectedBorrowingForAction.value.borrowing_id), {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
            router.reload({ only: ['borrowings', 'dashboardStats', 'pendingBorrowings'] });
        },
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

const goToBorrowingDetail = (borrowing) => {
    router.visit(route('admin.sports.borrowings.show', borrowing.borrowing_id));
};

import { useNotification } from '@/composables/useNotification';

const { notify } = useNotification();
const isExporting = ref(false);

const exportPdf = async () => {
    isExporting.value = true;
    try {
        const params = new URLSearchParams();
        if (borrowingSearch.value) params.append('borrowing_search', borrowingSearch.value);
        if (borrowingStatus.value) params.append('borrowing_status', borrowingStatus.value);

        const response = await axios.get(route('admin.sports.borrowings.export.pdf') + (params.toString() ? '?' + params.toString() : ''), {
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'borrowings_log.pdf');
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
</script>

<template>

    <Head title="Sports Unit" />

    <AdminLayout>
        <LoadingOverlay :show="isProcessing || isExporting" :message="isExporting ? 'Generating PDF... Please wait.' : 'Processing... Please wait.'" />
        <template #header>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Sports Unit
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton @click="router.visit(route('admin.sports.athletes'))">
                        View Athletes
                    </SecondaryButton>
                    <div class="flex flex-col items-start gap-0.5">
                        <SecondaryButton @click="exportPdf">
                            Export PDF
                        </SecondaryButton>
                        <span class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 px-1">Uses current filters</span>
                    </div>
                    <PrimaryButton @click="openAddModal">
                        New Borrowing
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Dashboard Cards -->
            <DashboardCards :cards="dashboardStats" />

            <!-- Equipment Borrowing Section -->
            <div>
                <!-- Pending Requests Section -->
                <div v-if="pendingBorrowings && pendingBorrowings.length > 0"
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-yellow-900">
                            Pending Requests ({{ pendingBorrowings.length }})
                        </h3>
                    </div>
                    <div class="space-y-3">
                        <div v-for="borrowing in pendingBorrowings" :key="borrowing.borrowing_id"
                            class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 p-4 cursor-pointer hover:border-yellow-300 transition-colors"
                            @click="goToBorrowingDetail(borrowing)">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ borrowing.item_name }}</h4>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadgeClass(borrowing.status, borrowing.status_color || 'yellow')">
                                            {{ borrowing.status }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <div>
                                            <span class="font-medium">Borrower:</span> {{ borrowing.borrower_name }} ({{
                                            borrowing.borrower_id }})
                                        </div>
                                        <div>
                                            <span class="font-medium">Borrow Date:</span> {{ borrowing.borrow_date }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Expected Return:</span> {{
                                            borrowing.expected_return_date
                                            }}
                                        </div>
                                        <div v-if="borrowing.description" class="md:col-span-4">
                                            <span class="font-medium">Description:</span> {{ borrowing.description }}
                                        </div>
                                        <div v-if="borrowing.approver_name && borrowing.approved_at"
                                            class="md:col-span-4 text-green-700">
                                            <span class="font-medium">Approved by:</span> {{ borrowing.approver_name }}
                                            on {{
                                            borrowing.approved_at }}
                                        </div>
                                        <div v-if="borrowing.rejector_name && borrowing.rejected_at"
                                            class="md:col-span-4 text-red-700">
                                            <span class="font-medium">Rejected by:</span> {{ borrowing.rejector_name }}
                                            on {{
                                            borrowing.rejected_at }}
                                        </div>
                                        <div v-if="borrowing.admin_remarks" class="md:col-span-4">
                                            <span class="font-medium">Admin Remarks:</span> {{ borrowing.admin_remarks
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 ml-4" @click.stop>
                                    <PrimaryButton @click.stop="openApproveModal(borrowing)"
                                        class="bg-green-600 hover:bg-green-700">
                                        Approve
                                    </PrimaryButton>
                                    <button @click.stop="openRejectModal(borrowing)"
                                        class="inline-flex items-center rounded-md border border-red-600 bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="borrowing_search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Search
                            </label>
                            <input id="borrowing_search" v-model="borrowingSearch" type="text"
                                placeholder="Equipment, student, or employee..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="borrowing_status" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                Status
                            </label>
                            <select id="borrowing_status" v-model="borrowingStatus"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>


                    </div>
                </div>

                <!-- Borrowings Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Borrower
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Equipment
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Borrow Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Return Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Admin Action
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="borrowings.data && borrowings.data.length === 0">
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No borrowing records found.
                                    </td>
                                </tr>
                                <tr v-for="borrowing in borrowings.data" :key="borrowing.borrowing_id"
                                    class="hover:bg-gray-50 dark:bg-gray-900 cursor-pointer"
                                    :class="{ 'bg-red-50': borrowing.is_overdue }"
                                    @click="goToBorrowingDetail(borrowing)">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <div>
                                            <div class="font-medium">{{ borrowing.borrower_name }}</div>
                                            <div class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-xs">{{ borrowing.borrower_id }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ borrowing.item_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ borrowing.borrow_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ borrowing.expected_return_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ borrowing.return_date || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)">
                                            {{ borrowing.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <div v-if="borrowing.approver_name && borrowing.approved_at"
                                            class="text-green-700">
                                            <div class="font-medium">Approved by:</div>
                                            <div class="text-xs">{{ borrowing.approver_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ borrowing.approved_at }}</div>
                                        </div>
                                        <div v-else-if="borrowing.rejector_name && borrowing.rejected_at"
                                            class="text-red-700">
                                            <div class="font-medium">Rejected by:</div>
                                            <div class="text-xs">{{ borrowing.rejector_name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ borrowing.rejected_at }}</div>
                                        </div>
                                        <div v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-xs">
                                            -
                                        </div>
                                        <div v-if="borrowing.admin_remarks" class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 italic">
                                            "{{ borrowing.admin_remarks }}"
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                                        <div class="flex justify-end space-x-2">
                                            <button
                                                v-if="borrowing.status === 'pending'"
                                                @click.stop="openApproveModal(borrowing)"
                                                class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                            <button
                                                v-if="borrowing.status === 'pending'"
                                                @click.stop="openRejectModal(borrowing)"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900">
                                                Reject
                                            </button>
                                            <button
                                                v-if="borrowing.status === 'Borrowed' || borrowing.status === 'borrowed'"
                                                @click.stop="markAsReturned(borrowing)"
                                                class="text-green-600 hover:text-green-900">
                                                Mark Returned
                                            </button>
                                            <button @click.stop="openEditModal(borrowing)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="borrowings" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <SportsBorrowingFormModal :show="showModal" :borrowing="selectedBorrowing" :students="students"
            :employees="employees" @close="closeModal" @saved="handleSaved" />

        <!-- Approve Modal -->
        <div v-if="showApproveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approve Borrowing Request</h3>
                    <form @submit.prevent="approveBorrowing">
                        <div class="mb-4">
                            <InputLabel for="approve_status" value="Status" />
                            <select id="approve_status" v-model="approveForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                <option value="approved">Approved</option>
                                <option value="borrowed">Borrowed (Equipment Ready)</option>
                            </select>
                            <InputError :message="approveForm.errors.status" class="mt-2" />
                        </div>
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

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Reject Borrowing Request</h3>
                    <form @submit.prevent="rejectBorrowing">
                        <div class="mb-4">
                            <InputLabel for="reject_remarks" value="Rejection Reason *" />
                            <Textarea id="reject_remarks" v-model="rejectForm.admin_remarks" class="mt-1 block w-full"
                                rows="4" placeholder="Please provide a reason for rejection (minimum 10 characters)..."
                                required />
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
    </AdminLayout>
</template>
