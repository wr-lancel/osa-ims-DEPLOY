<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import StaffFormModal from '@/Components/Admin/StaffFormModal.vue';
import RoleFormModal from '@/Components/Admin/RoleFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import { formatLabel } from '@/utils/formatLabel.js';

const { notification, notify, confirmAction, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    employees: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const showStaffModal = ref(false);
const showRoleModal = ref(false);
const selectedEmployee = ref(null);

const search = ref(props.filters.search || '');
const department = ref(props.filters.department || '');
const position = ref(props.filters.position || '');
const roleId = ref(props.filters.role_id || '');
const status = ref(props.filters.status || '');

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const applyFilters = () => {
    router.get(route('admin.staff.index'), {
        search: search.value || undefined,
        department: department.value || undefined,
        position: position.value || undefined,
        role_id: roleId.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

let searchDebounce = null;
watch(search, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => applyFilters(), 350);
});
watch([department, position, roleId, status], () => applyFilters());

const openAddModal = () => {
    selectedEmployee.value = null;
    showStaffModal.value = true;
};

const openEditModal = (employee) => {
    selectedEmployee.value = {
        ...employee,
        role_id: employee.roles && employee.roles.length > 0 ? employee.roles[0].role_id : '',
    };
    showStaffModal.value = true;
};

const closeStaffModal = () => {
    showStaffModal.value = false;
    selectedEmployee.value = null;
};

const openRoleModal = () => {
    showRoleModal.value = true;
};

const closeRoleModal = () => {
    showRoleModal.value = false;
};

const handleStaffSaved = () => {
    router.reload({ only: ['employees', 'departments', 'positions'] });
};

const handleRoleSaved = () => {
    router.reload({ only: ['roles'] });
    // Emit event to refresh roles in StaffFormModal if it's open
    // The roles prop will be updated automatically via Inertia reload
};

const deleteEmployee = async (employee) => {
    confirmAction(
        `Are you sure you want to delete ${employee.full_name}? This will also delete their user account.`,
        'Delete Staff Member',
        async () => {
            try {
                const response = await axios.delete(route('admin.staff.destroy', employee.employee_id));
                if (response.data.success) {
                    notify('success', 'Staff member deleted successfully.');
                    router.reload({ only: ['employees', 'departments', 'positions'] });
                } else {
                    notify('error', response.data.message || 'Failed to delete staff member.');
                }
            } catch (error) {
                console.error('Failed to delete employee:', error);
                notify('error', error.response?.data?.message || 'Failed to delete staff member.');
            }
        },
        { confirmLabel: 'Delete', cancelLabel: 'Cancel' }
    );
};

const flash = computed(() => page.props.flash || {});

const exportPdf = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (department.value) params.append('department', department.value);
    if (position.value) params.append('position', position.value);
    if (roleId.value) params.append('role_id', roleId.value);
    if (status.value) params.append('status', status.value);

    window.location.href = route('admin.staff.export.pdf') + (params.toString() ? '?' + params.toString() : '');
};
</script>

<template>

    <Head title="Manage Staff" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Manage Staff
                </h2>
                <div class="flex flex-wrap gap-2">
                    <SecondaryButton @click="exportPdf">
                        Export PDF
                    </SecondaryButton>
                    <PrimaryButton @click="openAddModal">
                        Add Staff
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Success/Error Flash Messages -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ flash.error }}</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>
                        <input id="search" v-model="search" type="text" placeholder="Employee number or name..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-1">
                            Department
                        </label>
                        <select id="department" v-model="department"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option v-for="dept in departments" :key="dept" :value="dept">
                                {{ dept }}
                            </option>
                        </select>
                    </div>

                    <!-- Position -->
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                            Position
                        </label>
                        <select id="position" v-model="position"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option v-for="pos in positions" :key="pos" :value="pos">
                                {{ pos }}
                            </option>
                        </select>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Role
                        </label>
                        <select id="role_id" v-model="roleId"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                                {{ formatLabel(role.role_name) }}
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select id="status" v-model="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>


            </div>

            <!-- Employees Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Employee ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Department
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Position
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="employees.data.length === 0">
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No staff members found.
                                </td>
                            </tr>
                            <tr v-for="employee in employees.data" :key="employee.employee_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ employee.employee_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ employee.full_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.email || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.department || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ employee.position || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span v-if="employee.roles && employee.roles.length > 0"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ employee.roles.map(r => formatLabel(r.role_name)).join(', ') }}
                                    </span>
                                    <span v-else class="text-gray-400">No role</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="employee.status === 'active' ? 'text-green-600' : 'text-red-600'"
                                        class="font-medium">
                                        {{ formatLabel(employee.status) || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openEditModal(employee)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4">
                                        Edit
                                    </button>
                                    <button @click="deleteEmployee(employee)" class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :data="employees" />
            </div>
        </div>

        <!-- Modals -->
        <StaffFormModal :show="showStaffModal" :employee="selectedEmployee" :roles="roles" @close="closeStaffModal"
            @saved="handleStaffSaved" />

        <RoleFormModal :show="showRoleModal" @close="closeRoleModal" @saved="handleRoleSaved" />

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
