<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    borrowing: {
        type: Object,
        default: null,
    },
    students: {
        type: Array,
        default: () => [],
    },
    employees: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const formErrors = ref({});
const borrowerType = ref('student');

const form = useForm({
    student_number: '',
    employee_id: '',
    item_name: '',
    description: '',
    borrow_date: new Date().toISOString().split('T')[0],
    expected_return_date: '',
    status: 'borrowed',
    notes: '',
});

const statusOptions = [
    { value: 'borrowed', label: 'Borrowed' },
    { value: 'returned', label: 'Returned' },
];

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        formErrors.value = {};
        if (props.borrowing) {
            borrowerType.value = props.borrowing.borrower?.type || 'student';
            form.student_number = props.borrowing.borrower?.type === 'student' ? props.borrowing.borrower.number?.toString() : '';
            form.employee_id = props.borrowing.borrower?.type === 'employee' ? props.borrowing.borrower.id?.toString() : '';
            form.item_name = props.borrowing.item_name || '';
            form.description = props.borrowing.description || '';
            form.borrow_date = props.borrowing.borrow_date || new Date().toISOString().split('T')[0];
            form.expected_return_date = props.borrowing.expected_return_date || '';
            form.status = props.borrowing.status?.toLowerCase() || 'borrowed';
            form.notes = props.borrowing.notes || '';
        } else {
            form.reset();
            form.borrow_date = new Date().toISOString().split('T')[0];
            form.status = 'borrowed';
            borrowerType.value = 'student';
        }
    }
});

watch(() => borrowerType.value, (type) => {
    if (type === 'student') {
        form.employee_id = '';
    } else {
        form.student_number = '';
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    // Clear the other borrower type
    if (borrowerType.value === 'student') {
        form.employee_id = '';
    } else {
        form.student_number = '';
    }

    const url = props.borrowing
        ? route('admin.sports.borrowings.update', props.borrowing.borrowing_id)
        : route('admin.sports.borrowings.store');

    const method = props.borrowing ? 'put' : 'post';

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
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ borrowing ? 'Edit Borrowing Record' : 'New Equipment Borrowing' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Borrower Type -->
                <div class="mb-4">
                    <InputLabel for="borrower_type" value="Borrower Type" />
                    <select
                        id="borrower_type"
                        v-model="borrowerType"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                    >
                        <option value="student">Student</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>

                <!-- Student Selection -->
                <div v-if="borrowerType === 'student'" class="mb-4">
                    <InputLabel for="student_number" value="Student" />
                    <select
                        id="student_number"
                        v-model="form.student_number"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.student_number }"
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

                <!-- Employee Selection -->
                <div v-else class="mb-4">
                    <InputLabel for="employee_id" value="Employee" />
                    <select
                        id="employee_id"
                        v-model="form.employee_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.employee_id }"
                    >
                        <option value="">Select Employee</option>
                        <option
                            v-for="employee in employees"
                            :key="employee.employee_id"
                            :value="employee.employee_id"
                        >
                            {{ employee.employee_number }} - {{ employee.full_name }}
                        </option>
                    </select>
                    <InputError :message="formErrors.employee_id" />
                </div>

                <!-- Item Name -->
                <div class="mb-4">
                    <InputLabel for="item_name" value="Equipment Name" />
                    <TextInput
                        id="item_name"
                        v-model="form.item_name"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.item_name }"
                        required
                    />
                    <InputError :message="formErrors.item_name" />
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.description }"
                    />
                    <InputError :message="formErrors.description" />
                </div>

                <!-- Borrow Date -->
                <div class="mb-4">
                    <InputLabel for="borrow_date" value="Borrow Date" />
                    <TextInput
                        id="borrow_date"
                        v-model="form.borrow_date"
                        type="date"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.borrow_date }"
                        required
                    />
                    <InputError :message="formErrors.borrow_date" />
                </div>

                <!-- Expected Return Date -->
                <div class="mb-4">
                    <InputLabel for="expected_return_date" value="Expected Return Date" />
                    <TextInput
                        id="expected_return_date"
                        v-model="form.expected_return_date"
                        type="date"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.expected_return_date }"
                        required
                    />
                    <InputError :message="formErrors.expected_return_date" />
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
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

                <!-- Notes -->
                <div class="mb-6">
                    <InputLabel for="notes" value="Notes" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.notes }"
                    />
                    <InputError :message="formErrors.notes" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (borrowing ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

