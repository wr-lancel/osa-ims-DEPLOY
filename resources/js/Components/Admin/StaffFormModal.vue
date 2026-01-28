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
    employee: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);

const formData = ref({
    employee_number: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    department: '',
    position: '',
    role_id: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        errors.value = {};
        if (props.employee) {
            formData.value = {
                employee_number: props.employee.employee_number || '',
                first_name: props.employee.first_name || '',
                last_name: props.employee.last_name || '',
                email: props.employee.email || '',
                phone: props.employee.phone || '',
                department: props.employee.department || '',
                position: props.employee.position || '',
                role_id: props.employee.role_id || '',
                password: '',
                password_confirmation: '',
            };
        } else {
            formData.value = {
                employee_number: '',
                first_name: '',
                last_name: '',
                email: '',
                phone: '',
                department: '',
                position: '',
                role_id: '',
                password: '',
                password_confirmation: '',
            };
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    errors.value = {};
    
    const url = props.employee 
        ? route('admin.staff.update', props.employee.employee_id)
        : route('admin.staff.store');
    
    const method = props.employee ? 'put' : 'post';
    const message = props.employee ? 'Staff member updated successfully!' : 'Staff member created successfully!';
    
    // Prepare data - exclude password fields if updating and password is empty
    const data = { ...formData.value };
    if (props.employee && !data.password) {
        delete data.password;
        delete data.password_confirmation;
    }
    
    axios[method](url, data)
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
                alert('Failed to save staff member. Please try again.');
            }
        });
};

const resetForm = () => {
    formData.value = {
        employee_number: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        department: '',
        position: '',
        role_id: '',
        password: '',
        password_confirmation: '',
    };
    errors.value = {};
};

const close = () => {
    resetForm();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                {{ employee ? 'Edit Staff Member' : 'Add New Staff Member' }}
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
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Employee Number -->
                        <div>
                            <InputLabel for="employee_number" value="Employee Number *" />
                            <TextInput
                                id="employee_number"
                                v-model="formData.employee_number"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.employee_number }"
                                required
                            />
                            <InputError :message="errors.employee_number?.[0]" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div>
                            <InputLabel for="role_id" value="Role *" />
                            <select
                                id="role_id"
                                v-model="formData.role_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{ 'border-red-500': errors.role_id }"
                                required
                            >
                                <option value="">Select a role</option>
                                <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                                    {{ role.role_name }}
                                </option>
                            </select>
                            <InputError :message="errors.role_id?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div>
                            <InputLabel for="first_name" value="First Name *" />
                            <TextInput
                                id="first_name"
                                v-model="formData.first_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.first_name }"
                                required
                            />
                            <InputError :message="errors.first_name?.[0]" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <InputLabel for="last_name" value="Last Name *" />
                            <TextInput
                                id="last_name"
                                v-model="formData.last_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.last_name }"
                                required
                            />
                            <InputError :message="errors.last_name?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email *" />
                            <TextInput
                                id="email"
                                v-model="formData.email"
                                type="email"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.email }"
                                required
                            />
                            <InputError :message="errors.email?.[0]" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="formData.phone"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.phone }"
                            />
                            <InputError :message="errors.phone?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Department -->
                        <div>
                            <InputLabel for="department" value="Department" />
                            <TextInput
                                id="department"
                                v-model="formData.department"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.department }"
                            />
                            <InputError :message="errors.department?.[0]" class="mt-2" />
                        </div>

                        <!-- Position -->
                        <div>
                            <InputLabel for="position" value="Position" />
                            <TextInput
                                id="position"
                                v-model="formData.position"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.position }"
                            />
                            <InputError :message="errors.position?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Password -->
                        <div>
                            <InputLabel for="password" :value="employee ? 'Password (leave blank to keep current)' : 'Password *'" />
                            <TextInput
                                id="password"
                                v-model="formData.password"
                                type="password"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.password }"
                                :required="!employee"
                            />
                            <InputError :message="errors.password?.[0]" class="mt-2" />
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <InputLabel for="password_confirmation" :value="employee ? 'Confirm Password' : 'Confirm Password *'" />
                            <TextInput
                                id="password_confirmation"
                                v-model="formData.password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.password_confirmation }"
                                :required="!employee"
                            />
                            <InputError :message="errors.password_confirmation?.[0]" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (employee ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

