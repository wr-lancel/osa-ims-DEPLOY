<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    violation: {
        type: Object,
        default: null,
    },
    students: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const formErrors = ref({});

const form = useForm({
    student_number: '',
    violation_date: new Date().toISOString().split('T')[0],
    violation_type: '',
    description: '',
    severity: '',
    status: 'Pending',
    reported_by: null,
});

const severityOptions = [
    { value: '', label: 'Select Severity' },
    { value: 'Minor', label: 'Minor' },
    { value: 'Moderate', label: 'Moderate' },
    { value: 'Major', label: 'Major' },
];

const statusOptions = [
    { value: 'Pending', label: 'Pending' },
    { value: 'Under Investigation', label: 'Under Investigation' },
    { value: 'Resolved', label: 'Resolved' },
];

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        formErrors.value = {};
        if (props.violation) {
            form.student_number = props.violation.student_number?.toString() || '';
            form.violation_date = props.violation.violation_date || new Date().toISOString().split('T')[0];
            form.violation_type = props.violation.violation_type || '';
            form.description = props.violation.description || '';
            form.severity = props.violation.severity || '';
            form.status = props.violation.status || 'Pending';
            form.reported_by = props.violation.reported_by || null;
        } else {
            form.reset();
            form.violation_date = new Date().toISOString().split('T')[0];
            form.status = 'Pending';
        }
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    const url = props.violation
        ? route('admin.discipline.update', props.violation.discipline_id)
        : route('admin.discipline.store');

    const method = props.violation ? 'put' : 'post';

    router[method](url, form, {
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
};

const close = () => {
    form.reset();
    formErrors.value = {};
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                {{ violation ? 'Edit Violation' : 'Add Violation' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Student Selection -->
                <div class="mb-4">
                    <InputLabel for="student_number" value="Student" />
                    <select
                        id="student_number"
                        v-model="form.student_number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.student_number }"
                        required
                    >
                        <option value="">Select Student</option>
                        <option
                            v-for="student in students"
                            :key="student.student_number"
                            :value="student.student_number"
                        >
                            {{ student.student_number }} - {{ student.full_name }}
                        </option>
                    </select>
                    <InputError :message="formErrors.student_number" />
                </div>

                <!-- Violation Date -->
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

                <!-- Violation Type -->
                <div class="mb-4">
                    <InputLabel for="violation_type" value="Violation Type" />
                    <TextInput
                        id="violation_type"
                        v-model="form.violation_type"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.violation_type }"
                        placeholder="e.g., Tardiness, Absenteeism, etc."
                        required
                    />
                    <InputError :message="formErrors.violation_type" />
                </div>

                <!-- Description -->
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

                <!-- Severity -->
                <div class="mb-4">
                    <InputLabel for="severity" value="Severity" />
                    <select
                        id="severity"
                        v-model="form.severity"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.severity }"
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

                <!-- Status -->
                <div class="mb-6">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.status }"
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

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (violation ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

