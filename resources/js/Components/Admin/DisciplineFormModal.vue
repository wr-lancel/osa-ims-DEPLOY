<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    violation: {
        type: Object,
        default: null,
    },
    enrollments: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
    violationTypes: {
        type: Array,
        default: () => [],
    },
    violationSeverities: {
        type: Array,
        default: () => ['Minor', 'Moderate', 'Major'],
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const formErrors = ref({});

const form = useForm({
    enrollment_id: '',
    violation_date: new Date().toISOString().split('T')[0],
    violation_type: '',
    description: '',
    severity: '',
    status: 'pending',
    sanction: '',
    remarks: '',
    date_resolved: '',
    reported_by: null,
});

// Build options for SearchableSelect
const enrollmentOptions = computed(() => {
    return props.enrollments.map(e => ({
        value: e.enrollment_id,
        label: e.display_label,
    }));
});

const severityOptions = computed(() => [
    { value: '', label: 'Select Severity' },
    ...props.violationSeverities.map(s => ({ value: s, label: s })),
]);

// Default status = first workflow step
const defaultStatus = computed(() => props.statusOptions[0]?.value || 'Violation Reported');

// Violation types filtered by selected severity
const filteredViolationTypes = computed(() => {
    if (!form.severity) return [];
    return props.violationTypes.filter(t => t.severity === form.severity);
});

// When severity changes, reset violation type (only if current type doesn't belong to new severity)
watch(() => form.severity, (newSev) => {
    if (newSev && form.violation_type) {
        const valid = props.violationTypes.some(t => t.severity === newSev && t.name === form.violation_type);
        if (!valid) {
            form.violation_type = '';
        }
    }
});

// When violation type changes, auto-fill sanction with default_sanction
watch(() => form.violation_type, (newType) => {
    if (newType) {
        const match = props.violationTypes.find(t => t.name === newType && t.severity === form.severity);
        if (match && match.default_sanction) {
            form.sanction = match.default_sanction;
        }
    }
});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        formErrors.value = {};
        if (props.violation) {
            form.violation_date = props.violation.violation_date || new Date().toISOString().split('T')[0];
            form.violation_type = props.violation.violation_type || '';
            form.description = props.violation.description || '';
            form.severity = props.violation.severity || '';
            form.status = props.violation.status || defaultStatus.value;
            form.sanction = props.violation.sanction || '';
            form.remarks = props.violation.remarks || '';
            form.date_resolved = props.violation.date_resolved || '';
            form.reported_by = props.violation.reported_by || null;
        } else {
            form.reset();
            form.violation_date = new Date().toISOString().split('T')[0];
            form.status = defaultStatus.value;
            form.enrollment_id = '';
            form.sanction = '';
            form.remarks = '';
            form.date_resolved = '';
        }
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    if (props.violation) {
        // Edit — use PUT
        router.put(route('admin.discipline.update', props.violation.discipline_id), {
            violation_date: form.violation_date,
            violation_type: form.violation_type,
            description: form.description,
            severity: form.severity || '',
            status: form.status,
            sanction: form.sanction || '',
            remarks: form.remarks || '',
            date_resolved: form.date_resolved || '',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
            onError: (errors) => {
                formErrors.value = errors;
                isProcessing.value = false;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        });
    } else {
        // Create — use POST
        router.post(route('admin.discipline.store'), {
            enrollment_id: form.enrollment_id,
            violation_date: form.violation_date,
            violation_type: form.violation_type,
            description: form.description,
            severity: form.severity || '',
            status: form.status,
            sanction: form.sanction || '',
            remarks: form.remarks || '',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
            onError: (errors) => {
                formErrors.value = errors;
                isProcessing.value = false;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        });
    }
};

const close = () => {
    form.reset();
    formErrors.value = {};
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                {{ violation ? 'Edit Violation' : 'Add Violation' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Student (Term) - create only, using SearchableSelect -->
                <div v-if="!violation" class="mb-4">
                    <InputLabel for="enrollment_id" value="Student (Term)" />
                    <SearchableSelect
                        v-model="form.enrollment_id"
                        :options="enrollmentOptions"
                        placeholder="Search by name or student number..."
                        :error="formErrors.enrollment_id"
                    />
                </div>

                <div class="mb-4">
                    <InputLabel for="violation_date" value="Violation Date" />
                    <TextInput
                        id="violation_date"
                        v-model="form.violation_date"
                        type="date"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.violation_date }"
                        required
                    />
                    <InputError :message="formErrors.violation_date" />
                </div>

                <div class="mb-4">
                    <InputLabel for="severity" value="Severity" />
                    <select
                        id="severity"
                        v-model="form.severity"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option
                            v-for="option in severityOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="formErrors.severity" />
                </div>

                <div class="mb-4">
                    <InputLabel for="violation_type" value="Offense / Violation Type" />
                    <select
                        id="violation_type"
                        v-model="form.violation_type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.violation_type }"
                        :disabled="!form.severity"
                        required
                    >
                        <option value="">{{ form.severity ? 'Select Violation Type' : 'Select severity first' }}</option>
                        <option
                            v-for="type in filteredViolationTypes"
                            :key="type.id"
                            :value="type.name"
                        >
                            {{ type.name }}
                        </option>
                    </select>
                    <InputError :message="formErrors.violation_type" />
                </div>

                <div class="mb-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.description }"
                        placeholder="Describe the violation in detail..."
                        required
                    />
                    <InputError :message="formErrors.description" />
                </div>

                <div class="mb-4">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="formErrors.status" />
                </div>

                <div class="mb-4">
                    <InputLabel for="sanction" value="Sanction (optional)" />
                    <textarea
                        id="sanction"
                        v-model="form.sanction"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Sanction applied..."
                    />
                    <InputError :message="formErrors.sanction" />
                </div>

                <div class="mb-4">
                    <InputLabel for="remarks" value="Remarks (optional)" />
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Additional remarks..."
                    />
                    <InputError :message="formErrors.remarks" />
                </div>

                <div v-if="violation" class="mb-6">
                    <InputLabel for="date_resolved" value="Date Resolved (optional)" />
                    <TextInput
                        id="date_resolved"
                        v-model="form.date_resolved"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="formErrors.date_resolved" />
                </div>

                <div v-else class="mb-6" />

                <div class="flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (violation ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
