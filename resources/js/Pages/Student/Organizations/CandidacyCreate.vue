<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    organizations: { type: Array, default: () => [] },
    positionsByOrg: { type: Object, default: () => ({}) },
    academicTerms: { type: Array, default: () => [] },
    defaultAcadId: { type: Number, default: null },
    preSelectedOrgId: { type: Number, default: null },
    candidacyOpen: { type: Boolean, default: false },
    studentInfo: { type: Object, default: () => ({}) },
});

const form = useForm({
    org_id: props.preSelectedOrgId || '',
    position_id: '',
    acad_id: props.defaultAcadId || '',
    party_affiliation: '',
    unit_load: '',
    platform_statement: '',
    motivation: '',
});

const declarationChecks = ref([false, false, false, false]);

const declarationTexts = [
    'I am physically fit as certified by the College Physician.',
    'If elected as an officer in our Major Organization, I will not run for any lower or higher position in any organization in this institution.',
    'I have read the election guidelines/election code and the CBL of 2022.',
    'I fully understand the task expected of me in the position I am applying for.',
];

const allDeclarationsChecked = computed(() => declarationChecks.value.every(d => d));

const positionsForSelectedOrg = computed(() => {
    if (!form.org_id) return [];
    return props.positionsByOrg[String(form.org_id)] || [];
});

watch(() => form.org_id, () => { form.position_id = ''; });

function submit() {
    if (!allDeclarationsChecked.value) return;
    form.post(route('student.organizations.candidacy.store'));
}

const ordinalYear = (level) => {
    if (!level) return '—';
    const map = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year', 5: '5th Year' };
    return map[level] || `${level}th Year`;
};
</script>

<template>
    <Head title="Submit Candidacy" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Certificate of Candidacy
                </h2>
                <Link
                    :href="route('student.organizations.candidacies.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    My Candidacies
                </Link>
            </div>
        </template>

        <div class="space-y-4">

            <!-- Candidacy closed warning -->
            <div v-if="!candidacyOpen" class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-amber-100 dark:bg-amber-800 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">Candidacy Submissions Closed</p>
                        <p class="text-xs text-amber-700 dark:text-amber-400">Candidacy applications are not currently being accepted.</p>
                    </div>
                </div>
            </div>

            <!-- Form Document -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                <!-- Document Header -->
                <div class="text-center border-b border-gray-200 dark:border-gray-700 px-6 py-5 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                        Office of the Student Affairs
                    </p>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white mt-1 uppercase tracking-wide">
                        Application for Supreme Student Council
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 italic">Certificate of Candidacy</p>
                </div>

                <form @submit.prevent="submit" class="divide-y divide-gray-200 dark:divide-gray-700">

                    <!-- Section 1: Personal Information -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-6 gap-4">
                            <!-- Name -->
                            <div class="col-span-6 sm:col-span-4">
                                <InputLabel value="Name (Surname, First, M.I.)" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.name || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <InputLabel value="Age" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.age || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <InputLabel value="Student No." />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.student_number || '—' }}
                                </div>
                            </div>

                            <!-- Course & Year -->
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel value="Course" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.course || '—' }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-2">
                                <InputLabel value="Current Year" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ ordinalYear(studentInfo.year_level) }}
                                </div>
                            </div>
                            <div class="col-span-6 sm:col-span-1">
                                <InputLabel for="unit_load" value="No. Unit Load" />
                                <input
                                    id="unit_load"
                                    type="number"
                                    min="1"
                                    max="99"
                                    v-model="form.unit_load"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.unit_load }"
                                    placeholder="e.g. 21"
                                />
                                <InputError :message="form.errors.unit_load" />
                            </div>

                            <!-- Address -->
                            <div class="col-span-6">
                                <InputLabel value="Present / Home Address" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.address || '—' }}
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel value="Cellphone Number" />
                                <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600 text-sm text-gray-900 dark:text-gray-100 min-h-[38px]">
                                    {{ studentInfo.phone || '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Candidacy Details -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Candidacy Details
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Organization -->
                            <div>
                                <InputLabel for="org_id" value="Organization" />
                                <select
                                    id="org_id"
                                    v-model="form.org_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.org_id }"
                                    required
                                    :disabled="!!preSelectedOrgId"
                                >
                                    <option value="">Select organization</option>
                                    <option v-for="org in organizations" :key="org.org_id" :value="org.org_id">
                                        {{ org.org_name }} ({{ org.org_code }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.org_id" />
                            </div>

                            <!-- School Year / Term -->
                            <div>
                                <InputLabel for="acad_id" value="School Year / Term" />
                                <select
                                    id="acad_id"
                                    v-model="form.acad_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.acad_id }"
                                    required
                                >
                                    <option value="">Select term</option>
                                    <option v-for="term in academicTerms" :key="term.calendar_id" :value="term.calendar_id">
                                        {{ term.display_label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.acad_id" />
                            </div>

                            <!-- Position Applied -->
                            <div>
                                <InputLabel for="position_id" value="Position Applied" />
                                <select
                                    id="position_id"
                                    v-model="form.position_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.position_id }"
                                    required
                                    :disabled="!form.org_id"
                                >
                                    <option value="">Select position</option>
                                    <option v-for="pos in positionsForSelectedOrg" :key="pos.position_id" :value="pos.position_id">
                                        {{ pos.position_name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.position_id" />
                            </div>

                            <!-- Political Party Affiliation -->
                            <div>
                                <InputLabel for="party_affiliation" value="Political Party Affiliation" />
                                <input
                                    id="party_affiliation"
                                    type="text"
                                    v-model="form.party_affiliation"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.party_affiliation }"
                                    placeholder="e.g. Independent / Party name"
                                />
                                <InputError :message="form.errors.party_affiliation" />
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Declarations -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">
                            Declarations
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            I further declare the following. All items must be acknowledged before submitting.
                        </p>
                        <div class="space-y-3">
                            <label
                                v-for="(text, index) in declarationTexts"
                                :key="index"
                                class="flex items-start gap-3 cursor-pointer group"
                            >
                                <input
                                    type="checkbox"
                                    v-model="declarationChecks[index]"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300 leading-snug">
                                    <span class="font-medium text-gray-500 dark:text-gray-400 mr-1">{{ index + 1 }}.</span>
                                    {{ text }}
                                </span>
                            </label>
                        </div>
                        <p v-if="!allDeclarationsChecked && form.wasSuccessful === false" class="mt-3 text-xs text-red-600 dark:text-red-400">
                            You must acknowledge all declarations before submitting.
                        </p>
                    </div>

                    <!-- Section 4: Platform & Motivation -->
                    <div class="px-6 py-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">
                            Platform & Motivation
                        </h3>
                        <div class="space-y-5">
                            <div>
                                <InputLabel for="platform_statement" value="Platform Statement" />
                                <textarea
                                    id="platform_statement"
                                    v-model="form.platform_statement"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.platform_statement }"
                                    placeholder="State your platform and goals for the position..."
                                />
                                <InputError :message="form.errors.platform_statement" />
                            </div>
                            <div>
                                <InputLabel for="motivation" value="Motivation / Why you want to run" />
                                <textarea
                                    id="motivation"
                                    v-model="form.motivation"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                    :class="{ 'border-red-300': form.errors.motivation }"
                                    placeholder="Why do you want to run for this position?"
                                />
                                <InputError :message="form.errors.motivation" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer Note -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            <span class="font-semibold">Note:</span>
                            Misrepresentation of facts or tampering with / falsifying official records will be a basis for disqualification as a candidate.
                            After filing this application, no candidate can change party affiliation before or during the election period.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 flex items-center justify-between gap-3 bg-white dark:bg-gray-800">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="!allDeclarationsChecked" class="text-amber-600 dark:text-amber-400 font-medium">
                                Please acknowledge all declarations above.
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link :href="route('student.organizations.candidacies.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing || !candidacyOpen || !allDeclarationsChecked"
                            >
                                {{ form.processing ? 'Submitting...' : 'Submit Candidacy' }}
                            </PrimaryButton>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <LoadingOverlay :show="form.processing" message="Submitting... Please wait." />
    </StudentLayout>
</template>
