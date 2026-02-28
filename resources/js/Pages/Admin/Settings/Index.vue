<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AcademicCalendarFormModal from '@/Components/Admin/AcademicCalendarFormModal.vue';
import CourseFormModal from '@/Components/Admin/CourseFormModal.vue';
import SectionFormModal from '@/Components/Admin/SectionFormModal.vue';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    academicCalendars: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    courses: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    sections: {
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
});

// Tab management
const activeTab = ref('calendars');

const tabs = [
    { id: 'calendars', label: 'Academic Calendars', count: computed(() => props.academicCalendars.total ?? props.academicCalendars.data?.length ?? 0) },
    { id: 'courses', label: 'Courses', count: computed(() => props.courses.total ?? props.courses.data?.length ?? 0) },
    { id: 'sections', label: 'Sections', count: computed(() => props.sections.total ?? props.sections.data?.length ?? 0) },
    { id: 'discipline-workflow', label: 'Discipline Workflow', count: computed(() => props.disciplineWorkflowSteps.length) },
    { id: 'violation-types', label: 'Violation Types', count: computed(() => props.disciplineViolationTypes.length) },
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
    router.reload({ only: ['courses', 'sections'] });
};

// Section Modal
const showSectionModal = ref(false);
const selectedSection = ref(null);

const openSectionModal = (section = null) => {
    selectedSection.value = section;
    showSectionModal.value = true;
};

const closeSectionModal = () => {
    showSectionModal.value = false;
    selectedSection.value = null;
    router.reload({ only: ['sections'] });
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

// Section filters
const sectionCourseFilter = ref('');
const sectionCalendarFilter = ref('');

const filteredSections = computed(() => {
    let result = props.sections.data || [];

    if (sectionCourseFilter.value) {
        result = result.filter(s => s.course_id == sectionCourseFilter.value);
    }

    if (sectionCalendarFilter.value) {
        result = result.filter(s => s.calendar_id == sectionCalendarFilter.value);
    }

    return result;
});

// Add button action based on active tab
const handleAddNew = () => {
    switch (activeTab.value) {
        case 'calendars':
            openCalendarModal();
            break;
        case 'courses':
            openCourseModal();
            break;
        case 'sections':
            openSectionModal();
            break;
        case 'discipline-workflow':
            showAddStepForm.value = true;
            break;
        case 'violation-types':
            showAddTypeForm.value = true;
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

const severities = ['Minor', 'Moderate', 'Major'];
const severityColors = {
    Minor: { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-700', badge: 'bg-green-100 text-green-800' },
    Moderate: { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-700', badge: 'bg-yellow-100 text-yellow-800' },
    Major: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badge: 'bg-red-100 text-red-800' },
};

const typesBySeverity = computed(() => {
    const grouped = {};
    severities.forEach(s => { grouped[s] = []; });
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
</script>

<template>
    <Head title="Settings" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
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
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ tab.label }}
                            <span
                                :class="[
                                    'ml-2 px-2 py-0.5 text-xs rounded-full',
                                    activeTab === tab.id
                                        ? 'bg-indigo-100 text-indigo-600'
                                        : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ tab.count.value }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Academic Calendars Tab -->
                <div v-if="activeTab === 'calendars'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Manage academic terms/semesters. Only one term can be <strong>Active</strong> at a time.
                            Setting a term as active will automatically mark other active terms as completed.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Academic Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrollments</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="!academicCalendars.data || academicCalendars.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No academic calendars found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="calendar in academicCalendars.data"
                                    :key="calendar.calendar_id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ calendar.academic_year }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ calendar.semester || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ calendar.start_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ calendar.end_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize"
                                            :class="getStatusBadgeClass(calendar.status)"
                                        >
                                            {{ calendar.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ calendar.enrollments_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openCalendarModal(calendar)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
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
                        <p class="text-sm text-gray-600">
                            Manage courses/programs. Courses with sections or enrollments cannot be deleted.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sections</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="!courses.data || courses.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No courses found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="course in courses.data"
                                    :key="course.course_id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ course.course_code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ course.course_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ course.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ course.sections_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openCourseModal(course)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="courses" pageName="courses_page" />
                    </div>
                </div>

                <!-- Sections Tab -->
                <div v-if="activeTab === 'sections'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Manage sections. Sections with enrollments cannot be deleted.
                        </p>
                    </div>

                    <!-- Filters -->
                    <div class="mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Course</label>
                            <select
                                v-model="sectionCourseFilter"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Courses</option>
                                <option
                                    v-for="course in courses.data"
                                    :key="course.course_id"
                                    :value="course.course_id"
                                >
                                    {{ course.course_code }} - {{ course.course_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredSections.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No sections found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="section in filteredSections"
                                    :key="section.section_id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ section.section_code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ section.course_code || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openSectionModal(section)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="sections" pageName="sections_page" />
                    </div>
                </div>

                <!-- Discipline Workflow Tab -->
                <div v-if="activeTab === 'discipline-workflow'" class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Configure the workflow steps for discipline violation cases. These steps define the progress bar shown on each case.
                            Drag rows to reorder. Steps with active cases cannot be deleted.
                        </p>
                    </div>

                    <!-- Error alert (e.g., delete blocked) -->
                    <div v-if="flashErrors.workflow" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700">{{ flashErrors.workflow }}</p>
                    </div>

                    <!-- Live preview of current workflow -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">Progress Bar Preview</p>
                        <StatusProgressBar
                            :steps="previewSteps"
                            :terminal-statuses="previewTerminal"
                            current-status=""
                            size="sm"
                        />
                    </div>

                    <!-- Workflow steps table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-10 px-3 py-3"></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Step Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Terminal</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cases</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="disciplineWorkflowSteps.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No workflow steps defined. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(step, index) in disciplineWorkflowSteps"
                                    :key="step.id"
                                    draggable="true"
                                    @dragstart="onDragStart(index)"
                                    @dragover="onDragOver($event, index)"
                                    @dragend="onDragEnd"
                                    :class="[
                                        'hover:bg-gray-50 transition-colors cursor-grab active:cursor-grabbing',
                                        dragOverIndex === index ? 'bg-indigo-50 border-t-2 border-indigo-400' : '',
                                    ]"
                                >
                                    <!-- Drag handle -->
                                    <td class="px-3 py-4 text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </td>
                                    <!-- Order -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                        {{ step.sort_order }}
                                    </td>

                                    <!-- Name (editable) -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <template v-if="editingStepId === step.id">
                                            <input
                                                v-model="editForm.name"
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                @keyup.enter="saveEdit(step.id)"
                                                @keyup.escape="cancelEdit"
                                            />
                                            <p v-if="stepErrors.name" class="mt-1 text-xs text-red-600">{{ stepErrors.name }}</p>
                                        </template>
                                        <span v-else class="font-medium text-gray-900">{{ step.name }}</span>
                                    </td>

                                    <!-- Description (editable) -->
                                    <td class="px-4 py-4 text-sm text-gray-500 max-w-xs">
                                        <template v-if="editingStepId === step.id">
                                            <input
                                                v-model="editForm.description"
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                placeholder="Optional description..."
                                                @keyup.enter="saveEdit(step.id)"
                                                @keyup.escape="cancelEdit"
                                            />
                                        </template>
                                        <span v-else class="truncate block">{{ step.description || '—' }}</span>
                                    </td>

                                    <!-- Terminal badge -->
                                    <td class="px-4 py-4 text-center">
                                        <template v-if="editingStepId === step.id">
                                            <input type="checkbox" v-model="editForm.is_terminal"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </template>
                                        <template v-else>
                                            <span v-if="step.is_terminal"
                                                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Final
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Cases count -->
                                    <td class="px-4 py-4 text-center text-sm text-gray-500">
                                        {{ step.cases_count }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <template v-if="editingStepId === step.id">
                                            <button @click="saveEdit(step.id)" :disabled="stepProcessing"
                                                class="text-green-600 hover:text-green-900">Save</button>
                                            <button @click="cancelEdit"
                                                class="text-gray-500 hover:text-gray-700">Cancel</button>
                                        </template>
                                        <template v-else-if="confirmingDeleteId === step.id">
                                            <span class="text-sm text-red-600 mr-2">Delete?</span>
                                            <button @click="deleteStep(step.id)" :disabled="stepProcessing"
                                                class="text-red-600 hover:text-red-900 font-semibold">Yes</button>
                                            <button @click="cancelDelete"
                                                class="text-gray-500 hover:text-gray-700">No</button>
                                        </template>
                                        <template v-else>
                                            <button @click="startEdit(step)"
                                                class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <button @click="confirmDelete(step)"
                                                class="text-red-500 hover:text-red-700">Delete</button>
                                        </template>
                                    </td>
                                </tr>

                                <!-- Add new step row -->
                                <tr v-if="showAddStepForm" class="bg-indigo-50">
                                    <td class="px-3 py-4"></td>
                                    <td class="px-4 py-4 text-sm text-gray-400 font-mono">New</td>
                                    <td class="px-4 py-4">
                                        <input
                                            v-model="newStep.name"
                                            type="text"
                                            placeholder="Step name..."
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            @keyup.enter="addStep"
                                            @keyup.escape="showAddStepForm = false"
                                        />
                                        <p v-if="stepErrors.name" class="mt-1 text-xs text-red-600">{{ stepErrors.name }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input
                                            v-model="newStep.description"
                                            type="text"
                                            placeholder="Optional description..."
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            @keyup.enter="addStep"
                                        />
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" v-model="newStep.is_terminal"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-gray-400">—</td>
                                    <td class="px-4 py-4 text-right text-sm font-medium space-x-2">
                                        <button @click="addStep" :disabled="stepProcessing"
                                            class="text-green-600 hover:text-green-900">Add</button>
                                        <button @click="showAddStepForm = false"
                                            class="text-gray-500 hover:text-gray-700">Cancel</button>
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
                <div v-if="flashErrors.violation_type" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ flashErrors.violation_type }}
                </div>

                <!-- Severity sections -->
                <div v-for="sev in severities" :key="sev" class="mb-6">
                    <div :class="[severityColors[sev].bg, severityColors[sev].border]" class="border rounded-lg overflow-hidden">
                        <div class="px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span :class="severityColors[sev].badge" class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full">
                                    {{ sev }}
                                </span>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ typesBySeverity[sev].length }} type(s)
                                </span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Default Sanction</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Cases</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="typesBySeverity[sev].length === 0">
                                        <td colspan="5" class="px-4 py-3 text-center text-sm text-gray-400">No violation types for {{ sev }} offenses yet.</td>
                                    </tr>
                                    <tr v-for="type in typesBySeverity[sev]" :key="type.id" class="hover:bg-gray-50">
                                        <!-- View mode -->
                                        <template v-if="editingTypeId !== type.id">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ type.name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ type.description || '—' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ type.default_sanction || '—' }}</td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-500">{{ type.cases_count }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium space-x-2">
                                                <button @click="startTypeEdit(type)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                                <template v-if="confirmingDeleteTypeId === type.id">
                                                    <button @click="deleteType(type.id)" :disabled="typeProcessing" class="text-red-600 hover:text-red-900 font-semibold">Confirm</button>
                                                    <button @click="confirmingDeleteTypeId = null" class="text-gray-500 hover:text-gray-700">No</button>
                                                </template>
                                                <button v-else @click="confirmingDeleteTypeId = type.id" class="text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </template>
                                        <!-- Edit mode -->
                                        <template v-else>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.name" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.description" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <input v-model="editTypeForm.default_sanction" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-400">{{ type.cases_count }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium space-x-2">
                                                <button @click="saveTypeEdit(type.id)" :disabled="typeProcessing" class="text-green-600 hover:text-green-900">Save</button>
                                                <button @click="cancelTypeEdit" class="text-gray-500 hover:text-gray-700">Cancel</button>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add new type form -->
                <div v-if="showAddTypeForm" class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Add New Violation Type</h4>
                    <div v-if="typeErrors.violation_type" class="mb-3 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-600">{{ typeErrors.violation_type }}</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Severity</label>
                            <select v-model="addTypeSeverity" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option v-for="s in severities" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                            <input v-model="newType.name" type="text" placeholder="e.g. Tardiness" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Description (optional)</label>
                            <input v-model="newType.description" type="text" placeholder="Short description..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Default Sanction (optional)</label>
                            <input v-model="newType.default_sanction" type="text" placeholder="e.g. Verbal Warning" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-3">
                        <button @click="showAddTypeForm = false" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                        <button @click="addType" :disabled="typeProcessing || !newType.name" class="px-3 py-1.5 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">Add Type</button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modals -->
        <AcademicCalendarFormModal
            :show="showCalendarModal"
            :calendar="selectedCalendar"
            @close="closeCalendarModal"
            @saved="closeCalendarModal"
        />

        <CourseFormModal
            :show="showCourseModal"
            :course="selectedCourse"
            @close="closeCourseModal"
            @saved="closeCourseModal"
        />

        <SectionFormModal
            :show="showSectionModal"
            :section="selectedSection"
            :courses="courses.data || []"
            @close="closeSectionModal"
            @saved="closeSectionModal"
        />
    </AdminLayout>
</template>
