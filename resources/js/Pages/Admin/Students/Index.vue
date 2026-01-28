<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import axios from 'axios';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import StudentFormModal from '@/Components/Admin/StudentFormModal.vue';
import ImportStudentsModal from '@/Components/Admin/ImportStudentsModal.vue';
import CreateStudentAccountModal from '@/Components/Admin/CreateStudentAccountModal.vue';
import CourseFormModal from '@/Components/Admin/CourseFormModal.vue';
import SectionFormModal from '@/Components/Admin/SectionFormModal.vue';
import AcademicCalendarFormModal from '@/Components/Admin/AcademicCalendarFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    students: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    courses: {
        type: Array,
        default: () => [],
    },
    activeTerm: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
    dashboardStats: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const showStudentModal = ref(false);
const showImportModal = ref(false);
const showAccountModal = ref(false);
const showCourseModal = ref(false);
const showSectionModal = ref(false);
const showAcademicCalendarModal = ref(false);
const showManageDropdown = ref(false);
const selectedStudent = ref(null);
const selectedStudentForAccount = ref(null);
const selectedCourse = ref(null);
const selectedSection = ref(null);
const selectedAcademicCalendar = ref(null);
const selectedStudentsForBulk = ref([]);

// Filters
const search = ref(props.filters.search || '');
const yearLevel = ref(props.filters.year_level || '');
const courseId = ref(props.filters.course_id || '');
const status = ref(props.filters.status || '');

// Debounce timer
let debounceTimer = null;
const DEBOUNCE_DELAY = 400; // ms

const yearLevels = ['1', '2', '3', '4', '5'];
const statusOptions = [
    { value: 'enrolled', label: 'Enrolled' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'graduated', label: 'Graduated' },
    { value: 'dropped', label: 'Dropped' },
];

// Auto-apply filters with debounce (term is locked to active term)
const applyFilters = () => {
    router.get(route('admin.students.index'), {
        search: search.value || undefined,
        year_level: yearLevel.value || undefined,
        course_id: courseId.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedApplyFilters = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, DEBOUNCE_DELAY);
};

// Immediate apply for dropdown changes (no debounce needed)
const applyFiltersImmediate = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    applyFilters();
};

// Watch filter changes for auto-apply
watch(search, debouncedApplyFilters);
watch(yearLevel, applyFiltersImmediate);
watch(courseId, applyFiltersImmediate);
watch(status, applyFiltersImmediate);

const resetFilters = () => {
    search.value = '';
    yearLevel.value = '';
    courseId.value = '';
    status.value = '';
    router.get(route('admin.students.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openAddModal = () => {
    selectedStudent.value = null;
    showStudentModal.value = true;
};

const openEditModal = async (student) => {
    // Fetch full student data for editing
    try {
        const response = await axios.get(route('admin.students.show', student.student_number));
        selectedStudent.value = {
            ...response.data.student,
            course_id: response.data.current_enrollment?.course_id || '',
            section_id: response.data.current_enrollment?.section_id || '',
            year_level: response.data.current_enrollment?.year_level || '',
        };
        showStudentModal.value = true;
    } catch (error) {
        console.error('Failed to load student data:', error);
    }
};

const closeStudentModal = () => {
    showStudentModal.value = false;
    selectedStudent.value = null;
};

const openImportModal = () => {
    showImportModal.value = true;
};

const closeImportModal = () => {
    showImportModal.value = false;
};

const openCourseModal = () => {
    selectedCourse.value = null;
    showCourseModal.value = true;
};

const closeCourseModal = () => {
    showCourseModal.value = false;
    selectedCourse.value = null;
    // Refresh courses list
    router.reload({ only: ['courses'] });
};

const openSectionModal = () => {
    selectedSection.value = null;
    showSectionModal.value = true;
};

const closeSectionModal = () => {
    showSectionModal.value = false;
    selectedSection.value = null;
};

const openAcademicCalendarModal = () => {
    selectedAcademicCalendar.value = null;
    showAcademicCalendarModal.value = true;
};

const closeAcademicCalendarModal = () => {
    showAcademicCalendarModal.value = false;
    selectedAcademicCalendar.value = null;
    // Refresh academic calendars list
    router.reload({ only: ['academicCalendars'] });
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (showManageDropdown.value && !event.target.closest('.relative')) {
        showManageDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});

const exportStudents = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (yearLevel.value) params.append('year_level', yearLevel.value);
    if (courseId.value) params.append('course_id', courseId.value);
    if (status.value) params.append('status', status.value);

    window.location.href = route('admin.students.export') + (params.toString() ? '?' + params.toString() : '');
};

const updateStatus = (enrollmentId, currentStatus) => {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    router.patch(route('admin.students.enrollments.updateStatus', enrollmentId), {
        status: newStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

// Navigate to student profile
const goToStudentProfile = (studentId) => {
    router.visit(route('admin.students.profile', studentId));
};

// Account management functions
const openCreateAccountModal = (student) => {
    selectedStudentForAccount.value = student;
    showAccountModal.value = true;
};

const closeAccountModal = () => {
    showAccountModal.value = false;
    selectedStudentForAccount.value = null;
};

const handleAccountCreated = () => {
    closeAccountModal();
    router.reload({ only: ['students'] });
};

const bulkCreateAccounts = async () => {
    if (selectedStudentsForBulk.value.length === 0) {
        alert('Please select at least one student.');
        return;
    }

    if (!confirm(`Create accounts for ${selectedStudentsForBulk.value.length} selected student(s)?`)) {
        return;
    }

    try {
        const response = await axios.post(route('admin.students.accounts.bulk-create'), {
            student_numbers: selectedStudentsForBulk.value,
        });

        if (response.data.success) {
            alert(response.data.message);
            selectedStudentsForBulk.value = [];
            router.reload({ only: ['students'] });
        } else {
            alert(response.data.message || 'Failed to create accounts.');
        }
    } catch (error) {
        console.error('Bulk account creation failed:', error);
        alert(error.response?.data?.message || 'Failed to create accounts. Please try again.');
    }
};

const toggleStudentSelection = (studentId) => {
    const index = selectedStudentsForBulk.value.indexOf(studentId);
    if (index > -1) {
        selectedStudentsForBulk.value.splice(index, 1);
    } else {
        selectedStudentsForBulk.value.push(studentId);
    }
};

// Computed properties for "Select All (No Account)" feature
const studentsWithoutAccounts = computed(() => {
    if (!props.students?.data) return [];
    return props.students.data.filter(student => !student.has_account);
});

const allNoAccountSelected = computed(() => {
    if (studentsWithoutAccounts.value.length === 0) return false;
    const noAccountIds = studentsWithoutAccounts.value.map(s => s.student_number);
    return noAccountIds.every(id => selectedStudentsForBulk.value.includes(id));
});

const someNoAccountSelected = computed(() => {
    if (studentsWithoutAccounts.value.length === 0) return false;
    const noAccountIds = studentsWithoutAccounts.value.map(s => s.student_number);
    const selectedCount = noAccountIds.filter(id => selectedStudentsForBulk.value.includes(id)).length;
    return selectedCount > 0 && selectedCount < noAccountIds.length;
});

const selectAllCheckboxRef = ref(null);

const toggleSelectAllNoAccount = () => {
    const noAccountIds = studentsWithoutAccounts.value.map(s => s.student_number);
    
    if (allNoAccountSelected.value) {
        // Deselect all no-account students
        selectedStudentsForBulk.value = selectedStudentsForBulk.value.filter(
            id => !noAccountIds.includes(id)
        );
    } else {
        // Select all no-account students (keep existing selections)
        noAccountIds.forEach(id => {
            if (!selectedStudentsForBulk.value.includes(id)) {
                selectedStudentsForBulk.value.push(id);
            }
        });
    }
};

// Watch for changes to update indeterminate state
watch([allNoAccountSelected, someNoAccountSelected], () => {
    nextTick(() => {
        if (selectAllCheckboxRef.value) {
            selectAllCheckboxRef.value.indeterminate = someNoAccountSelected.value && !allNoAccountSelected.value;
        }
    });
}, { immediate: true });

const flash = computed(() => page.props.flash || {});
const importResult = computed(() => page.props.import_result || null);
</script>

<template>
    <Head title="Student Records" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Student Records
                </h2>
                <div class="flex flex-wrap gap-2">
                    <div class="relative">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition"
                            @click.stop="showManageDropdown = !showManageDropdown"
                        >
                            Manage
                            <svg class="ml-2 -mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div
                            v-show="showManageDropdown"
                            class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                            @click.stop
                        >
                            <div class="py-1">
                                <button
                                    @click="openCourseModal(); showManageDropdown = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Add Course
                                </button>
                                <button
                                    @click="openSectionModal(); showManageDropdown = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Add Section
                                </button>
                                <button
                                    @click="openAcademicCalendarModal(); showManageDropdown = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Add Academic Calendar
                                </button>
                            </div>
                        </div>
                    </div>
                    <SecondaryButton
                        v-if="selectedStudentsForBulk.length > 0"
                        @click="bulkCreateAccounts"
                        class="bg-green-600 hover:bg-green-700 text-white"
                    >
                        Create Accounts ({{ selectedStudentsForBulk.length }})
                    </SecondaryButton>
                    <SecondaryButton @click="openImportModal">
                        Import File
                    </SecondaryButton>
                    <SecondaryButton @click="exportStudents">
                        Export
                    </SecondaryButton>
                    <PrimaryButton @click="openAddModal">
                        Add Student
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>

            <!-- Active Term Display (Locked) -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-700">Current Term:</span>
                        <span v-if="activeTerm" class="text-lg font-semibold text-gray-900">
                            {{ activeTerm.display_label }}
                        </span>
                        <span
                            v-if="activeTerm"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                        >
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Active
                        </span>
                        <span v-if="!activeTerm" class="text-sm text-gray-500 italic">
                            No active term set
                        </span>
                    </div>
                    <Link
                        :href="route('admin.settings')"
                        class="text-sm text-indigo-600 hover:text-indigo-900"
                    >
                        Change in Settings →
                    </Link>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <DashboardCards v-if="dashboardStats && dashboardStats.length > 0" :cards="dashboardStats" />

            <!-- Success/Error Flash Messages -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ flash.error }}</p>
            </div>

            <!-- Import Results -->
            <div v-if="importResult" class="p-4 rounded-lg" :class="{
                'bg-green-50 border border-green-200': importResult.failed === 0,
                'bg-yellow-50 border border-yellow-200': importResult.failed > 0 && importResult.failed < (importResult.inserted + importResult.updated),
                'bg-red-50 border border-red-200': importResult.failed === (importResult.inserted + importResult.updated)
            }">
                <h3 class="font-semibold mb-2">Import Results</h3>
                <ul class="text-sm space-y-1">
                    <li>Inserted: {{ importResult.inserted || 0 }}</li>
                    <li>Updated: {{ importResult.updated || 0 }}</li>
                    <li>Failed: {{ importResult.failed || 0 }}</li>
                </ul>
                <div v-if="importResult.errors && importResult.errors.length > 0" class="mt-3">
                    <h4 class="font-medium text-sm mb-1">Errors:</h4>
                    <ul class="text-xs space-y-1 max-h-32 overflow-y-auto">
                        <li v-for="(error, index) in importResult.errors" :key="index">
                            Row {{ error.row }} ({{ error.student_number }}): {{ error.error }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>
                        <input
                            id="search"
                            v-model="search"
                            type="text"
                            placeholder="Student number or name..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Year Level -->
                    <div>
                        <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">
                            Year Level
                        </label>
                        <select
                            id="year_level"
                            v-model="yearLevel"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option v-for="level in yearLevels" :key="level" :value="level">
                                Year {{ level }}
                            </option>
                        </select>
                    </div>

                    <!-- Course -->
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Course
                        </label>
                        <select
                            id="course_id"
                            v-model="courseId"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option v-for="course in courses" :key="course.course_id" :value="course.course_id">
                                {{ course.course_code }}
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select
                            id="status"
                            v-model="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <SecondaryButton @click="resetFilters">
                        Reset Filters
                    </SecondaryButton>
                </div>
            </div>

            <!-- Students Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Year Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Section
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <span>Account</span>
                                        <div class="flex items-center gap-1 ml-2" @click.stop>
                                            <input
                                                ref="selectAllCheckboxRef"
                                                type="checkbox"
                                                :checked="allNoAccountSelected"
                                                @change="toggleSelectAllNoAccount"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                title="Select all students without accounts"
                                            />
                                            <span class="text-xs font-normal text-gray-500">(No Account)</span>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="students.data && students.data.length === 0">
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No students found for this term.
                                </td>
                            </tr>
                            <tr
                                v-for="student in students.data"
                                :key="student.enrollment_id"
                                class="hover:bg-gray-50 cursor-pointer transition-colors"
                                @click="goToStudentProfile(student.student_number)"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ student.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ student.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ student.year_level }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ student.section_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ student.course_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': student.status === 'active',
                                            'bg-yellow-100 text-yellow-800': student.status === 'enrolled',
                                            'bg-gray-100 text-gray-800': student.status === 'inactive',
                                            'bg-blue-100 text-blue-800': student.status === 'graduated',
                                            'bg-red-100 text-red-800': student.status === 'dropped',
                                        }"
                                    >
                                        {{ student.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="student.has_account"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Account
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                            No Account
                                        </span>
                                        <input
                                            type="checkbox"
                                            :checked="selectedStudentsForBulk.includes(student.student_number)"
                                            @click.stop="toggleStudentSelection(student.student_number)"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            v-if="!student.has_account"
                                            @click.stop="openCreateAccountModal(student)"
                                            class="text-green-600 hover:text-green-900"
                                            title="Create Account"
                                        >
                                            Create Account
                                        </button>
                                        <button
                                            v-else
                                            @click.stop="openCreateAccountModal(student)"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="View/Reset Account"
                                        >
                                            Account
                                        </button>
                                        <button
                                            @click.stop="openEditModal(student)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click.stop="updateStatus(student.enrollment_id, student.status)"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            {{ student.status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="students.links && students.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="students.links[0].url"
                                :href="students.links[0].url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="students.links[students.links.length - 1].url"
                                :href="students.links[students.links.length - 1].url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ students.meta?.from || 0 }}</span>
                                    to
                                    <span class="font-medium">{{ students.meta?.to || 0 }}</span>
                                    of
                                    <span class="font-medium">{{ students.meta?.total || 0 }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <Link
                                        v-for="(link, index) in students.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === students.links.length - 1 ? 'rounded-r-md' : '',
                                            !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <StudentFormModal
            :show="showStudentModal"
            :student="selectedStudent"
            :courses="courses"
            :active-term="activeTerm"
            @close="closeStudentModal"
        />

        <CreateStudentAccountModal
            :show="showAccountModal"
            :student="selectedStudentForAccount"
            @close="closeAccountModal"
            @created="handleAccountCreated"
        />

        <ImportStudentsModal
            :show="showImportModal"
            :import-result="importResult"
            :active-term="activeTerm"
            @close="closeImportModal"
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

        <AcademicCalendarFormModal
            :show="showAcademicCalendarModal"
            :calendar="selectedAcademicCalendar"
            @close="closeAcademicCalendarModal"
            @saved="closeAcademicCalendarModal"
        />
    </AdminLayout>
</template>
