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

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    section: {
        type: Object,
        default: null,
    },
    courses: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'saved']);

const showDeleteConfirm = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);

const form = useForm({
    course_id: '',
    section_code: '',
});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showDeleteConfirm.value = false;
        showSuccessMessage.value = false;
        if (props.section) {
            form.course_id = props.section.course_id || '';
            form.section_code = props.section.section_code || '';
        } else {
            form.reset();
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    form.clearErrors();
    
    const url = props.section 
        ? route('admin.sections.update', props.section.section_id)
        : route('admin.sections.store');
    
    const method = props.section ? 'put' : 'post';
    const message = props.section ? 'Section updated successfully!' : 'Section created successfully!';
    
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
                    alert(response.data.message);
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
                alert(error.response.data.message);
            } else {
                alert('Failed to save section. Please try again.');
            }
        });
};

const handleDelete = () => {
    if (showDeleteConfirm.value) {
        axios.delete(route('admin.sections.destroy', props.section.section_id))
            .then(() => {
                successMessage.value = 'Section deleted successfully!';
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
                    alert(error.response.data.message);
                } else {
                    alert('Failed to delete section.');
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
                {{ section ? 'Edit Section' : 'Add New Section' }}
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
                    <!-- Course -->
                    <div>
                        <InputLabel for="course_id" value="Course" />
                        <select
                            id="course_id"
                            v-model="form.course_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :class="{ 'border-red-500': form.errors.course_id }"
                            required
                        >
                            <option value="">Select Course</option>
                            <option
                                v-for="course in courses"
                                :key="course.course_id"
                                :value="course.course_id"
                            >
                                {{ course.course_code }} - {{ course.course_name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.course_id" class="mt-2" />
                    </div>

                    <!-- Section Code -->
                    <div>
                        <InputLabel for="section_code" value="Section Code" />
                        <TextInput
                            id="section_code"
                            v-model="form.section_code"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': form.errors.section_code }"
                            required
                        />
                        <InputError :message="form.errors.section_code" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <div>
                        <button
                            v-if="section"
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
                            {{ isProcessing ? 'Saving...' : (section ? 'Update' : 'Create') }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>

