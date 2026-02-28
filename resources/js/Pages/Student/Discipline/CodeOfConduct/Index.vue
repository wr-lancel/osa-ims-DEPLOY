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
                <h2 class="text-2xl font-semibold text-gray-900">
                    Student Code of Conduct
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
            <p class="text-sm text-gray-600">
                Review the categories and topics below. Each item links to more detail.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="section in sections"
                    :key="section.id"
                    class="bg-white rounded-lg border border-gray-200 shadow-sm p-6"
                >
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ section.title }}</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="item in section.items"
                            :key="item.slug"
                            class="flex items-center"
                        >
                            <span class="text-gray-400 mr-2">•</span>
                            <Link
                                :href="route('student.discipline.code-of-conduct.show', item.slug)"
                                class="text-indigo-600 hover:text-indigo-900 text-sm"
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
