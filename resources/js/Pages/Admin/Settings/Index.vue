<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AcademicCalendarFormModal from '@/Components/Admin/AcademicCalendarFormModal.vue';
import CourseFormModal from '@/Components/Admin/CourseFormModal.vue';
import SectionFormModal from '@/Components/Admin/SectionFormModal.vue';

const props = defineProps({
    academicCalendars: {
        type: Array,
        default: () => [],
    },
    courses: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
});

// Tab management
const activeTab = ref('calendars');

const tabs = [
    { id: 'calendars', label: 'Academic Calendars', count: computed(() => props.academicCalendars.length) },
    { id: 'courses', label: 'Courses', count: computed(() => props.courses.length) },
    { id: 'sections', label: 'Sections', count: computed(() => props.sections.length) },
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
    let result = props.sections;
    
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
    }
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
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
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
                                <tr v-if="academicCalendars.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No academic calendars found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="calendar in academicCalendars"
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
                                <tr v-if="courses.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No courses found. Click "Add New" to create one.
                                    </td>
                                </tr>
                                <tr
                                    v-for="course in courses"
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
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Course</label>
                            <select
                                v-model="sectionCourseFilter"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Courses</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.course_id"
                                    :value="course.course_id"
                                >
                                    {{ course.course_code }} - {{ course.course_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Academic Year</label>
                            <select
                                v-model="sectionCalendarFilter"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Academic Years</option>
                                <option
                                    v-for="calendar in academicCalendars"
                                    :key="calendar.calendar_id"
                                    :value="calendar.calendar_id"
                                >
                                    {{ calendar.academic_year }} - {{ calendar.semester }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Academic Year</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredSections.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
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
                                        {{ section.section_name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ section.course_code || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span v-if="section.academic_year">
                                            {{ section.academic_year }} - {{ section.semester }}
                                        </span>
                                        <span v-else>-</span>
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
            :courses="courses"
            @close="closeSectionModal"
            @saved="closeSectionModal"
        />
    </AdminLayout>
</template>

