<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Dashboard
            </h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <p class="text-gray-700">
                        You're logged in!
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
