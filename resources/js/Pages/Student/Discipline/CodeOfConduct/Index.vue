<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});

const sectionNumbers = {
    'academic-integrity': 'I',
    'conduct-and-behavior': 'II',
    'prohibited-activities': 'III',
    'property-and-security': 'IV',
    'campus-rules': 'V',
    'special-laws': 'VI',
    'sanctions-and-process': 'VII',
};
</script>

<template>
    <Head title="Code of Conduct" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Student Code of Conduct
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm self-start sm:self-auto"
                >
                    &larr; Discipline Unit
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Review the student code of conduct below. Click on any topic to learn more about the specific rules, definitions, and applicable sanctions.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div
                    v-for="section in sections"
                    :key="section.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                >
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold flex-shrink-0">
                            {{ sectionNumbers[section.id] || '#' }}
                        </span>
                        {{ section.title }}
                    </h3>
                    <ul class="space-y-2">
                        <li
                            v-for="item in section.items"
                            :key="item.slug"
                            class="flex items-start"
                        >
                            <span class="text-gray-400 dark:text-gray-500 mr-2 mt-0.5">•</span>
                            <Link
                                :href="route('student.discipline.code-of-conduct.show', item.slug)"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm hover:underline"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                Reference: Student Handbook 2024
            </p>
        </div>
    </StudentLayout>
</template>
