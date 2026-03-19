<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    slug: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    content: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Head :title="title" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ title }}
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm self-start sm:self-auto"
                >
                    ← Discipline Unit
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ content }}</p>
            </div>
        </div>
    </StudentLayout>
</template>
