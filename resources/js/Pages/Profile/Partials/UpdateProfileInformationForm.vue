<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    profileName: {
        type: String,
        default: null,
    },
});

const user = usePage().props.auth.user;
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Your account's profile information.
            </p>
        </header>

        <div class="mt-6 space-y-6">
            <div>
                <InputLabel value="Name" />
                <p class="mt-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md">
                    {{ profileName ?? '—' }}
                </p>
            </div>

            <div>
                <InputLabel value="Email" />
                <p class="mt-1 px-3 py-2 text-sm text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md">
                    {{ user.email }}
                </p>
            </div>
        </div>
    </section>
</template>
