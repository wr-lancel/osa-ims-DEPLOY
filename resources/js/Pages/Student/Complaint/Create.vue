<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    category: '',
    subject: '',
    description: '',
    incident_date: new Date().toISOString().split('T')[0],
    location: '',
    respondent_name: '',
    anonymous: false,
});

const submit = () => {
    form.post(route('student.discipline.complaints.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Submit Complaint" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Submit Complaint
                </h2>
                <Link
                    :href="route('student.discipline.complaints.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    My Complaints
                </Link>
            </div>
        </template>

        <div class="space-y-6 max-w-3xl">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="category" value="Complaint Category *" />
                        <select
                            id="category"
                            v-model="form.category"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                            required
                        >
                            <option value="">Select category</option>
                            <option
                                v-for="c in categories"
                                :key="c.value"
                                :value="c.value"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="subject" value="Subject / Title *" />
                        <TextInput
                            id="subject"
                            v-model="form.subject"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Brief subject of the complaint"
                            required
                        />
                        <InputError :message="form.errors.subject" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Detailed Description *" />
                        <Textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full"
                            rows="5"
                            placeholder="Describe what happened in detail."
                            required
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="incident_date" value="Date of Incident *" />
                            <TextInput
                                id="incident_date"
                                v-model="form.incident_date"
                                type="date"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.incident_date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="location" value="Location" />
                            <TextInput
                                id="location"
                                v-model="form.location"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Where it occurred"
                            />
                            <InputError :message="form.errors.location" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="respondent_name" value="Respondent (optional)" />
                        <TextInput
                            id="respondent_name"
                            v-model="form.respondent_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Name of the person being complained about"
                        />
                        <InputError :message="form.errors.respondent_name" class="mt-1" />
                    </div>

                    <div class="flex items-center">
                        <Checkbox v-model:checked="form.anonymous" name="anonymous" />
                        <InputLabel for="anonymous" value="Submit anonymously (placeholder)" class="ml-2" />
                    </div>

                    <div class="border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Attachments (placeholder — uploads not yet supported)</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            Submit Complaint
                        </PrimaryButton>
                        <Link
                            :href="route('student.discipline.complaints.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:bg-gray-900"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
        <LoadingOverlay :show="form.processing" message="Submitting... Please wait." />
    </StudentLayout>
</template>
