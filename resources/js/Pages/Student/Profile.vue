<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    student: { type: Object, default: null },
    profile: { type: Object, default: null },
    educationalBackground: { type: Object, default: null },
    familyInfo: { type: Object, default: null },
    emergencyContact: { type: Object, default: null },
    enrollmentHistory: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    // Editable student fields
    phone: props.student?.phone || '',
    email: props.student?.email || '',
    address: props.student?.address || '',
    birth_date: props.student?.birth_date ? props.student.birth_date.split('T')[0] : '',

    // Profile
    birth_place: props.profile?.birth_place || '',
    gender: props.profile?.gender || '',
    citizenship: props.profile?.citizenship || '',
    civil_status: props.profile?.civil_status || 'single',
    spouse_name: props.profile?.spouse_name || '',
    is_single_parent: props.profile?.is_single_parent || false,
    has_disability: props.profile?.has_disability || false,
    disability_details: props.profile?.disability_details || '',
    is_employed: props.profile?.is_employed || false,
    company_name: props.profile?.company_name || '',

    // Educational Background
    elementary_school: props.educationalBackground?.elementary_school || '',
    elementary_address: props.educationalBackground?.elementary_address || '',
    elementary_graduated: props.educationalBackground?.elementary_graduated || '',
    senior_high_school: props.educationalBackground?.senior_high_school || '',
    senior_high_strand: props.educationalBackground?.senior_high_strand || '',
    senior_high_address: props.educationalBackground?.senior_high_address || '',
    senior_high_graduated: props.educationalBackground?.senior_high_graduated || '',
    honors_received: props.educationalBackground?.honors_received || '',

    // Family Info
    father_last_name: props.familyInfo?.father_last_name || '',
    father_first_name: props.familyInfo?.father_first_name || '',
    father_middle_initial: props.familyInfo?.father_middle_initial || '',
    father_occupation: props.familyInfo?.father_occupation || '',
    mother_maiden_last_name: props.familyInfo?.mother_maiden_last_name || '',
    mother_first_name: props.familyInfo?.mother_first_name || '',
    mother_middle_initial: props.familyInfo?.mother_middle_initial || '',
    mother_occupation: props.familyInfo?.mother_occupation || '',

    // Emergency Contact
    contact_name: props.emergencyContact?.contact_name || '',
    relationship: props.emergencyContact?.relationship || '',
    contact_number: props.emergencyContact?.contact_number || '',
    contact_address: props.emergencyContact?.contact_address || '',
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
};

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

const computedAge = computed(() => calculateAge(form.birth_date));

const submit = () => {
    form.put(route('student.profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Profile" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                My Profile
            </h2>
        </template>

        <!-- No Student Record -->
        <div v-if="!student" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <p class="text-yellow-800">Your account is not linked to a student record. Please contact the administrator.
            </p>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-6">
            <!-- Success / Error Messages -->
            <div v-if="flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-800">{{ flash.error }}</p>
            </div>

            <!-- ======================== STUDENT'S INFORMATION ======================== -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">STUDENT'S INFORMATION</h3>
                </div>
                <div class="p-6">
                    <!-- Current Course (read-only) -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Current Course</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ enrollmentHistory[0]?.course_name || 'N/A' }}
                                    <span v-if="enrollmentHistory[0]?.course_code" class="text-gray-500">({{
                                        enrollmentHistory[0]?.course_code }})</span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase">Year Level</label>
                                <p class="mt-1 text-sm text-gray-900">{{ enrollmentHistory[0]?.year_level || 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Name (read-only) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Last Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{
                                student.last_name
                                }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">First Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{
                                student.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Middle Name</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium border-b border-gray-300 pb-1">{{
                                student.middle_name || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Address (editable) -->
                    <div class="mb-4">
                        <label for="address" class="block text-xs font-medium text-gray-500 uppercase">Address</label>
                        <input id="address" v-model="form.address" type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="e.g. Brgy. San Isidro, Concepcion, Tarlac" />
                        <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="phone" class="block text-xs font-medium text-gray-500 uppercase">Mobile
                                Number</label>
                            <input id="phone" v-model="form.phone" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="e.g. 09123456789" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-500 uppercase">Email
                                Address</label>
                            <input id="email" v-model="form.email" type="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="e.g. juan.delacruz@email.com" />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <!-- Birth Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="birth_date" class="block text-xs font-medium text-gray-500 uppercase">Birth
                                Date</label>
                            <input id="birth_date" v-model="form.birth_date" type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            <p v-if="form.errors.birth_date" class="mt-1 text-xs text-red-600">{{ form.errors.birth_date
                                }}</p>
                        </div>
                        <div>
                            <label for="birth_place" class="block text-xs font-medium text-gray-500 uppercase">Birth
                                Place</label>
                            <input id="birth_place" v-model="form.birth_place" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="e.g. Tarlac City, Tarlac" />
                            <p v-if="form.errors.birth_place" class="mt-1 text-xs text-red-600">{{
                                form.errors.birth_place }}
                            </p>
                        </div>
                    </div>

                    <!-- Age, Gender, Citizenship -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Age</label>
                            <p class="mt-1 text-sm text-gray-900 border-b border-gray-300 pb-1">{{ computedAge }}</p>
                        </div>
                        <div>
                            <label for="gender" class="block text-xs font-medium text-gray-500 uppercase">Gender</label>
                            <select id="gender" v-model="form.gender"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">{{ form.errors.gender }}</p>
                        </div>
                        <div>
                            <label for="citizenship"
                                class="block text-xs font-medium text-gray-500 uppercase">Citizenship</label>
                            <input id="citizenship" v-model="form.citizenship" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="e.g. Filipino" />
                            <p v-if="form.errors.citizenship" class="mt-1 text-xs text-red-600">{{
                                form.errors.citizenship }}
                            </p>
                        </div>
                    </div>

                    <!-- Civil Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="civil_status" class="block text-xs font-medium text-gray-500 uppercase">Civil
                                Status</label>
                            <select id="civil_status" v-model="form.civil_status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                            </select>
                            <p v-if="form.errors.civil_status" class="mt-1 text-xs text-red-600">{{
                                form.errors.civil_status }}
                            </p>
                        </div>
                        <div v-if="form.civil_status === 'married'">
                            <label for="spouse_name" class="block text-xs font-medium text-gray-500 uppercase">Name of
                                Spouse</label>
                            <input id="spouse_name" v-model="form.spouse_name" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter spouse name" />
                            <p v-if="form.errors.spouse_name" class="mt-1 text-xs text-red-600">{{
                                form.errors.spouse_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Single Parent?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_single_parent" :value="true"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_single_parent" :value="false"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">With
                                Disability?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.has_disability" :value="true"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.has_disability" :value="false"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Employed?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_employed" :value="true"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_employed" :value="false"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700">No</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.has_disability" class="mb-4">
                        <label for="disability_details"
                            class="block text-xs font-medium text-gray-500 uppercase">Disability
                            Details</label>
                        <input id="disability_details" v-model="form.disability_details" type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Specify disability" />
                    </div>

                    <div v-if="form.is_employed" class="mb-4">
                        <label for="company_name" class="block text-xs font-medium text-gray-500 uppercase">Company
                            Name</label>
                        <input id="company_name" v-model="form.company_name" type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Enter company name" />
                    </div>

                    <!-- Student Number (read-only) -->
                    <div class="pt-4 border-t border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase">Student Number</label>
                        <p class="mt-1 text-sm text-gray-900 font-bold">{{ student.student_number }}</p>
                    </div>
                </div>
            </div>

            <!-- ======================== EDUCATIONAL BACKGROUND ======================== -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">EDUCATIONAL BACKGROUND</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Elementary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="elementary_school"
                                class="block text-xs font-medium text-gray-500 uppercase">Elementary
                                School</label>
                            <input id="elementary_school" v-model="form.elementary_school" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter elementary school name" />
                        </div>
                        <div>
                            <label for="elementary_graduated"
                                class="block text-xs font-medium text-gray-500 uppercase">Date
                                Graduated</label>
                            <input id="elementary_graduated" v-model="form.elementary_graduated" type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                    </div>
                    <div>
                        <label for="elementary_address"
                            class="block text-xs font-medium text-gray-500 uppercase">Elementary
                            Address</label>
                        <input id="elementary_address" v-model="form.elementary_address" type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Enter school address" />
                    </div>

                    <!-- Senior High -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="senior_high_school"
                                class="block text-xs font-medium text-gray-500 uppercase">Senior
                                High School</label>
                            <input id="senior_high_school" v-model="form.senior_high_school" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter senior high school name" />
                        </div>
                        <div>
                            <label for="senior_high_strand"
                                class="block text-xs font-medium text-gray-500 uppercase">Strand</label>
                            <input id="senior_high_strand" v-model="form.senior_high_strand" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter strand" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="senior_high_address"
                                class="block text-xs font-medium text-gray-500 uppercase">Senior
                                High Address</label>
                            <input id="senior_high_address" v-model="form.senior_high_address" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter school address" />
                        </div>
                        <div>
                            <label for="senior_high_graduated"
                                class="block text-xs font-medium text-gray-500 uppercase">Date
                                Graduated</label>
                            <input id="senior_high_graduated" v-model="form.senior_high_graduated" type="date"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                    </div>
                    <div>
                        <label for="honors_received" class="block text-xs font-medium text-gray-500 uppercase">Honors
                            Received,
                            If Any</label>
                        <textarea id="honors_received" v-model="form.honors_received" rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Enter any honors received"></textarea>
                    </div>
                </div>
            </div>

            <!-- ======================== FAMILY INFORMATION ======================== -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">FAMILY INFORMATION</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Father -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Father's Name</label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <span class="text-xs text-gray-400">(Last Name)</span>
                                    <input v-model="form.father_last_name" type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">(First Name)</span>
                                    <input v-model="form.father_first_name" type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">(M.I.)</span>
                                    <input v-model="form.father_middle_initial" type="text" maxlength="10"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="father_occupation"
                                class="block text-xs font-medium text-gray-500 uppercase">Occupation/Trabajo</label>
                            <input id="father_occupation" v-model="form.father_occupation" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pt-4"
                                placeholder="Enter occupation" />
                        </div>
                    </div>

                    <!-- Mother -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Mother's Maiden
                                Name</label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <span class="text-xs text-gray-400">(Last Name)</span>
                                    <input v-model="form.mother_maiden_last_name" type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">(First Name)</span>
                                    <input v-model="form.mother_first_name" type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400">(M.I.)</span>
                                    <input v-model="form.mother_middle_initial" type="text" maxlength="10"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="mother_occupation"
                                class="block text-xs font-medium text-gray-500 uppercase">Occupation/Trabajo</label>
                            <input id="mother_occupation" v-model="form.mother_occupation" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pt-4"
                                placeholder="Enter occupation" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== EMERGENCY CONTACT ======================== -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">PERSON TO CONTACT IN CASE OF EMERGENCY</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_name"
                                class="block text-xs font-medium text-gray-500 uppercase">Name</label>
                            <input id="contact_name" v-model="form.contact_name" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter contact person name" />
                        </div>
                        <div>
                            <label for="relationship"
                                class="block text-xs font-medium text-gray-500 uppercase">Relationship</label>
                            <input id="relationship" v-model="form.relationship" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="e.g. Parent, Guardian, Sibling" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_number"
                                class="block text-xs font-medium text-gray-500 uppercase">Contact
                                Number</label>
                            <input id="contact_number" v-model="form.contact_number" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter contact number" />
                        </div>
                        <div>
                            <label for="contact_address"
                                class="block text-xs font-medium text-gray-500 uppercase">Address</label>
                            <input id="contact_address" v-model="form.contact_address" type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Enter contact address" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== SUBMIT BUTTON ======================== -->
            <div class="flex justify-center sm:justify-end">
                <PrimaryButton type="submit" :disabled="form.processing" class="px-8 w-full sm:w-auto">
                    {{ form.processing ? 'Saving...' : 'Save Profile' }}
                </PrimaryButton>
            </div>
        </form>
    </StudentLayout>
</template>
