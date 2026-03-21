<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: 'Processing, please wait...',
    },
});

const isVisible = computed(() => props.show);
</script>

<template>
    <div v-show="isVisible" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

        <!-- Dialog Box -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg px-8 py-6 shadow-2xl flex flex-col items-center justify-center w-80 max-w-full transform transition-all">
            <!-- Spinner (Tailwind) -->
            <svg class="animate-spin -ml-1 mr-3 h-12 w-12 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white text-center animate-pulse">
                {{ message }}
            </h3>
            
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 text-center text-balance">
                Please do not close or refresh this page.
            </p>
        </div>
    </div>
</template>
