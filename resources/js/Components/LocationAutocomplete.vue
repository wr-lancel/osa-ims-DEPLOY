<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search location...' },
    error: { type: String, default: '' },
    id: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const inputText  = ref(props.modelValue || '');
const results    = ref([]);
const isOpen     = ref(false);
const isLoading  = ref(false);
const highlighted = ref(-1);
const inputRef   = ref(null);

let debounceTimer = null;

// Keep input in sync if parent changes the value externally
watch(() => props.modelValue, (val) => {
    if (val !== inputText.value) inputText.value = val || '';
});

const onInput = (e) => {
    const val = e.target.value;
    inputText.value = val;
    emit('update:modelValue', val);
    highlighted.value = -1;

    clearTimeout(debounceTimer);

    if (!val || val.length < 3) {
        results.value = [];
        isOpen.value = false;
        return;
    }

    debounceTimer = setTimeout(() => fetchLocations(val), 400);
};

const fetchLocations = async (query) => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams({
            q: query,
            format: 'json',
            limit: '6',
            countrycodes: 'ph',
            addressdetails: '0',
        });

        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?${params}`,
            {
                headers: {
                    'Accept-Language': 'en',
                },
            }
        );

        const data = await res.json();
        results.value = data.map(item => item.display_name);
        isOpen.value = results.value.length > 0;
    } catch {
        results.value = [];
        isOpen.value = false;
    } finally {
        isLoading.value = false;
    }
};

const select = (location) => {
    inputText.value = location;
    emit('update:modelValue', location);
    results.value = [];
    isOpen.value = false;
    highlighted.value = -1;
};

const clear = () => {
    inputText.value = '';
    emit('update:modelValue', '');
    results.value = [];
    isOpen.value = false;
    inputRef.value?.focus();
};

const onBlur = () => {
    // Delay so mousedown on result fires first
    setTimeout(() => {
        isOpen.value = false;
        highlighted.value = -1;
    }, 150);
};

const onKeydown = (e) => {
    if (!isOpen.value) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            highlighted.value = Math.min(highlighted.value + 1, results.value.length - 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            highlighted.value = Math.max(highlighted.value - 1, 0);
            break;
        case 'Enter':
            e.preventDefault();
            if (highlighted.value >= 0 && results.value[highlighted.value]) {
                select(results.value[highlighted.value]);
            }
            break;
        case 'Escape':
            isOpen.value = false;
            highlighted.value = -1;
            break;
    }
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <input
                ref="inputRef"
                :id="id"
                type="text"
                :value="inputText"
                :placeholder="placeholder"
                autocomplete="off"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100 pr-8"
                :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': error }"
                @input="onInput"
                @blur="onBlur"
                @keydown="onKeydown"
            />

            <!-- Loading spinner -->
            <div v-if="isLoading" class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                <svg class="h-4 w-4 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>
            </div>

            <!-- Clear button -->
            <button
                v-else-if="inputText"
                type="button"
                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-200"
                @mousedown.prevent="clear"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Dropdown -->
        <ul
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-56 overflow-y-auto"
        >
            <li
                v-for="(location, idx) in results"
                :key="idx"
                class="px-4 py-2.5 text-sm cursor-pointer transition-colors"
                :class="highlighted === idx
                    ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300'
                    : 'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600'"
                @mousedown.prevent="select(location)"
                @mouseenter="highlighted = idx"
            >
                <!-- Pin icon -->
                <span class="inline-flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ location }}
                </span>
            </li>
        </ul>

        <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
