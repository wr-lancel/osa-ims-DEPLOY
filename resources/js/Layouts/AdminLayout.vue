<script setup>
import { ref, watch, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const mobileMenuOpen = ref(false);

// Global flash-to-toast
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
    <div class="bg-gray-100 dark:bg-gray-900 min-h-screen text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar -->
            <AdminSidebar :mobile-open="mobileMenuOpen" @close="mobileMenuOpen = false" />

            <!-- Main Content -->
            <div class="w-full flex flex-col min-w-0">
                <!-- Top Navigation -->
                <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16">
                            <div class="flex items-center gap-3">
                                <!-- Hamburger (mobile only) -->
                                <button @click="mobileMenuOpen = true"
                                    class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                    aria-label="Open navigation menu">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>

                                <Link :href="route('admin.dashboard')" class="flex items-center">
                                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                    <span
                                        class="ms-3 hidden sm:inline whitespace-nowrap text-sm font-semibold tracking-tight text-gray-800 dark:text-gray-200">
                                        Office of the Student Affairs and Services
                                    </span>
                                </Link>
                            </div>

                            <!-- Settings Dropdown & Theme Toggle -->
                            <div class="flex items-center gap-4">
                                <!-- Theme Toggle -->
                                <ThemeToggle />

                                <div class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button type="button"
                                                    class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none">
                                                    <span class="hidden sm:inline max-w-[120px] truncate">
                                                        {{ $page.props.auth.user?.display_name ||
                                                        $page.props.auth.user?.email }}
                                                    </span>
                                                    <span class="sm:hidden">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </span>

                                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('profile.edit')">
                                                Profile
                                            </DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button">
                                                Log Out
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page Heading -->
                <header class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-900/50 transition-colors duration-200" v-if="$slots.header">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 text-gray-900 dark:text-white">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1">
                    <div class="py-6">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>
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
