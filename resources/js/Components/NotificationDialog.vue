<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    type: { type: String, default: 'success' }, // success, error, warning, confirm
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    autoClose: { type: Boolean, default: true },
    autoCloseDelay: { type: Number, default: 2500 },
});

const emit = defineEmits(['close', 'confirm']);

let autoCloseTimer = null;

watch(() => props.show, (isShowing) => {
    if (autoCloseTimer) {
        clearTimeout(autoCloseTimer);
        autoCloseTimer = null;
    }
    if (isShowing && props.autoClose && props.type !== 'confirm') {
        autoCloseTimer = setTimeout(() => {
            emit('close');
        }, props.autoCloseDelay);
    }
});

const iconConfig = computed(() => {
    switch (props.type) {
        case 'success':
            return {
                bgColor: 'bg-green-100 dark:bg-green-900/40',
                iconColor: 'text-green-600 dark:text-green-400',
                path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            };
        case 'error':
            return {
                bgColor: 'bg-red-100 dark:bg-red-900/40',
                iconColor: 'text-red-600 dark:text-red-400',
                path: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            };
        case 'warning':
            return {
                bgColor: 'bg-amber-100 dark:bg-amber-900/40',
                iconColor: 'text-amber-600 dark:text-amber-400',
                path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            };
        case 'confirm':
            return {
                bgColor: 'bg-blue-100 dark:bg-blue-900/40',
                iconColor: 'text-blue-600 dark:text-blue-400',
                path: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            };
        default:
            return {
                bgColor: 'bg-gray-100 dark:bg-gray-700',
                iconColor: 'text-gray-600 dark:text-gray-300',
                path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            };
    }
});

const titleColor = computed(() => {
    switch (props.type) {
        case 'success': return 'text-green-900 dark:text-green-300';
        case 'error': return 'text-red-900 dark:text-red-300';
        case 'warning': return 'text-amber-900 dark:text-amber-300';
        case 'confirm': return 'text-gray-900 dark:text-white';
        default: return 'text-gray-900 dark:text-white';
    }
});

const confirmButtonClass = computed(() => {
    if (props.type === 'error' || props.confirmLabel?.toLowerCase().includes('delete') || props.confirmLabel?.toLowerCase().includes('remove')) {
        return 'bg-red-600 hover:bg-red-700 focus:ring-red-500';
    }
    return 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" @click="$emit('close')" />

                <!-- Dialog -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="show"
                            class="relative w-full max-w-md transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-2xl transition-all"
                        >
                            <!-- Content -->
                            <div class="p-6">
                                <div class="flex items-start gap-4">
                                    <!-- Icon -->
                                    <div
                                        class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full"
                                        :class="iconConfig.bgColor"
                                    >
                                        <svg
                                            class="h-6 w-6"
                                            :class="iconConfig.iconColor"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                :d="iconConfig.path"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Text -->
                                    <div class="flex-1 min-w-0">
                                        <h3
                                            class="text-lg font-semibold leading-6"
                                            :class="titleColor"
                                        >
                                            {{ title }}
                                        </h3>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">
                                            {{ message }}
                                        </p>
                                    </div>

                                    <!-- Close button (non-confirm) -->
                                    <button
                                        v-if="type !== 'confirm'"
                                        type="button"
                                        @click="$emit('close')"
                                        class="flex-shrink-0 rounded-md p-1.5 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                v-if="type === 'confirm'"
                                class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3"
                            >
                                <button
                                    type="button"
                                    @click="$emit('close')"
                                    class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                                >
                                    {{ cancelLabel }}
                                </button>
                                <button
                                    type="button"
                                    @click="$emit('confirm')"
                                    class="inline-flex items-center rounded-lg border border-transparent px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                                    :class="confirmButtonClass"
                                >
                                    {{ confirmLabel }}
                                </button>
                            </div>

                            <!-- Progress bar for auto-close -->
                            <div v-if="type !== 'confirm' && autoClose" class="h-1 w-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div
                                    class="h-full transition-all ease-linear"
                                    :class="{
                                        'bg-green-500': type === 'success',
                                        'bg-red-500': type === 'error',
                                        'bg-amber-500': type === 'warning',
                                    }"
                                    :style="{ animation: `shrink ${autoCloseDelay}ms linear forwards` }"
                                />
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
</style>
