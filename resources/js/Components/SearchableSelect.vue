<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    labelKey: {
        type: String,
        default: 'label',
    },
    valueKey: {
        type: String,
        default: 'value',
    },
    placeholder: {
        type: String,
        default: 'Search...',
    },
    error: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    maxResults: {
        type: Number,
        default: 20,
    },
});

const emit = defineEmits(['update:modelValue']);

const searchText = ref('');
const isOpen = ref(false);
const inputRef = ref(null);
const dropdownRef = ref(null);
const highlightedIndex = ref(-1);

// Find the currently selected option to display its label
const selectedOption = computed(() => {
    if (!props.modelValue) return null;
    return props.options.find(o => String(o[props.valueKey]) === String(props.modelValue));
});

// Filter options based on search text
const filteredOptions = computed(() => {
    if (!searchText.value) return props.options.slice(0, props.maxResults);
    const search = searchText.value.toLowerCase();
    return props.options.filter(o =>
        String(o[props.labelKey]).toLowerCase().includes(search)
    ).slice(0, props.maxResults);
});

// When a value is selected externally, update the display
watch(() => props.modelValue, (newVal) => {
    if (!newVal) {
        searchText.value = '';
    } else if (selectedOption.value) {
        searchText.value = selectedOption.value[props.labelKey];
    }
}, { immediate: true });

const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    highlightedIndex.value = -1;
    // Clear search to show all options when opening
    if (selectedOption.value) {
        searchText.value = '';
    }
};

const closeDropdown = () => {
    // Delay so click on option registers first
    setTimeout(() => {
        isOpen.value = false;
        highlightedIndex.value = -1;
        // Restore selected label or clear
        if (selectedOption.value) {
            searchText.value = selectedOption.value[props.labelKey];
        } else {
            searchText.value = '';
        }
    }, 150);
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    searchText.value = option[props.labelKey];
    isOpen.value = false;
    highlightedIndex.value = -1;
};

const clearSelection = () => {
    emit('update:modelValue', '');
    searchText.value = '';
    isOpen.value = false;
    nextTick(() => inputRef.value?.focus());
};

const onKeydown = (e) => {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            openDropdown();
            e.preventDefault();
        }
        return;
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            e.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            e.preventDefault();
            if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            closeDropdown();
            break;
    }
};

const scrollToHighlighted = () => {
    nextTick(() => {
        const el = dropdownRef.value?.querySelector(`[data-index="${highlightedIndex.value}"]`);
        if (el) el.scrollIntoView({ block: 'nearest' });
    });
};

// Reset highlighted on search change
watch(searchText, () => {
    highlightedIndex.value = -1;
});
</script>

<template>
    <div class="relative">
        <!-- Input -->
        <div class="relative">
            <input ref="inputRef" v-model="searchText" type="text" :placeholder="placeholder" :disabled="disabled"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8"
                :class="{
                    'border-red-300 focus:border-red-500 focus:ring-red-500': error,
                    'bg-gray-100 dark:bg-gray-600 cursor-not-allowed': disabled,
                }" autocomplete="off" @focus="openDropdown" @blur="closeDropdown" @keydown="onKeydown"
                @input="isOpen = true" />
            <!-- Clear button when a value is selected -->
            <button v-if="modelValue && !disabled" type="button"
                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-200"
                @mousedown.prevent="clearSelection">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <!-- Chevron when no value -->
            <div v-else-if="!disabled"
                class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400 dark:text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Dropdown -->
        <div v-if="isOpen" ref="dropdownRef"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-52 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
            <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                No results found.
            </div>
            <button v-for="(option, idx) in filteredOptions" :key="option[valueKey]" :data-index="idx" type="button"
                class="block w-full text-left px-4 py-2.5 text-sm transition-colors" :class="{
                    'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300': highlightedIndex === idx,
                    'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-600': highlightedIndex !== idx,
                    'font-medium bg-indigo-50/50 dark:bg-indigo-900/20': String(option[valueKey]) === String(modelValue),
                }" @mousedown.prevent="selectOption(option)" @mouseenter="highlightedIndex = idx">
                <slot name="option" :option="option">
                    {{ option[labelKey] }}
                </slot>
            </button>
        </div>

        <!-- Error message -->
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
