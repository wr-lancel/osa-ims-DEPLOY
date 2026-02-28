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
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    {{ title }}
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                >
                    ← Discipline Unit
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ content }}</p>
            </div>
        </div>
    </StudentLayout>
</template>
