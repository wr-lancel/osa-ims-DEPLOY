<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, closeNotification } = useNotification();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    course: {
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
    course_code: '',
    course_name: '',
    description: '',
});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showDeleteConfirm.value = false;
        showSuccessMessage.value = false;
        if (props.course) {
            form.course_code = props.course.course_code || '';
            form.course_name = props.course.course_name || '';
            form.description = props.course.description || '';
        } else {
            form.reset();
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    form.clearErrors();
    
    const url = props.course 
        ? route('admin.courses.update', props.course.course_id)
        : route('admin.courses.store');
    
    const method = props.course ? 'put' : 'post';
    const message = props.course ? 'Course updated successfully!' : 'Course created successfully!';
    
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
                notify('error', 'Failed to save course. Please try again.');
            }
        });
};

const handleDelete = () => {
    if (showDeleteConfirm.value) {
        axios.delete(route('admin.courses.destroy', props.course.course_id))
            .then(() => {
                successMessage.value = 'Course deleted successfully!';
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
                    notify('error', 'Failed to delete course.');
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
                {{ course ? 'Edit Course' : 'Add New Course' }}
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
                    <!-- Course Code -->
                    <div>
                        <InputLabel for="course_code" value="Course Code" />
                        <TextInput
                            id="course_code"
                            v-model="form.course_code"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': form.errors.course_code }"
                            required
                        />
                        <InputError :message="form.errors.course_code" class="mt-2" />
                    </div>

                    <!-- Course Name -->
                    <div>
                        <InputLabel for="course_name" value="Course Name" />
                        <TextInput
                            id="course_name"
                            v-model="form.course_name"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': form.errors.course_name }"
                            required
                        />
                        <InputError :message="form.errors.course_name" class="mt-2" />
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="{ 'border-red-500': form.errors.description }"
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <div>
                        <button
                            v-if="course"
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
                            {{ isProcessing ? 'Saving...' : (course ? 'Update' : 'Create') }}
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

