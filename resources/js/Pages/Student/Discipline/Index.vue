<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Discipline Unit" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Discipline Unit
            </h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <p class="text-gray-700">
                        Discipline Unit - Coming Soon
                    </p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

