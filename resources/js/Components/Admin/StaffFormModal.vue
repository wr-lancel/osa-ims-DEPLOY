<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import { formatLabel } from '@/utils/formatLabel.js';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const { notification, notify, closeNotification } = useNotification();

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

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const errors = ref({});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        errors.value = {};
        if (props.employee) {
            let userEmail = props.employee.email || '';
            if (userEmail.endsWith('@chcc.edu.ph')) {
                userEmail = userEmail.replace('@chcc.edu.ph', '');
            }

            formData.value = {
                employee_number: props.employee.employee_number || '',
                first_name: props.employee.first_name || '',
                last_name: props.employee.last_name || '',
                email: userEmail,
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

    // Append the email domain
    if (data.email && !data.email.includes('@')) {
        data.email = `${data.email}@chcc.edu.ph`;
    }

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
                    notify('error', response.data.message);
                }
            }
        })
        .catch((error) => {
            isProcessing.value = false;
            if (error.response?.status === 422) {
                // Handle validation errors
                errors.value = error.response.data.errors || {};
            } else if (error.response?.data?.message) {
                notify('error', error.response.data.message);
            } else {
                notify('error', 'Failed to save staff member. Please try again.');
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
    <LoadingOverlay :show="isProcessing" message="Saving... Please wait." />
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ employee ? 'Edit Staff Member' : 'Add New Staff Member' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Success Message -->
                <div v-if="showSuccessMessage" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Employee Number -->
                        <div>
                            <InputLabel for="employee_number" value="Employee Number *" />
                            <TextInput id="employee_number" v-model="formData.employee_number" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-500': errors.employee_number }"
                                required />
                            <InputError :message="errors.employee_number?.[0]" class="mt-2" />
                        </div>

                        <!-- Role -->
                        <div>
                            <InputLabel for="role_id" value="Role *" />
                            <select id="role_id" v-model="formData.role_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                                :class="{ 'border-red-500': errors.role_id }" required>
                                <option value="">Select a role</option>
                                <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                                    {{ formatLabel(role.role_name) }}
                                </option>
                            </select>
                            <InputError :message="errors.role_id?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div>
                            <InputLabel for="first_name" value="First Name *" />
                            <TextInput id="first_name" v-model="formData.first_name" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-500': errors.first_name }" required />
                            <InputError :message="errors.first_name?.[0]" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <InputLabel for="last_name" value="Last Name *" />
                            <TextInput id="last_name" v-model="formData.last_name" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.last_name }" required />
                            <InputError :message="errors.last_name?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email *" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <TextInput id="email" v-model="formData.email" type="text"
                                    class="block w-full flex-1 rounded-none rounded-l-md"
                                    :class="{ 'border-red-500': errors.email }" placeholder="first.last" required />
                                <span
                                    class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 px-3 text-gray-500 dark:text-gray-400 dark:text-gray-400 sm:text-sm">
                                    @chcc.edu.ph
                                </span>
                            </div>
                            <InputError :message="errors.email?.[0]" class="mt-2" />
                        </div>

                        <!-- Phone -->
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput id="phone" v-model="formData.phone" type="tel" class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.phone }"
                                @input="formData.phone = $event.target.value.replace(/\D/g, '')" />
                            <InputError :message="errors.phone?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Department -->
                        <div>
                            <InputLabel for="department" value="Department" />
                            <TextInput id="department" v-model="formData.department" type="text"
                                class="mt-1 block w-full" :class="{ 'border-red-500': errors.department }" />
                            <InputError :message="errors.department?.[0]" class="mt-2" />
                        </div>

                        <!-- Position -->
                        <div>
                            <InputLabel for="position" value="Position" />
                            <TextInput id="position" v-model="formData.position" type="text" class="mt-1 block w-full"
                                :class="{ 'border-red-500': errors.position }" />
                            <InputError :message="errors.position?.[0]" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Password -->
                        <div>
                            <InputLabel for="password"
                                :value="employee ? 'Password (leave blank to keep current)' : 'Password *'" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <TextInput id="password" v-model="formData.password"
                                    :type="showPassword ? 'text' : 'password'" class="block w-full pr-10"
                                    autocomplete="new-password" :class="{ 'border-red-500': errors.password }"
                                    :required="!employee" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                    <svg v-if="!showPassword" class="h-5 w-5 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="errors.password?.[0]" class="mt-2" />
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <InputLabel for="password_confirmation"
                                :value="employee ? 'Confirm Password' : 'Confirm Password *'" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <TextInput id="password_confirmation" v-model="formData.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'" class="block w-full pr-10"
                                    autocomplete="new-password"
                                    :class="{ 'border-red-500': errors.password_confirmation }" :required="!employee" />
                                <button type="button" @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                                    <svg v-if="!showPassword" class="h-5 w-5 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-white" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
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

    <NotificationDialog :show="notification.show" :type="notification.type" :title="notification.title"
        :message="notification.message" @close="closeNotification" />
</template>
