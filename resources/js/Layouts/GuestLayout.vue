<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 dark:bg-gray-900 pt-6 sm:justify-center sm:pt-0 transition-colors duration-200 relative">
        <!-- Theme Toggle Absolute Top Right -->
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6">
            <ThemeToggle />
        </div>

        <div>
<<<<<<< Updated upstream
            <Link href="/" class="flex items-center gap-3">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500 dark:text-gray-400" />
                <span class="max-w-[16rem] text-base font-semibold leading-tight tracking-tight text-gray-800 dark:text-gray-200">
                    Office of the Student Affairs
                </span>
=======
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500 dark:text-gray-400" />
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white dark:bg-gray-800 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg border border-transparent dark:border-gray-700 transition-colors duration-200">
            <slot />
        </div>
    </div>
</template>
