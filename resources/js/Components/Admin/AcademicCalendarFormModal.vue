<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, closeNotification } = useNotification();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    calendar: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const showDeleteConfirm = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);

const form = useForm({
    academic_year: '',
    semester: '',
    start_date: '',
    end_date: '',
    status: 'upcoming',
});

const statusOptions = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
];

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showDeleteConfirm.value = false;
        showSuccessMessage.value = false;
        if (props.calendar) {
            form.academic_year = props.calendar.academic_year || '';
            form.semester = props.calendar.semester || '';
            form.start_date = props.calendar.start_date || '';
            form.end_date = props.calendar.end_date || '';
            form.status = props.calendar.status || 'upcoming';
        } else {
            form.reset();
            form.status = 'upcoming';
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    form.clearErrors();
    
    const url = props.calendar 
        ? route('admin.academic-calendars.update', props.calendar.calendar_id)
        : route('admin.academic-calendars.store');
    
    const method = props.calendar ? 'put' : 'post';
    const message = props.calendar ? 'Academic calendar updated successfully!' : 'Academic calendar created successfully!';
    
    axios[method](url, form.data())
        .then((response) => {
            if (response.data.success) {
                successMessage.value = message;
                showSuccessMessage.value = true;
                emit('saved');
                setTimeout(() => {
                    emit('close');
                    form.reset();
                    showSuccessMessage.value = false;
                    isProcessing.value = false;
                }, 1500);
            } else {
                isProcessing.value = false;
                if (response.data.message) {
                    notify('error', response.data.message);
                }
            }
        })
        .catch((error) => {
            isProcessing.value = false;
            if (error.response?.status === 422) {
                // Handle validation errors
                const errors = error.response.data.errors || {};
                Object.keys(errors).forEach(key => {
                    form.setError(key, errors[key][0]);
                });
            } else if (error.response?.data?.message) {
                notify('error', error.response.data.message);
            } else {
                notify('error', 'Failed to save academic calendar. Please try again.');
            }
        });
};

const handleDelete = () => {
    if (showDeleteConfirm.value) {
        axios.delete(route('admin.academic-calendars.destroy', props.calendar.calendar_id))
            .then(() => {
                successMessage.value = 'Academic calendar deleted successfully!';
                showSuccessMessage.value = true;
                emit('saved');
                setTimeout(() => {
                    emit('close');
                    form.reset();
                    showSuccessMessage.value = false;
                }, 1500);
            })
            .catch((error) => {
                if (error.response?.data?.message) {
                    notify('error', error.response.data.message);
                } else {
                    notify('error', 'Failed to delete academic calendar.');
                }
            });
    } else {
        showDeleteConfirm.value = true;
    }
};

const close = () => {
    form.reset();
    showDeleteConfirm.value = false;
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                {{ calendar ? 'Edit Academic Calendar' : 'Add New Academic Calendar' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Success Message -->
                <div
                    v-if="showSuccessMessage"
                    class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md"
                >
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Academic Year and Semester -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="academic_year" value="Academic Year" />
                            <TextInput
                                id="academic_year"
                                v-model="form.academic_year"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.academic_year }"
                                placeholder="e.g., 2024-2025"
                                required
                            />
                            <InputError :message="form.errors.academic_year" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="semester" value="Semester" />
                            <TextInput
                                id="semester"
                                v-model="form.semester"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.semester }"
                                placeholder="e.g., 1st Semester"
                            />
                            <InputError :message="form.errors.semester" class="mt-2" />
                        </div>
                    </div>

                    <!-- Start Date and End Date -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="start_date" value="Start Date" />
                            <TextInput
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.start_date }"
                                required
                            />
                            <InputError :message="form.errors.start_date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="end_date" value="End Date" />
                            <TextInput
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.end_date }"
                                required
                            />
                            <InputError :message="form.errors.end_date" class="mt-2" />
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="{ 'border-red-500': form.errors.status }"
                        >
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Setting a semester as "Active" will automatically mark other active semesters as "Completed".
                        </p>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <div>
                        <button
                            v-if="calendar"
                            type="button"
                            @click="handleDelete"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition"
                        >
                            {{ showDeleteConfirm ? 'Confirm Delete' : 'Delete' }}
                        </button>
                    </div>
                    <div class="flex space-x-3">
                        <SecondaryButton type="button" @click="close">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="isProcessing">
                            {{ isProcessing ? 'Saving...' : (calendar ? 'Update' : 'Create') }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>

    <NotificationDialog
        :show="notification.show"
        :type="notification.type"
        :title="notification.title"
        :message="notification.message"
        @close="closeNotification"
    />
</template>


