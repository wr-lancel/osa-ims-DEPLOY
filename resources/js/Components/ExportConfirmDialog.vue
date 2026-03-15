<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: 'This will export the list according to the current filters. Do you want to continue?',
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40" @click="emit('cancel')" />

            <!-- Dialog -->
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 w-full max-w-sm mx-4 p-6">
                <!-- Icon -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Export PDF</h3>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">{{ message }}</p>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="emit('cancel')"
                        class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="emit('confirm')"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-sm font-medium text-white transition-colors"
                    >
                        Export
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
