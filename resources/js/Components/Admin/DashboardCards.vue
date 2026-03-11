<script setup>
const props = defineProps({
    cards: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const colorClasses = {
    blue: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100',
    yellow: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-900 dark:text-yellow-100',
    green: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-900 dark:text-green-100',
    red: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-900 dark:text-red-100',
    gray: 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100',
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
            v-for="(card, index) in cards"
            :key="index"
            class="rounded-lg border shadow-sm p-6"
            :class="colorClasses[card.color] || colorClasses.gray"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium opacity-75">
                        {{ card.title }}
                    </p>
                    <p class="text-3xl font-bold mt-2">
                        {{ card.value }}
                    </p>
                </div>
                <div
                    v-if="card.icon"
                    class="h-12 w-12 rounded-lg flex items-center justify-center opacity-20"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="card.icon"
                        />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>

