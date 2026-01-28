<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    role: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);

const formData = ref({
    role_name: '',
});

const errors = ref({});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        errors.value = {};
        if (props.role) {
            formData.value = {
                role_name: props.role.role_name || '',
            };
        } else {
            formData.value = {
                role_name: '',
            };
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    errors.value = {};
    
    const url = props.role 
        ? route('admin.roles.update', props.role.role_id)
        : route('admin.roles.store');
    
    const method = props.role ? 'put' : 'post';
    const message = props.role ? 'Role updated successfully!' : 'Role created successfully!';
    
    axios[method](url, formData.value)
        .then((response) => {
            if (response.data.success) {
                successMessage.value = message;
                showSuccessMessage.value = true;
                emit('saved');
                setTimeout(() => {
                    emit('close');
                    resetForm();
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
                errors.value = error.response.data.errors || {};
            } else if (error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert('Failed to save role. Please try again.');
            }
        });
};

const resetForm = () => {
    formData.value = {
        role_name: '',
    };
    errors.value = {};
};

const close = () => {
    resetForm();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="md">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                {{ role ? 'Edit Role' : 'Add New Role' }}
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
                    <!-- Role Name -->
                    <div>
                        <InputLabel for="role_name" value="Role Name *" />
                        <TextInput
                            id="role_name"
                            v-model="formData.role_name"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': errors.role_name }"
                            required
                        />
                        <InputError :message="errors.role_name?.[0]" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (role ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

