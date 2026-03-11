<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Code of Conduct" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Student Code of Conduct
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm"
                >
                    ← Discipline Unit
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Review the categories and topics below. Each item links to more detail.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="section in sections"
                    :key="section.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                >
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ section.title }}</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="item in section.items"
                            :key="item.slug"
                            class="flex items-center"
                        >
                            <span class="text-gray-400 dark:text-gray-500 dark:text-gray-400 mr-2">•</span>
                            <Link
                                :href="route('student.discipline.code-of-conduct.show', item.slug)"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
