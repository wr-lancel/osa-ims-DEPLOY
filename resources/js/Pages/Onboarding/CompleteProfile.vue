<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import LocationAutocomplete from '@/Components/LocationAutocomplete.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    student: { type: Object, default: null },
    profile: { type: Object, default: null },
    educationalBackground: { type: Object, default: null },
    familyInfo: { type: Object, default: null },
    emergencyContact: { type: Object, default: null },
    enrollmentHistory: { type: Array, default: () => [] },
});

const form = useForm({
    // Student basic info
    phone:      props.student?.phone || '',
    email:      props.student?.email || '',
    address:    props.student?.address || '',
    birth_date: props.student?.birth_date || '',

    // Personal profile
    birth_place:        props.profile?.birth_place || '',
    gender:             props.profile?.gender || '',
    citizenship:        props.profile?.citizenship || '',
    civil_status:       props.profile?.civil_status || 'single',
    spouse_name:        props.profile?.spouse_name || '',
    is_single_parent:   props.profile?.is_single_parent ?? false,
    has_disability:     props.profile?.has_disability ?? false,
    disability_details: props.profile?.disability_details || '',
    is_employed:        props.profile?.is_employed ?? false,
    company_name:       props.profile?.company_name || '',

    // Educational background
    elementary_school:     props.educationalBackground?.elementary_school || '',
    elementary_address:    props.educationalBackground?.elementary_address || '',
    elementary_graduated:  props.educationalBackground?.elementary_graduated || '',
    senior_high_school:    props.educationalBackground?.senior_high_school || '',
    senior_high_strand:    props.educationalBackground?.senior_high_strand || '',
    senior_high_address:   props.educationalBackground?.senior_high_address || '',
    senior_high_graduated: props.educationalBackground?.senior_high_graduated || '',
    honors_received:       props.educationalBackground?.honors_received || '',

    // Family info
    father_last_name:        props.familyInfo?.father_last_name || '',
    father_first_name:       props.familyInfo?.father_first_name || '',
    father_middle_initial:   props.familyInfo?.father_middle_initial || '',
    father_occupation:       props.familyInfo?.father_occupation || '',
    mother_maiden_last_name: props.familyInfo?.mother_maiden_last_name || '',
    mother_first_name:       props.familyInfo?.mother_first_name || '',
    mother_middle_initial:   props.familyInfo?.mother_middle_initial || '',
    mother_occupation:       props.familyInfo?.mother_occupation || '',

    // Emergency contact
    contact_name:    props.emergencyContact?.contact_name || '',
    relationship:    props.emergencyContact?.relationship || '',
    contact_number:  props.emergencyContact?.contact_number || '',
    contact_address: props.emergencyContact?.contact_address || '',
});

const computedAge = computed(() => {
    if (!form.birth_date) return 'N/A';
    const today = new Date();
    const birth = new Date(form.birth_date);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age;
});

const submit = () => {
    form.post(route('onboarding.complete-profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Complete Your Profile" />

    <OnboardingLayout>
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Complete Your Profile</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Please fill in all required fields before proceeding to the system.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- ======================== STUDENT'S INFORMATION ======================== -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">STUDENT'S INFORMATION</h3>
                </div>
                <div class="p-6">
                    <!-- Current Course (read-only) -->
                    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current Course</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ enrollmentHistory[0]?.course_name || 'N/A' }}
                                    <span v-if="enrollmentHistory[0]?.course_code" class="text-gray-500 dark:text-gray-400">
                                        ({{ enrollmentHistory[0]?.course_code }})
                                    </span>
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Year Level</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ enrollmentHistory[0]?.year_level || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Name (read-only) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Last Name</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-medium border-b border-gray-300 dark:border-gray-600 pb-1">{{ student?.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">First Name</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-medium border-b border-gray-300 dark:border-gray-600 pb-1">{{ student?.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Middle Name</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white font-medium border-b border-gray-300 dark:border-gray-600 pb-1">{{ student?.middle_name || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label for="address" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Address <span class="text-red-500">*</span>
                        </label>
                        <LocationAutocomplete
                            id="address"
                            v-model="form.address"
                            placeholder="e.g. Brgy. San Isidro, Concepcion, Tarlac"
                            :error="form.errors.address"
                        />
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="phone" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Mobile Number <span class="text-red-500">*</span>
                            </label>
                            <input id="phone" v-model="form.phone" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="e.g. 09123456789" />
                            <InputError class="mt-1" :message="form.errors.phone" />
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input id="email" v-model="form.email" type="email"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="e.g. juan.delacruz@email.com" />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>
                    </div>

                    <!-- Birth Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="birth_date" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Birth Date <span class="text-red-500">*</span>
                            </label>
                            <input id="birth_date" v-model="form.birth_date" type="date"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                            <InputError class="mt-1" :message="form.errors.birth_date" />
                        </div>
                        <div>
                            <label for="birth_place" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Birth Place <span class="text-red-500">*</span>
                            </label>
                            <input id="birth_place" v-model="form.birth_place" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="e.g. Tarlac City, Tarlac" />
                            <InputError class="mt-1" :message="form.errors.birth_place" />
                        </div>
                    </div>

                    <!-- Age, Gender, Citizenship -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Age</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white border-b border-gray-300 dark:border-gray-600 pb-1">{{ computedAge }}</p>
                        </div>
                        <div>
                            <label for="gender" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Gender <span class="text-red-500">*</span>
                            </label>
                            <select id="gender" v-model="form.gender"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.gender" />
                        </div>
                        <div>
                            <label for="citizenship" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Citizenship <span class="text-red-500">*</span>
                            </label>
                            <input id="citizenship" v-model="form.citizenship" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="e.g. Filipino" />
                            <InputError class="mt-1" :message="form.errors.citizenship" />
                        </div>
                    </div>

                    <!-- Civil Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="civil_status" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Civil Status <span class="text-red-500">*</span>
                            </label>
                            <select id="civil_status" v-model="form.civil_status"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100">
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                            </select>
                        </div>
                        <div v-if="form.civil_status === 'married'">
                            <label for="spouse_name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name of Spouse</label>
                            <input id="spouse_name" v-model="form.spouse_name" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter spouse name" />
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Single Parent?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_single_parent" :value="true" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_single_parent" :value="false" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">No</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">With Disability?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.has_disability" :value="true" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.has_disability" :value="false" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">No</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Employed?</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_employed" :value="true" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="form.is_employed" :value="false" class="form-radio text-indigo-600" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">No</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.has_disability" class="mb-4">
                        <label for="disability_details" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Disability Details</label>
                        <input id="disability_details" v-model="form.disability_details" type="text"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Specify disability" />
                    </div>

                    <div v-if="form.is_employed" class="mb-4">
                        <label for="company_name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Company Name</label>
                        <input id="company_name" v-model="form.company_name" type="text"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Enter company name" />
                    </div>

                    <!-- Student Number (read-only) -->
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Student Number</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white font-bold">{{ student?.student_number }}</p>
                    </div>
                </div>
            </div>

            <!-- ======================== EDUCATIONAL BACKGROUND ======================== -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">EDUCATIONAL BACKGROUND</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="elementary_school" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Elementary School <span class="text-red-500">*</span>
                            </label>
                            <input id="elementary_school" v-model="form.elementary_school" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter elementary school name" />
                            <InputError class="mt-1" :message="form.errors.elementary_school" />
                        </div>
                        <div>
                            <label for="elementary_graduated" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Date Graduated <span class="text-red-500">*</span>
                            </label>
                            <input id="elementary_graduated" v-model="form.elementary_graduated" type="date"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                            <InputError class="mt-1" :message="form.errors.elementary_graduated" />
                        </div>
                    </div>
                    <div>
                        <label for="elementary_address" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Elementary Address <span class="text-red-500">*</span>
                        </label>
                        <LocationAutocomplete
                            id="elementary_address"
                            v-model="form.elementary_address"
                            placeholder="Enter school address"
                            :error="form.errors.elementary_address"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="senior_high_school" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Senior High School <span class="text-red-500">*</span>
                            </label>
                            <input id="senior_high_school" v-model="form.senior_high_school" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter senior high school name" />
                            <InputError class="mt-1" :message="form.errors.senior_high_school" />
                        </div>
                        <div>
                            <label for="senior_high_strand" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Strand <span class="text-red-500">*</span>
                            </label>
                            <input id="senior_high_strand" v-model="form.senior_high_strand" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter strand" />
                            <InputError class="mt-1" :message="form.errors.senior_high_strand" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="senior_high_address" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Senior High Address <span class="text-red-500">*</span>
                            </label>
                            <LocationAutocomplete
                                id="senior_high_address"
                                v-model="form.senior_high_address"
                                placeholder="Enter school address"
                                :error="form.errors.senior_high_address"
                            />
                        </div>
                        <div>
                            <label for="senior_high_graduated" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Date Graduated <span class="text-red-500">*</span>
                            </label>
                            <input id="senior_high_graduated" v-model="form.senior_high_graduated" type="date"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                            <InputError class="mt-1" :message="form.errors.senior_high_graduated" />
                        </div>
                    </div>
                    <div>
                        <label for="honors_received" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Honors Received, If Any</label>
                        <textarea id="honors_received" v-model="form.honors_received" rows="2"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Enter any honors received"></textarea>
                    </div>
                </div>
            </div>

            <!-- ======================== FAMILY INFORMATION ======================== -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">FAMILY INFORMATION</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Father -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                Father's Name <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(Last Name)</span>
                                    <input v-model="form.father_last_name" type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(First Name)</span>
                                    <input v-model="form.father_first_name" type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(M.I.)</span>
                                    <input v-model="form.father_middle_initial" type="text" maxlength="10"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.father_last_name || form.errors.father_first_name" />
                        </div>
                        <div>
                            <label for="father_occupation" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Occupation <span class="text-red-500">*</span>
                            </label>
                            <input id="father_occupation" v-model="form.father_occupation" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pt-4 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter occupation" />
                            <InputError class="mt-1" :message="form.errors.father_occupation" />
                        </div>
                    </div>

                    <!-- Mother -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">
                                Mother's Maiden Name <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(Last Name)</span>
                                    <input v-model="form.mother_maiden_last_name" type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(First Name)</span>
                                    <input v-model="form.mother_first_name" type="text"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">(M.I.)</span>
                                    <input v-model="form.mother_middle_initial" type="text" maxlength="10"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100" />
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.mother_maiden_last_name || form.errors.mother_first_name" />
                        </div>
                        <div>
                            <label for="mother_occupation" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Occupation <span class="text-red-500">*</span>
                            </label>
                            <input id="mother_occupation" v-model="form.mother_occupation" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pt-4 dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter occupation" />
                            <InputError class="mt-1" :message="form.errors.mother_occupation" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== EMERGENCY CONTACT ======================== -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="bg-gray-800 px-6 py-3">
                    <h3 class="text-lg font-semibold text-white italic">PERSON TO CONTACT IN CASE OF EMERGENCY</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input id="contact_name" v-model="form.contact_name" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter contact person name" />
                            <InputError class="mt-1" :message="form.errors.contact_name" />
                        </div>
                        <div>
                            <label for="relationship" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <input id="relationship" v-model="form.relationship" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="e.g. Parent, Guardian, Sibling" />
                            <InputError class="mt-1" :message="form.errors.relationship" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_number" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Contact Number <span class="text-red-500">*</span>
                            </label>
                            <input id="contact_number" v-model="form.contact_number" type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                placeholder="Enter contact number" />
                            <InputError class="mt-1" :message="form.errors.contact_number" />
                        </div>
                        <div>
                            <label for="contact_address" class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Address <span class="text-red-500">*</span>
                            </label>
                            <LocationAutocomplete
                                id="contact_address"
                                v-model="form.contact_address"
                                placeholder="Enter contact address"
                                :error="form.errors.contact_address"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <PrimaryButton type="submit" :disabled="form.processing" class="px-10 w-full sm:w-auto justify-center">
                    {{ form.processing ? 'Saving...' : 'Complete Profile & Continue' }}
                </PrimaryButton>
            </div>
        </form>

        <LoadingOverlay :show="form.processing" message="Saving your profile... Please wait." />
    </OnboardingLayout>
</template>
