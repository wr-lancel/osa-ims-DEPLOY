<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    steps: {
        type: Array,
        required: true,
        validator: (value) =>
            value.every((s) => typeof s === 'object' && s !== null && typeof s.value === 'string'),
    },
    currentStatus: {
        type: String,
        default: '',
    },
    terminalStatuses: {
        type: Array,
        default: () => [],
    },
    showLabels: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    editable: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:status']);

// Confirmation popover state
const confirmingIndex = ref(null);

const normalizedCurrent = computed(() =>
    (props.currentStatus || '').trim().toLowerCase()
);

const isTerminal = computed(() =>
    props.terminalStatuses.some(
        (s) => String(s).toLowerCase() === normalizedCurrent.value
    )
);

const currentStepIndex = computed(() => {
    if (!normalizedCurrent.value) return -1;
    const idx = props.steps.findIndex(
        (s) => String(s.value).toLowerCase() === normalizedCurrent.value
    );
    return idx;
});

const effectiveSteps = computed(() => {
    if (!isTerminal.value || currentStepIndex.value >= 0) {
        return props.steps;
    }
    return [
        ...props.steps,
        {
            value: props.currentStatus,
            label:
                props.currentStatus.charAt(0).toUpperCase() +
                props.currentStatus.slice(1).replace(/_/g, ' '),
        },
    ];
});

const displayIndex = computed(() => {
    if (currentStepIndex.value >= 0) return currentStepIndex.value;
    if (isTerminal.value) return effectiveSteps.value.length - 1;
    return -1;
});

const getStepState = (index) => {
    if (index < displayIndex.value) return 'completed';
    if (index === displayIndex.value) return 'current';
    return 'future';
};

const sizeClasses = computed(() => {
    const map = {
        sm: { track: 'h-1', dot: 'w-2.5 h-2.5', label: 'text-xs' },
        md: { track: 'h-1.5', dot: 'w-3 h-3', label: 'text-sm' },
        lg: { track: 'h-2', dot: 'w-4 h-4', label: 'text-base' },
    };
    return map[props.size] || map.md;
});

// --- Interactive / Editable logic ---
const canClick = (index) => {
    if (!props.editable) return false;
    // Don't allow clicking the current step
    return index !== displayIndex.value;
};

const handleStepClick = (index) => {
    if (!canClick(index)) return;
    confirmingIndex.value = index;
};

const confirmChange = () => {
    if (confirmingIndex.value === null) return;
    const step = effectiveSteps.value[confirmingIndex.value];
    emit('update:status', step.value);
    confirmingIndex.value = null;
};

const cancelChange = () => {
    confirmingIndex.value = null;
};
</script>

<template>
    <div class="w-full relative">
        <!-- Track + dots row -->
        <div class="flex items-center w-full">
            <template v-for="(step, index) in effectiveSteps" :key="index">
                <!-- Step dot container -->
                <div class="relative flex items-center">
                    <div class="shrink-0 rounded-full flex items-center justify-center transition-all duration-200"
                        :class="[
                            sizeClasses.dot,
                            getStepState(index) === 'completed'
                                ? 'bg-blue-600 ring-2 ring-blue-100'
                                : getStepState(index) === 'current'
                                    ? isTerminal && index === effectiveSteps.length - 1
                                        ? 'bg-red-500 ring-2 ring-red-100 scale-110'
                                        : 'bg-blue-600 ring-2 ring-blue-200 scale-110'
                                    : 'bg-gray-200',
                            editable && canClick(index)
                                ? 'cursor-pointer hover:scale-125 hover:ring-4 hover:ring-blue-200'
                                : '',
                        ]" @click="handleStepClick(index)">
                        <svg v-if="getStepState(index) === 'completed' && !(isTerminal && index === effectiveSteps.length - 1)"
                            class="w-1.5 h-1.5 text-white shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- Confirmation Popover -->
                    <Transition enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                        <div v-if="confirmingIndex === index" class="absolute z-50 mt-2 -translate-x-1/2 left-1/2 top-4"
                            style="min-width: 200px">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                                <p class="text-sm text-gray-700 dark:text-gray-200 mb-2">
                                    Change status to
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ step.label || step.value }}</span>?
                                </p>
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1 text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded-md hover:bg-gray-200 transition"
                                        @click.stop="cancelChange">
                                        Cancel
                                    </button>
                                    <button type="button"
                                        class="px-3 py-1 text-xs font-medium text-white bg-blue-700 rounded-md hover:bg-blue-800 transition"
                                        @click.stop="confirmChange">
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Connector after step (except last) -->
                <div v-if="index < effectiveSteps.length - 1" class="flex-1 rounded-full mx-0.5 min-w-[12px]"
                    :class="[sizeClasses.track, getStepState(index) === 'completed' ? 'bg-blue-600' : 'bg-gray-200']" />
            </template>
        </div>
        <!-- Labels row -->
        <div v-if="showLabels" class="flex w-full mt-2">
            <template v-for="(step, index) in effectiveSteps" :key="`label-${index}`">
                <div class="flex-1 flex flex-col items-center min-w-0 px-1" :class="[
                    index === 0 ? 'items-start' : index === effectiveSteps.length - 1 ? 'items-end' : 'items-center',
                    editable && canClick(index) ? 'cursor-pointer' : '',
                ]" @click="handleStepClick(index)">
                    <span class="truncate max-w-full" :class="[
                        sizeClasses.label,
                        getStepState(index) === 'current'
                            ? 'font-medium text-gray-900 dark:text-white'
                            : getStepState(index) === 'completed'
                                ? 'text-gray-600 dark:text-gray-300'
                                : 'text-gray-400 dark:text-gray-500 dark:text-gray-400',
                        editable && canClick(index) ? 'hover:text-blue-600 dark:text-blue-400 hover:font-medium' : '',
                    ]" :title="step.label || step.value">
                        {{ step.label || step.value }}
                    </span>
                </div>
            </template>
        </div>

        <!-- Editable hint -->
        <p v-if="editable" class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 mt-1 text-center">
            Click a step to change status
        </p>
    </div>
</template>
