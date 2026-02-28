<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import axios from 'axios';
import DashboardCards from '@/Components/Admin/DashboardCards.vue';
import StudentFormModal from '@/Components/Admin/StudentFormModal.vue';
import ImportStudentsModal from '@/Components/Admin/ImportStudentsModal.vue';
import CreateStudentAccountModal from '@/Components/Admin/CreateStudentAccountModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

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
const selectedStudent = ref(null);
const selectedStudentForAccount = ref(null);
const selectedStudentsForBulk = ref([]);

// Filters
const search = ref(props.filters.search || '');
const yearLevel = ref(props.filters.year_level || '');
const courseId = ref(props.filters.course_id || '');
const status = ref(props.filters.status || 'enrolled');

// Debounce timer
let debounceTimer = null;
const DEBOUNCE_DELAY = 400; // ms

const yearLevels = ['1', '2', '3', '4', '5'];
const statusOptions = [
    { value: 'enrolled', label: 'Enrolled' },
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

onMounted(() => {
});

onUnmounted(() => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});



const exportPdf = () => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (yearLevel.value) params.append('year_level', yearLevel.value);
    if (courseId.value) params.append('course_id', courseId.value);
    if (status.value) params.append('status', status.value);

    window.location.href = route('admin.students.export.pdf') + (params.toString() ? '?' + params.toString() : '');
};

const updateStudentStatus = (studentNumber, newStatus) => {
    router.patch(route('admin.students.updateStatus', studentNumber), {
        status: newStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const bulkUpdateStudentStatus = async (newStatus) => {
    if (selectedStudentsForBulk.value.length === 0) {
        alert('Please select at least one student.');
        return;
    }

    const label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    if (!confirm(`Mark ${selectedStudentsForBulk.value.length} selected student(s) as ${label}?`)) {
        return;
    }

    try {
        const response = await axios.post(route('admin.students.bulk-status'), {
            student_numbers: selectedStudentsForBulk.value,
            status: newStatus,
        });

        if (response.data.success) {
            alert(response.data.message);
            selectedStudentsForBulk.value = [];
            router.reload({ only: ['students', 'dashboardStats'] });
        } else {
            alert(response.data.message || 'Failed to update statuses.');
        }
    } catch (error) {
        console.error('Bulk status update failed:', error);
        alert(error.response?.data?.message || 'Failed to update statuses. Please try again.');
    }
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
                    <button v-if="selectedStudentsForBulk.length > 0" type="button" @click="bulkCreateAccounts"
                        class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white bg-green-600 hover:bg-green-700 shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Create Accounts ({{ selectedStudentsForBulk.length }})
                    </button>
                    <button v-if="selectedStudentsForBulk.length > 0" type="button"
                        @click="bulkUpdateStudentStatus('graduated')"
                        class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Graduate ({{ selectedStudentsForBulk.length }})
                    </button>
                    <button v-if="selectedStudentsForBulk.length > 0" type="button"
                        @click="bulkUpdateStudentStatus('dropped')"
                        class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white bg-red-600 hover:bg-red-700 shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Drop ({{ selectedStudentsForBulk.length }})
                    </button>
                    <SecondaryButton @click="openImportModal">
                        Import File
                    </SecondaryButton>

                    <SecondaryButton @click="exportPdf">
                        Export PDF
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
                        <span v-if="activeTerm"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Active
                        </span>
                        <span v-if="!activeTerm" class="text-sm text-gray-500 italic">
                            No active term set
                        </span>
                    </div>
                    <Link :href="route('admin.settings')" class="text-sm text-indigo-600 hover:text-indigo-900">
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
                        <input id="search" v-model="search" type="text" placeholder="Student number or name..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- Year Level -->
                    <div>
                        <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">
                            Year Level
                        </label>
                        <select id="year_level" v-model="yearLevel"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                        <select id="course_id" v-model="courseId"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                        <select id="status" v-model="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="all">All</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>


            </div>

            <!-- Students Table -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Year Level
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Section
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <span>Account</span>
                                        <div class="flex items-center gap-1 ml-2" @click.stop>
                                            <input ref="selectAllCheckboxRef" type="checkbox"
                                                :checked="allNoAccountSelected" @change="toggleSelectAllNoAccount"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                title="Select all students without accounts" />
                                            <span class="text-xs font-normal text-gray-500">(No Account)</span>
                                        </div>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                            <tr v-for="student in students.data" :key="student.enrollment_id"
                                class="hover:bg-gray-50 cursor-pointer transition-colors"
                                @click="goToStudentProfile(student.student_number)">
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
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="{
                                        'bg-green-100 text-green-800': student.status === 'enrolled',
                                        'bg-blue-100 text-blue-800': student.status === 'graduated',
                                        'bg-red-100 text-red-800': student.status === 'dropped',
                                    }">
                                        {{ student.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                    <div class="flex items-center gap-2">
                                        <span v-if="student.has_account"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Account
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            No Account
                                        </span>
                                        <input type="checkbox"
                                            :checked="selectedStudentsForBulk.includes(student.student_number)"
                                            @click.stop="toggleStudentSelection(student.student_number)"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
                                    <div class="flex justify-end space-x-2">
                                        <button v-if="!student.has_account"
                                            @click.stop="openCreateAccountModal(student)"
                                            class="text-green-600 hover:text-green-900" title="Create Account">
                                            Create Account
                                        </button>
                                        <button v-else @click.stop="openCreateAccountModal(student)"
                                            class="text-blue-600 hover:text-blue-900" title="View/Reset Account">
                                            Account
                                        </button>
                                        <button @click.stop="openEditModal(student)"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </button>
                                        <select @click.stop :value="student.status"
                                            @change="updateStudentStatus(student.student_number, $event.target.value)"
                                            class="text-xs rounded border-gray-300 py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="enrolled">Enrolled</option>
                                            <option value="graduated">Graduated</option>
                                            <option value="dropped">Dropped</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :data="students" routeName="admin.students.index" :filters="{
                    search: search || undefined,
                    year_level: yearLevel || undefined,
                    course_id: courseId || undefined,
                    status: status || undefined,
                    acad_id: props.activeAcademicCalendar?.calendar_id || undefined,
                }" />
            </div>
        </div>

        <!-- Modals -->
        <StudentFormModal :show="showStudentModal" :student="selectedStudent" :courses="courses"
            :active-term="activeTerm" @close="closeStudentModal" />

        <CreateStudentAccountModal :show="showAccountModal" :student="selectedStudentForAccount"
            @close="closeAccountModal" @created="handleAccountCreated" />

        <ImportStudentsModal :show="showImportModal" :import-result="importResult" :active-term="activeTerm"
            @close="closeImportModal" />
    </AdminLayout>
</template>
