<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AcademicCalendarFormModal from '@/Components/Admin/AcademicCalendarFormModal.vue';
import CourseFormModal from '@/Components/Admin/CourseFormModal.vue';

import RoleFormModal from '@/Components/Admin/RoleFormModal.vue';
import Modal from '@/Components/Modal.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import Pagination from '@/Components/Pagination.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import { formatLabel } from '@/utils/formatLabel.js';
import axios from 'axios';

const { notification, notify, confirmAction, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    academicCalendars: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    courses: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },

    disciplineWorkflowSteps: {
        type: Array,
        default: () => [],
    },
    disciplineViolationTypes: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    lookupValues: {
        type: Object,
        default: () => ({}),
    },
});

// Tab management
const activeTab = ref('calendars');

const tabs = [
    { id: 'calendars', label: 'Academic Calendars', count: computed(() => props.academicCalendars.total ?? props.academicCalendars.data?.length ?? 0) },
    { id: 'courses', label: 'Courses', count: computed(() => props.courses.total ?? props.courses.data?.length ?? 0) },

    { id: 'roles', label: 'Roles', count: computed(() => props.roles.length) },
    { id: 'discipline-workflow', label: 'Discipline Workflow', count: computed(() => props.disciplineWorkflowSteps.length) },
    { id: 'violation-types', label: 'Violation Types', count: computed(() => props.disciplineViolationTypes.length) },
    { id: 'lookup-values', label: 'Lookup Values', count: computed(() => Object.keys(props.lookupValues).length) },
];

// Academic Calendar Modal
const showCalendarModal = ref(false);
const selectedCalendar = ref(null);

const openCalendarModal = (calendar = null) => {
    selectedCalendar.value = calendar;
    showCalendarModal.value = true;
};

const closeCalendarModal = () => {
    showCalendarModal.value = false;
    selectedCalendar.value = null;
    router.reload({ only: ['academicCalendars'] });
};

// Course Modal
const showCourseModal = ref(false);
const selectedCourse = ref(null);

const openCourseModal = (course = null) => {
    selectedCourse.value = course;
    showCourseModal.value = true;
};

const closeCourseModal = () => {
    showCourseModal.value = false;
    selectedCourse.value = null;
    router.reload({ only: ['courses'] });
};

// Status badge colors
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'upcoming': return 'bg-blue-100 text-blue-800';
        case 'completed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};



// Add button action based on active tab
const handleAddNew = () => {
    switch (activeTab.value) {
        case 'calendars':
            openCalendarModal();
            break;
        case 'courses':
            openCourseModal();
            break;
        case 'discipline-workflow':
            showAddStepForm.value = true;
            break;
        case 'violation-types':
            showAddTypeForm.value = true;
            break;
        case 'roles':
            openRoleModal();
            break;
        case 'lookup-values':
            // No global add — each card has its own add input
            break;
    }
};

// ──────────────────────────────────────────────
// Discipline Workflow Step Management
// ──────────────────────────────────────────────
const stepProcessing = ref(false);
const stepErrors = ref({});

// Flash errors from backend (for delete-blocked)
const flashErrors = computed(() => usePage().props?.errors ?? {});

// ── Add Step ──
const showAddStepForm = ref(false);
const newStep = ref({ name: '', description: '', is_terminal: false });

const addStep = () => {
    stepProcessing.value = true;
    stepErrors.value = {};

    router.post(route('admin.settings.discipline-steps.store'), {
        name: newStep.value.name,
        description: newStep.value.description,
        is_terminal: newStep.value.is_terminal,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newStep.value = { name: '', description: '', is_terminal: false };
            showAddStepForm.value = false;
        },
        onError: (errors) => {
            stepErrors.value = errors;
        },
        onFinish: () => {
            stepProcessing.value = false;
        },
    });
};

// ── Edit Step ──
const editingStepId = ref(null);
const editForm = ref({ name: '', description: '', is_terminal: false });

const startEdit = (step) => {
    editingStepId.value = step.id;
    editForm.value = { name: step.name, description: step.description || '', is_terminal: step.is_terminal };
    stepErrors.value = {};
};

const cancelEdit = () => {
    editingStepId.value = null;
    stepErrors.value = {};
};

const saveEdit = (stepId) => {
    stepProcessing.value = true;
    stepErrors.value = {};

    router.put(route('admin.settings.discipline-steps.update', stepId), {
        name: editForm.value.name,
        description: editForm.value.description,
        is_terminal: editForm.value.is_terminal,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingStepId.value = null;
        },
        onError: (errors) => {
            stepErrors.value = errors;
        },
        onFinish: () => {
            stepProcessing.value = false;
        },
    });
};

// ── Delete Step ──
const confirmingDeleteId = ref(null);

const confirmDelete = (step) => {
    confirmingDeleteId.value = step.id;
};

const cancelDelete = () => {
    confirmingDeleteId.value = null;
};

const deleteStep = (stepId) => {
    stepProcessing.value = true;
    router.delete(route('admin.settings.discipline-steps.destroy', stepId), {
        preserveScroll: true,
        onFinish: () => {
            stepProcessing.value = false;
            confirmingDeleteId.value = null;
        },
    });
};

// ── Drag-to-Reorder ──
const dragIndex = ref(null);
const dragOverIndex = ref(null);

const onDragStart = (index) => {
    dragIndex.value = index;
};

const onDragOver = (e, index) => {
    e.preventDefault();
    dragOverIndex.value = index;
};

const onDragEnd = () => {
    if (dragIndex.value === null || dragOverIndex.value === null || dragIndex.value === dragOverIndex.value) {
        dragIndex.value = null;
        dragOverIndex.value = null;
        return;
    }

    // Build new order
    const items = [...props.disciplineWorkflowSteps];
    const [moved] = items.splice(dragIndex.value, 1);
    items.splice(dragOverIndex.value, 0, moved);

    const reordered = items.map((item, idx) => ({ id: item.id, sort_order: idx + 1 }));

    dragIndex.value = null;
    dragOverIndex.value = null;

    router.put(route('admin.settings.discipline-steps.reorder'), { steps: reordered }, {
        preserveScroll: true,
    });
};

// Preview steps for progress bar
const previewSteps = computed(() =>
    props.disciplineWorkflowSteps.map(s => ({ value: s.name, label: s.name }))
);
const previewTerminal = computed(() =>
    props.disciplineWorkflowSteps.filter(s => s.is_terminal).map(s => s.name)
);

// ──────────────────────────────────────────────
// Violation Type Management
// ──────────────────────────────────────────────
const typeProcessing = ref(false);
const typeErrors = ref({});

const severities = computed(() => props.lookupValues?.violation_severities || ['Minor', 'Moderate', 'Major']);
const severityColors = {
    Minor: { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-700', badge: 'bg-green-100 text-green-800' },
    Moderate: { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-700', badge: 'bg-yellow-100 text-yellow-800' },
    Major: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badge: 'bg-red-100 text-red-800' },
};

const typesBySeverity = computed(() => {
    const grouped = {};
    severities.value.forEach(s => { grouped[s] = []; });
    (props.disciplineViolationTypes || []).forEach(t => {
        if (grouped[t.severity]) grouped[t.severity].push(t);
    });
    return grouped;
});

// ── Add Type ──
const showAddTypeForm = ref(false);
const addTypeSeverity = ref('Minor');
const newType = ref({ name: '', description: '', default_sanction: '' });

const addType = () => {
    typeProcessing.value = true;
    typeErrors.value = {};

    router.post(route('admin.settings.violation-types.store'), {
        name: newType.value.name,
        severity: addTypeSeverity.value,
        description: newType.value.description,
        default_sanction: newType.value.default_sanction,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newType.value = { name: '', description: '', default_sanction: '' };
            showAddTypeForm.value = false;
        },
        onError: (errors) => { typeErrors.value = errors; },
        onFinish: () => { typeProcessing.value = false; },
    });
};

const closeTypeModal = () => {
    showAddTypeForm.value = false;
    newType.value = { name: '', description: '', default_sanction: '' };
    typeErrors.value = {};
};

// ── Edit Type ──
const editingTypeId = ref(null);
const editTypeForm = ref({ name: '', severity: '', description: '', default_sanction: '' });

const startTypeEdit = (type) => {
    editingTypeId.value = type.id;
    editTypeForm.value = {
        name: type.name,
        severity: type.severity,
        description: type.description || '',
        default_sanction: type.default_sanction || '',
    };
    typeErrors.value = {};
};

const cancelTypeEdit = () => {
    editingTypeId.value = null;
    typeErrors.value = {};
};

const saveTypeEdit = (typeId) => {
    typeProcessing.value = true;
    typeErrors.value = {};

    router.put(route('admin.settings.violation-types.update', typeId), editTypeForm.value, {
        preserveScroll: true,
        onSuccess: () => { editingTypeId.value = null; },
        onError: (errors) => { typeErrors.value = errors; },
        onFinish: () => { typeProcessing.value = false; },
    });
};

// ── Delete Type ──
const confirmingDeleteTypeId = ref(null);

const deleteType = (typeId) => {
    typeProcessing.value = true;
    router.delete(route('admin.settings.violation-types.destroy', typeId), {
        preserveScroll: true,
        onFinish: () => {
            typeProcessing.value = false;
            confirmingDeleteTypeId.value = null;
        },
    });
};

// ──────────────────────────────────────────────
// Role Management
// ──────────────────────────────────────────────
const showRoleModal = ref(false);
const selectedRole = ref(null);
const editingRoleId = ref(null);
const editRoleForm = ref({ role_name: '' });
const roleProcessing = ref(false);
const roleErrors = ref({});
const confirmingDeleteRoleId = ref(null);

const openRoleModal = (role = null) => {
    selectedRole.value = role;
    showRoleModal.value = true;
};

const closeRoleModal = () => {
    showRoleModal.value = false;
    selectedRole.value = null;
    router.reload({ only: ['roles'] });
};

const startRoleEdit = (role) => {
    editingRoleId.value = role.role_id;
    editRoleForm.value = { role_name: role.role_name };
    roleErrors.value = {};
};

const cancelRoleEdit = () => {
    editingRoleId.value = null;
    roleErrors.value = {};
};

const saveRoleEdit = async (roleId) => {
    roleProcessing.value = true;
    roleErrors.value = {};

    try {
        const response = await axios.put(route('admin.roles.update', roleId), editRoleForm.value);
        if (response.data.success) {
            notify('success', 'Role updated successfully.');
            editingRoleId.value = null;
            router.reload({ only: ['roles'] });
        } else {
            notify('error', response.data.message || 'Failed to update role.');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            roleErrors.value = error.response.data.errors || {};
        } else {
            notify('error', error.response?.data?.message || 'Failed to update role.');
        }
    } finally {
        roleProcessing.value = false;
    }
};

const deleteRole = async (role) => {
    confirmAction(
        `Are you sure you want to delete the role "${formatLabel(role.role_name)}"?${role.users_count > 0 ? ` It is assigned to ${role.users_count} user(s).` : ''}`,
        'Delete Role',
        async () => {
            try {
                const response = await axios.delete(route('admin.roles.destroy', role.role_id));
                if (response.data.success) {
                    notify('success', 'Role deleted successfully.');
                    router.reload({ only: ['roles'] });
                } else {
                    notify('error', response.data.message || 'Failed to delete role.');
                }
            } catch (error) {
                notify('error', error.response?.data?.message || 'Failed to delete role.');
            }
        },
        { confirmLabel: 'Delete', cancelLabel: 'Cancel' }
    );
};

// ──────────────────────────────────────────────
// Lookup Values Management
// ──────────────────────────────────────────────
const lookupLabels = {
    organization_types: 'Organization Types',
    complaint_categories: 'Complaint Categories',
    guidance_case_types: 'Guidance Case Types',
    guidance_appointment_types: 'Guidance Appointment Types',
    event_statuses: 'Event Statuses',
    violation_severities: 'Violation Severities',
    default_org_positions: 'Default Org Positions',
};

const lookupDescriptions = {
    organization_types: 'Types available when creating or editing student organizations.',
    complaint_categories: 'Categories students can choose when filing complaints.',
    guidance_case_types: 'Types available when creating guidance cases.',
    guidance_appointment_types: 'Types available when booking guidance appointments.',
    event_statuses: 'Status options for organization events.',
    violation_severities: 'Severity levels for discipline violations.',
    default_org_positions: 'Default officer positions seeded for new organizations.',
};

const lookupIcons = {
    organization_types: '🏛️',
    complaint_categories: '📋',
    guidance_case_types: '🤝',
    guidance_appointment_types: '📅',
    event_statuses: '🎉',
    violation_severities: '⚠️',
    default_org_positions: '👤',
};

const lookupProcessing = ref(false);
const newLookupItem = ref({});
const editingLookupKey = ref(null);
const editingLookupIndex = ref(null);
const editingLookupValue = ref('');

const addLookupItem = (key) => {
    const value = (newLookupItem.value[key] || '').trim();
    if (!value) return;
    const currentValues = [...(props.lookupValues[key] || [])];
    if (currentValues.includes(value)) {
        notify('error', `"${value}" already exists in this list.`);
        return;
    }
    currentValues.push(value);
    saveLookupValues(key, currentValues);
    newLookupItem.value[key] = '';
};

const removeLookupItem = (key, index) => {
    const currentValues = [...(props.lookupValues[key] || [])];
    if (currentValues.length <= 1) {
        notify('error', 'Cannot remove the last item. At least one value is required.');
        return;
    }
    const removed = currentValues.splice(index, 1);
    confirmAction(
        `Remove "${removed[0]}" from ${lookupLabels[key]}?`,
        'Remove Value',
        () => saveLookupValues(key, currentValues),
        { confirmLabel: 'Remove', cancelLabel: 'Cancel' }
    );
};

const startLookupEdit = (key, index) => {
    editingLookupKey.value = key;
    editingLookupIndex.value = index;
    editingLookupValue.value = props.lookupValues[key][index];
};

const cancelLookupEdit = () => {
    editingLookupKey.value = null;
    editingLookupIndex.value = null;
    editingLookupValue.value = '';
};

const saveLookupEdit = (key, index) => {
    const value = editingLookupValue.value.trim();
    if (!value) return;
    const currentValues = [...(props.lookupValues[key] || [])];
    // Check for duplicates (excluding current)
    if (currentValues.some((v, i) => i !== index && v === value)) {
        notify('error', `"${value}" already exists in this list.`);
        return;
    }
    currentValues[index] = value;
    saveLookupValues(key, currentValues);
    cancelLookupEdit();
};

const saveLookupValues = (key, values) => {
    lookupProcessing.value = true;
    router.put(route('admin.settings.lookup-values.update'), {
        key: key,
        values: values,
    }, {
        preserveScroll: true,
        onFinish: () => { lookupProcessing.value = false; },
    });
};
</script>

<template>

    <Head title="Settings" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Settings
                </h2>
                <PrimaryButton @click="handleAddNew">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New
                </PrimaryButton>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600" aria-label="Tabs">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                            'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            activeTab === tab.id
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200 hover:border-gray-300 dark:border-gray-600'
                        ]">
                            {{ tab.label }}
                            <span :class="[
                                'ml-2 px-2 py-0.5 text-xs rounded-full',
                                activeTab === tab.id
                                    ? 'bg-indigo-100 text-indigo-600 dark:text-indigo-400'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'
                            ]">
                                {{ tab.count.value }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Academic Calendars Tab -->
                <div v-if="activeTab === 'calendars'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Manage academic terms/semesters. Only one term can be <strong>Active</strong> at a time.
                            Setting a term as active will automatically mark other active terms as completed.
                        </p>
                    </div>
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Academic
                                        Year
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Semester
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Start
                                        Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">End Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                        Enrollments</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!academicCalendars.data || academicCalendars.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No academic calendars found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr v-for="calendar in academicCalendars.data" :key="calendar.calendar_id"
                                    class="hover:bg-gray-50 dark:bg-gray-900">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ calendar.academic_year }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ calendar.semester || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ calendar.start_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ calendar.end_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                            :class="getStatusBadgeClass(calendar.status)">
                                            {{ calendar.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ calendar.enrollments_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openCalendarModal(calendar)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="academicCalendars" pageName="calendars_page" />
                    </div>
                </div>

                <!-- Courses Tab -->
                <div v-if="activeTab === 'courses'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Manage courses/programs. Courses with sections or enrollments cannot be deleted.
                        </p>
                    </div>
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Course
                                        Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Course
                                        Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                        Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Sections
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="!courses.data || courses.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No courses found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr v-for="course in courses.data" :key="course.course_id" class="hover:bg-gray-50 dark:bg-gray-900">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ course.course_code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ course.course_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 max-w-xs truncate">
                                        {{ course.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ course.sections_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openCourseModal(course)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="courses" pageName="courses_page" />
                    </div>
                </div>



                <!-- Discipline Workflow Tab -->
                <div v-if="activeTab === 'discipline-workflow'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Configure the workflow steps for discipline violation cases. These steps define the progress
                            bar
                            shown on each case.
                            Drag rows to reorder. Steps with active cases cannot be deleted.
                        </p>
                    </div>

                    <!-- Error alert (e.g., delete blocked) -->
                    <div v-if="flashErrors.workflow" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700">{{ flashErrors.workflow }}</p>
                    </div>

                    <!-- Live preview of current workflow -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide mb-3">Progress Bar Preview
                        </p>
                        <StatusProgressBar :steps="previewSteps" :terminal-statuses="previewTerminal" current-status=""
                            size="sm" />
                    </div>

                    <!-- Workflow steps table -->
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="w-10 px-3 py-3"></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Order
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Step
                                        Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                        Description</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                        Terminal</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Cases
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="disciplineWorkflowSteps.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No workflow steps defined. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr v-for="(step, index) in disciplineWorkflowSteps" :key="step.id" draggable="true"
                                    @dragstart="onDragStart(index)" @dragover="onDragOver($event, index)"
                                    @dragend="onDragEnd" :class="[
                                        'hover:bg-gray-50 dark:bg-gray-900 transition-colors cursor-grab active:cursor-grabbing',
                                        dragOverIndex === index ? 'bg-indigo-50 border-t-2 border-indigo-400' : '',
                                    ]">
                                    <!-- Drag handle -->
                                    <td class="px-3 py-4 text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </td>
                                    <!-- Order -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 font-mono">
                                        {{ step.sort_order }}
                                    </td>

                                    <!-- Name (editable) -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <template v-if="editingStepId === step.id">
                                            <input v-model="editForm.name" type="text"
                                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                                                @keyup.enter="saveEdit(step.id)" @keyup.escape="cancelEdit" />
                                            <p v-if="stepErrors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{
                                                stepErrors.name }}</p>
                                        </template>
                                        <span v-else class="font-medium text-gray-900 dark:text-white">{{ step.name }}</span>
                                    </td>

                                    <!-- Description (editable) -->
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 max-w-xs">
                                        <template v-if="editingStepId === step.id">
                                            <input v-model="editForm.description" type="text"
                                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                                                placeholder="Optional description..." @keyup.enter="saveEdit(step.id)"
                                                @keyup.escape="cancelEdit" />
                                        </template>
                                        <span v-else class="truncate block">{{ step.description || '—' }}</span>
                                    </td>

                                    <!-- Terminal badge -->
                                    <td class="px-4 py-4 text-center">
                                        <template v-if="editingStepId === step.id">
                                            <input type="checkbox" v-model="editForm.is_terminal"
                                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500" />
                                        </template>
                                        <template v-else>
                                            <span v-if="step.is_terminal"
                                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Final
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Cases count -->
                                    <td class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ step.cases_count }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <template v-if="editingStepId === step.id">
                                            <button @click="saveEdit(step.id)" :disabled="stepProcessing"
                                                class="text-green-600 hover:text-green-900">Save</button>
                                            <button @click="cancelEdit"
                                                class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">Cancel</button>
                                        </template>
                                        <template v-else-if="confirmingDeleteId === step.id">
                                            <span class="text-sm text-red-600 dark:text-red-400 mr-2">Delete?</span>
                                            <button @click="deleteStep(step.id)" :disabled="stepProcessing"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 font-semibold">Yes</button>
                                            <button @click="cancelDelete"
                                                class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">No</button>
                                        </template>
                                        <template v-else>
                                            <button @click="startEdit(step)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</button>
                                            <button @click="confirmDelete(step)"
                                                class="text-red-500 hover:text-red-700">Delete</button>
                                        </template>
                                    </td>
                                </tr>

                                <!-- Add new step row -->
                                <tr v-if="showAddStepForm" class="bg-indigo-50">
                                    <td class="px-3 py-4"></td>
                                    <td class="px-4 py-4 text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 font-mono">New</td>
                                    <td class="px-4 py-4">
                                        <input v-model="newStep.name" type="text" placeholder="Step name..."
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                                            @keyup.enter="addStep" @keyup.escape="showAddStepForm = false" />
                                        <p v-if="stepErrors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ stepErrors.name
                                            }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input v-model="newStep.description" type="text"
                                            placeholder="Optional description..."
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                                            @keyup.enter="addStep" />
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="newStep.is_terminal"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400">—</td>
                                    <td class="px-4 py-4 text-right text-sm font-medium space-x-2">
                                        <button @click="addStep" :disabled="stepProcessing"
                                            class="text-green-600 hover:text-green-900">Add</button>
                                        <button @click="showAddStepForm = false"
                                            class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Violation Types Tab ─────────────────────── -->
            <div v-if="activeTab === 'violation-types'">
                <!-- Error banner -->
                <div v-if="flashErrors.violation_type"
                    class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ flashErrors.violation_type }}
                </div>

                <!-- Severity sections -->
                <div v-for="sev in severities" :key="sev" class="mb-6">
                    <div :class="[severityColors[sev].bg, severityColors[sev].border]"
                        class="border rounded-lg overflow-hidden">
                        <div class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span :class="severityColors[sev].badge"
                                    class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full">
                                    {{ sev }}
                                </span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ typesBySeverity[sev].length }} type(s)
                                </span>
                            </div>
                        </div>
                        <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Name
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                            Description
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                            Default
                                            Sanction</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                            Cases</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="typesBySeverity[sev].length === 0">
                                        <td colspan="5" class="px-4 py-3 text-center text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400">No violation
                                            types
                                            for {{ sev }} offenses yet.</td>
                                    </tr>
                                    <tr v-for="type in typesBySeverity[sev]" :key="type.id" class="hover:bg-gray-50 dark:bg-gray-900">
                                        <!-- View mode -->
                                        <template v-if="editingTypeId !== type.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ type.name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ type.description || '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ type.default_sanction || '—'
                                                }}</td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ type.cases_count
                                                }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium space-x-2">
                                                <button @click="startTypeEdit(type)"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</button>
                                                <template v-if="confirmingDeleteTypeId === type.id">
                                                    <button @click="deleteType(type.id)" :disabled="typeProcessing"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-900 font-semibold">Confirm</button>
                                                    <button @click="confirmingDeleteTypeId = null"
                                                        class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">No</button>
                                                </template>
                                                <button v-else @click="confirmingDeleteTypeId = type.id"
                                                    class="text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </template>
                                        <!-- Edit mode -->
                                        <template v-else>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.name" type="text"
                                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.description" type="text"
                                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.default_sanction" type="text"
                                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100" />
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400">{{ type.cases_count
                                                }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium space-x-2">
                                                <button @click="saveTypeEdit(type.id)" :disabled="typeProcessing"
                                                    class="text-green-600 hover:text-green-900">Save</button>
                                                <button @click="cancelTypeEdit"
                                                    class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">Cancel</button>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add new type form -->
                <Modal :show="showAddTypeForm" @close="closeTypeModal" max-width="2xl">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Violation Type</h3>
                        <div v-if="typeErrors.violation_type"
                            class="mb-3 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-600 dark:text-red-400">{{
                                typeErrors.violation_type }}</div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Severity</label>
                                <select v-model="addTypeSeverity"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100">
                                    <option v-for="s in severities" :key="s" :value="s">{{ s }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name *</label>
                                <input v-model="newType.name" type="text" placeholder="e.g. Tardiness"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                                <p v-if="typeErrors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ typeErrors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Description
                                    (optional)</label>
                                <input v-model="newType.description" type="text" placeholder="Short description..."
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Default Sanction
                                    (optional)</label>
                                <input v-model="newType.default_sanction" type="text" placeholder="e.g. Verbal Warning"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <SecondaryButton type="button" @click="closeTypeModal">Cancel</SecondaryButton>
                            <PrimaryButton @click="addType" :disabled="typeProcessing || !newType.name">
                                {{ typeProcessing ? 'Adding...' : 'Add Type' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </Modal>
            </div>

            <!-- ── Roles Tab ─────────────────────────────── -->
            <div v-if="activeTab === 'roles'">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Manage user roles. Roles assigned to active users cannot be deleted.
                        </p>
                    </div>
                    <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Role
                                        Name</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Users
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="roles.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        No roles found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr v-for="role in roles" :key="role.role_id" class="hover:bg-gray-50 dark:bg-gray-900">
                                    <!-- Name -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <template v-if="editingRoleId === role.role_id">
                                            <input v-model="editRoleForm.role_name" type="text"
                                                class="block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                                                @keyup.enter="saveRoleEdit(role.role_id)"
                                                @keyup.escape="cancelRoleEdit" />
                                            <p v-if="roleErrors.role_name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{
                                                roleErrors.role_name[0] }}</p>
                                        </template>
                                        <span v-else class="font-medium text-gray-900 dark:text-white">{{ formatLabel(role.role_name)
                                            }}</span>
                                    </td>
                                    <!-- Users Count -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                            :class="role.users_count > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'">
                                            {{ role.users_count }}
                                        </span>
                                    </td>
                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <template v-if="editingRoleId === role.role_id">
                                            <button @click="saveRoleEdit(role.role_id)" :disabled="roleProcessing"
                                                class="text-green-600 hover:text-green-900">Save</button>
                                            <button @click="cancelRoleEdit"
                                                class="text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-200">Cancel</button>
                                        </template>
                                        <template v-else>
                                            <button @click="startRoleEdit(role)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</button>
                                            <button @click="deleteRole(role)"
                                                class="text-red-500 hover:text-red-700">Delete</button>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- Closes roles tab -->

            <!-- Lookup Values Tab -->
            <div v-if="activeTab === 'lookup-values'" class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Manage configurable lookup values used across the system. Add, edit, or remove values as
                        needed.
                        Changes take effect immediately in forms and filters.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="(values, key) in lookupValues" :key="key"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                        <!-- Card Header -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ lookupIcons[key] || '📝' }}</span>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ lookupLabels[key] || key }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5">{{ lookupDescriptions[key] || '' }}</p>
                                </div>
                                <span
                                    class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">
                                    {{ values.length }}
                                </span>
                            </div>
                        </div>

                        <!-- Values List -->
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                            <div v-for="(item, index) in values" :key="index"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 dark:bg-gray-900 transition-colors group">
                                <!-- Editing mode -->
                                <template v-if="editingLookupKey === key && editingLookupIndex === index">
                                    <input v-model="editingLookupValue" type="text"
                                        class="flex-1 text-sm rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                        @keyup.enter="saveLookupEdit(key, index)" @keyup.escape="cancelLookupEdit" />
                                    <button @click="saveLookupEdit(key, index)"
                                        class="text-green-600 hover:text-green-800" :disabled="lookupProcessing">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button @click="cancelLookupEdit" class="text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </template>

                                <!-- Display mode -->
                                <template v-else>
                                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-200">{{ item }}</span>
                                    <button @click="startLookupEdit(key, index)"
                                        class="opacity-0 group-hover:opacity-100 text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="removeLookupItem(key, index)"
                                        class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 dark:text-red-400 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </template>
                            </div>

                            <div v-if="!values || values.length === 0"
                                class="px-4 py-3 text-center text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                No values configured.
                            </div>
                        </div>

                        <!-- Add New Value -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <input v-model="newLookupItem[key]" type="text" placeholder="Add new value..."
                                    class="flex-1 text-sm rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    @keyup.enter="addLookupItem(key)" />
                                <button @click="addLookupItem(key)"
                                    :disabled="lookupProcessing || !(newLookupItem[key] || '').trim()"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AcademicCalendarFormModal :show="showCalendarModal" :calendar="selectedCalendar" @close="closeCalendarModal"
            @saved="closeCalendarModal" />

        <CourseFormModal :show="showCourseModal" :course="selectedCourse" @close="closeCourseModal"
            @saved="closeCourseModal" />



        <RoleFormModal :show="showRoleModal" :role="selectedRole" @close="closeRoleModal" @saved="closeRoleModal" />

        <NotificationDialog :show="notification.show" :type="notification.type" :title="notification.title"
            :message="notification.message" :confirm-label="notification.confirmLabel"
            :cancel-label="notification.cancelLabel" @close="closeNotification" @confirm="handleConfirm" />
    </AdminLayout>
</template>
