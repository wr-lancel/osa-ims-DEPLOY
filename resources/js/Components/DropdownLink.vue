<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm font-medium text-gray-700 dark:text-gray-200 transition-colors duration-150 ease-in-out hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white focus:bg-gray-50 dark:focus:bg-gray-700 focus:text-gray-900 dark:focus:text-white focus:outline-none first:rounded-t-lg last:rounded-b-lg"
    >
        <slot />
    </Link>
</template>
