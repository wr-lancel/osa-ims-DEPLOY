<script setup>
import { computed } from 'vue';

const props = defineProps({
    column: { type: String, required: true },
    label: { type: String, required: true },
    currentSort: { type: String, default: '' },
    currentDir: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort']);

const isActive = computed(() => props.currentSort === props.column);

const toggle = () => {
    const dir = isActive.value && props.currentDir === 'asc' ? 'desc' : 'asc';
    emit('sort', { column: props.column, dir });
};
</script>

<template>
    <th
        @click="toggle"
        class="px-4 py-3 font-semibold whitespace-nowrap cursor-pointer select-none group transition-colors hover:bg-gray-100 dark:hover:bg-gray-700/50"
    >
        <div class="flex items-center gap-1.5">
            <span>{{ label }}</span>
            <span class="flex flex-col gap-px flex-shrink-0">
                <!-- Up arrow -->
                <svg
                    class="w-2.5 h-2.5 transition-colors"
                    :class="isActive && currentDir === 'asc' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-300 dark:text-gray-600 group-hover:text-gray-400'"
                    fill="currentColor" viewBox="0 0 24 24"
                >
                    <path d="M12 4l8 8H4z" />
                </svg>
                <!-- Down arrow -->
                <svg
                    class="w-2.5 h-2.5 transition-colors"
                    :class="isActive && currentDir === 'desc' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-300 dark:text-gray-600 group-hover:text-gray-400'"
                    fill="currentColor" viewBox="0 0 24 24"
                >
                    <path d="M12 20l-8-8h16z" />
                </svg>
            </span>
        </div>
    </th>
</template>
