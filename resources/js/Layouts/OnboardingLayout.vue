<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, router } from '@inertiajs/vue3';

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
        <!-- Top bar -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 flex items-center justify-between">
            <Link href="/" class="flex items-center gap-3">
                <ApplicationLogo class="h-10 w-10 fill-current text-gray-500 dark:text-gray-400" />
                <span class="text-sm font-semibold leading-tight text-gray-800 dark:text-gray-200 hidden sm:block">
                    Office of the Student Affairs and Services
                </span>
            </Link>
            <div class="flex items-center gap-4">
                <ThemeToggle />
                <button
                    @click="logout"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                >
                    Logout
                </button>
            </div>
        </div>

        <!-- Page Content -->
        <main class="py-8 px-4">
            <div class="mx-auto max-w-4xl">
                <slot />
            </div>
        </main>
    </div>
</template>
