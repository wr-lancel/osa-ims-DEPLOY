<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
    positionsByOrg: {
        type: Object,
        default: () => ({}),
    },
    academicTerms: {
        type: Array,
        default: () => [],
    },
    defaultAcadId: {
        type: Number,
        default: null,
    },
    preSelectedOrgId: {
        type: Number,
        default: null,
    },
    candidacyOpen: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    org_id: props.preSelectedOrgId || '',
    position_id: '',
    acad_id: props.defaultAcadId || '',
    platform_statement: '',
    motivation: '',
});

const positionsForSelectedOrg = computed(() => {
    if (!form.org_id) return [];
    const key = String(form.org_id);
    return props.positionsByOrg[key] || [];
});

watch(
    () => form.org_id,
    () => {
        form.position_id = '';
    }
);
</script>

<template>

    <Head title="Submit Candidacy" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Submit Certificate of Candidacy
                </h2>
                <Link :href="route('student.organizations.candidacies.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm">
                    My Candidacies
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                    Submit your candidacy to run for a leadership position in an organization you belong to.
                </p>

                <form @submit.prevent="form.post(route('student.organizations.candidacy.store'))" class="space-y-6">
                    <!-- Candidacy closed warning -->
                    <div v-if="!candidacyOpen" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-amber-900">Candidacy Submissions Closed</p>
                                <p class="text-xs text-amber-700">Candidacy applications are not currently being
                                    accepted.</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="org_id" value="Organization" />
                            <select id="org_id" v-model="form.org_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-300': form.errors.org_id }" required
                                :disabled="!!preSelectedOrgId">
                                <option value="">Select organization</option>
                                <option v-for="org in organizations" :key="org.org_id" :value="org.org_id">
                                    {{ org.org_name }} ({{ org.org_code }})
                                </option>
                            </select>
                            <InputError :message="form.errors.org_id" />
                        </div>

                        <div>
                            <InputLabel for="acad_id" value="Term / Semester" />
                            <select id="acad_id" v-model="form.acad_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-300': form.errors.acad_id }" required>
                                <option value="">Select term</option>
                                <option v-for="term in academicTerms" :key="term.calendar_id" :value="term.calendar_id">
                                    {{ term.display_label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.acad_id" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="position_id" value="Position" />
                        <select id="position_id" v-model="form.position_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-300': form.errors.position_id }" required :disabled="!form.org_id">
                            <option value="">Select position</option>
                            <option v-for="pos in positionsForSelectedOrg" :key="pos.position_id"
                                :value="pos.position_id">
                                {{ pos.position_name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.position_id" />
                    </div>

                    <div>
                        <InputLabel for="platform_statement" value="Platform Statement" />
                        <textarea id="platform_statement" v-model="form.platform_statement" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-300': form.errors.platform_statement }"
                            placeholder="Your platform and goals..." />
                        <InputError :message="form.errors.platform_statement" />
                    </div>

                    <div>
                        <InputLabel for="motivation" value="Motivation / Why you want to run" />
                        <textarea id="motivation" v-model="form.motivation" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                            :class="{ 'border-red-300': form.errors.motivation }"
                            placeholder="Why do you want to run for this position?" />
                        <InputError :message="form.errors.motivation" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <Link :href="route('student.organizations.candidacies.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton type="submit" :disabled="form.processing || !candidacyOpen">
                            {{ form.processing ? 'Submitting...' : 'Submit Candidacy' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </StudentLayout>
</template>
