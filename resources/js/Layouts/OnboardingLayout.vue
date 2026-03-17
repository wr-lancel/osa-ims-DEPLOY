<script setup>
import { ref, watch, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const logout = () => {
    router.post(route('logout'));
};

const toast = ref({ show: false, type: 'success', title: '', message: '' });
const flash = computed(() => usePage().props.flash || {});

watch(flash, (f) => {
    if (f.success) {
        toast.value = { show: true, type: 'success', title: 'Success', message: f.success };
    } else if (f.error) {
        toast.value = { show: true, type: 'error', title: 'Error', message: f.error };
    } else if (f.warning) {
        toast.value = { show: true, type: 'warning', title: 'Warning', message: f.warning };
    }
}, { deep: true, immediate: true });
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

        <!-- Global Toast -->
        <NotificationDialog
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            @close="toast.show = false"
        />
    </div>
</template>
