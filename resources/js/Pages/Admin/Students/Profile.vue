<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatLabel } from '@/utils/formatLabel.js';

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        default: null,
    },
    educationalBackground: {
        type: Object,
        default: null,
    },
    familyInfo: {
        type: Object,
        default: null,
    },
    emergencyContact: {
        type: Object,
        default: null,
    },
    account: {
        type: Object,
        default: null,
    },
    enrollmentHistory: {
        type: Array,
        default: () => [],
    },
    profileComplete: {
        type: Boolean,
        default: true,
    },
});

const goBack = () => {
    router.visit(route('admin.students.index'));
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800',
        enrolled: 'bg-yellow-100 text-yellow-800',
        inactive: 'bg-gray-100 text-gray-800',
        graduated: 'bg-blue-100 text-blue-800',
        dropped: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatGender = (gender) => {
    if (!gender) return 'N/A';
    return gender.charAt(0).toUpperCase() + gender.slice(1);
};

const formatCivilStatus = (status) => {
    if (!status) return 'N/A';
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatBoolean = (value) => {
    if (value === null || value === undefined) return 'N/A';
    return value ? 'Yes' : 'No';
};

// Calculate age from birth date
const calculateAge = (birthDate) => {
    if (!birthDate) return 'N/A';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};
</script>

<template>
    <Head :title="`Student Profile - ${student.full_name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <button
                        @click="goBack"
                        class="inline-flex items-center text-gray-500 hover:text-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Student Profile
                    </h2>
                </div>
                <div class="flex gap-2">
                    <span
                        v-if="profile?.profile_status"
                        class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full"
                        :class="getStatusBadgeClass(profile.profile_status)"
                    >
                        Profile: {{ profile.profile_status.charAt(0).toUpperCase() + profile.profile_status.slice(1) }}
                    </span>
                    <SecondaryButton @click="goBack">
                        Back to List
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Incomplete profile banner -->
            <div v-if="!profileComplete"
                class="flex items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3">
                <svg class="h-5 w-5 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-amber-800">
                    Incomplete profile — student has not filled all required sections.
                </p>
            </div>

            <!-- STUDENT'S INFORMATION Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">STUDENT'S INFORMATION</h3>
                </div>
                <div class="p-6">
                    <!-- Course Applied -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Current Course</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ enrollmentHistory[0]?.course_name || 'N/A' }}
                                    <span v-if="enrollmentHistory[0]?.course_code" class="text-gray-500">({{ enrollmentHistory[0]?.course_code }})</span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Year Level</label>
                                <p class="mt-1 text-sm text-gray-900">{{ enrollmentHistory[0]?.year_level || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Last Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{ student.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">First Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{ student.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Middle Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{ student.middle_name || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Address</label>
                        <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ student.address || 'N/A' }}</p>
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Mobile Number</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ student.phone || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Email Address</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ student.email || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Birth Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Birth Date</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatDate(student.birth_date) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Birth Place</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ profile?.birth_place || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Age, Gender, Citizenship -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Age</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ calculateAge(student.birth_date) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Gender</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatGender(profile?.gender) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Citizenship</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ profile?.citizenship || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Civil Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Civil Status</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatCivilStatus(profile?.civil_status) }}</p>
                        </div>
                        <div v-if="profile?.civil_status === 'married'">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Name of Spouse</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ profile?.spouse_name || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Single Parent?</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatBoolean(profile?.is_single_parent) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">With Disability?</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatBoolean(profile?.has_disability) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Employed?</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatBoolean(profile?.is_employed) }}</p>
                        </div>
                        <div v-if="profile?.has_disability">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Disability</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ profile?.disability_details || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Company Name -->
                    <div v-if="profile?.is_employed" class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Company Name</label>
                        <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ profile?.company_name || 'N/A' }}</p>
                    </div>

                    <!-- Student Number and Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Student Number</label>
                            <p class="mt-1 text-sm text-gray-900 font-bold">{{ student.student_number }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Account Status</label>
                            <span
                                class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                :class="getStatusBadgeClass(student.status)"
                            >
                                {{ student.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EDUCATIONAL BACKGROUND Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">EDUCATIONAL BACKGROUND</h3>
                </div>
                <div class="p-6">
                    <div v-if="educationalBackground" class="space-y-4">
                        <!-- Elementary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Elementary School</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.elementary_school || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Date Graduated</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatDate(educationalBackground.elementary_graduated) }}</p>
                            </div>
                        </div>

                        <!-- Elementary Address -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Elementary Address</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.elementary_address || 'N/A' }}</p>
                        </div>

                        <!-- Senior High School -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Senior High School</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.senior_high_school || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Strand</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.senior_high_strand || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- SHS Address and Graduation -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Senior High Address</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.senior_high_address || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Date Graduated</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ formatDate(educationalBackground.senior_high_graduated) }}</p>
                            </div>
                        </div>

                        <!-- Honors -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Honors Received, If Any</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ educationalBackground.honors_received || 'N/A' }}</p>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 italic py-4">
                        No educational background information available.
                    </div>
                </div>
            </div>

            <!-- FAMILY INFORMATION Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">FAMILY INFORMATION</h3>
                </div>
                <div class="p-6">
                    <div v-if="familyInfo" class="space-y-4">
                        <!-- Father's Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Father's Name</label>
                                <div class="mt-1 grid grid-cols-3 gap-2">
                                    <div>
                                        <span class="text-xs text-gray-400">(Last Name)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.father_last_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">(First Name)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.father_first_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">(M.I.)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.father_middle_initial || 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Occupation/Trabajo</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1 pt-4">{{ familyInfo.father_occupation || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Mother's Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Mother's Maiden Name</label>
                                <div class="mt-1 grid grid-cols-3 gap-2">
                                    <div>
                                        <span class="text-xs text-gray-400">(Last Name)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.mother_maiden_last_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">(First Name)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.mother_first_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400">(M.I.)</span>
                                        <p class="text-sm text-gray-900 border-b border-gray-300 pb-1">{{ familyInfo.mother_middle_initial || 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Occupation/Trabajo</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1 pt-4">{{ familyInfo.mother_occupation || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 italic py-4">
                        No family information available.
                    </div>
                </div>
            </div>

            <!-- PERSON TO CONTACT IN CASE OF EMERGENCY Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">PERSON TO CONTACT IN CASE OF EMERGENCY</h3>
                </div>
                <div class="p-6">
                    <div v-if="emergencyContact" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Name</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ emergencyContact.contact_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Relationship</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ emergencyContact.relationship || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Contact Number</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ emergencyContact.contact_number || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Address</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ emergencyContact.contact_address || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 italic py-4">
                        No emergency contact information available.
                    </div>
                </div>
            </div>

            <!-- ACCOUNT INFORMATION Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">ACCOUNT INFORMATION</h3>
                </div>
                <div class="p-6">
                    <div v-if="account" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Email / Username</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1 font-medium">{{ account.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Account Status</label>
                                <span
                                    class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getStatusBadgeClass(account.status || 'active')"
                                >
                                    {{ account.status || 'active' }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Roles</label>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <span
                                        v-for="role in account.roles"
                                        :key="role"
                                        class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800"
                                    >
                                        {{ formatLabel(role) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Account Created</label>
                                <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ account.created_at || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 italic py-4">
                        No account has been created for this student yet.
                    </div>
                </div>
            </div>

            <!-- ENROLLMENT HISTORY Section -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">ENROLLMENT HISTORY</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Academic Term
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Section
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Year Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Enrollment Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="enrollmentHistory.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No enrollment history found.
                                </td>
                            </tr>
                            <tr v-for="enrollment in enrollmentHistory" :key="enrollment.enrollment_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ enrollment.term_label }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>{{ enrollment.course_name }}</div>
                                    <div class="text-xs text-gray-500">{{ enrollment.course_code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ enrollment.section_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    Year {{ enrollment.year_level }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ enrollment.enrollment_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusBadgeClass(enrollment.enrollment_status)"
                                    >
                                        {{ enrollment.enrollment_status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profile Submission Info (if profile exists) -->
            <div v-if="profile?.submitted_at" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div>
                        <span class="font-medium">Profile Submitted:</span> {{ profile.submitted_at }}
                    </div>
                    <div v-if="profile?.reviewed_at">
                        <span class="font-medium">Reviewed:</span> {{ profile.reviewed_at }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
