<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const { notification, notify, confirmAction, closeNotification, handleConfirm } = useNotification();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    student: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'created']);

const isProcessing = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');
const formErrors = ref({});
const password = ref('');

const emailDomain = 'chcc.edu.ph';

// Email is always auto-generated from student number (institutional format)
const generatedEmail = computed(() => {
    if (!props.student?.student_number) return '';
    const cleanNumber = props.student.student_number.toLowerCase().trim().replace(/[^a-z0-9-]/g, '');
    return `${cleanNumber}@${emailDomain}`;
});

// Generate default password (same as student number)
const generateDefaultPassword = (studentNumber) => {
    return studentNumber || '';
};

watch(() => props.show, (isShowing) => {
    if (isShowing && props.student) {
        showSuccessMessage.value = false;
        formErrors.value = {};
        password.value = generateDefaultPassword(props.student.student_number);
    }
});

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        notify('success', 'Copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const submit = () => {
    if (isProcessing.value || !props.student) return;

    isProcessing.value = true;
    formErrors.value = {};

    const formData = {
        password: password.value || undefined,
    };

    axios.post(route('admin.students.account.create', props.student.student_number), formData, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    })
        .then((response) => {
            if (response.data.success) {
                successMessage.value = 'Account created successfully!';
                showSuccessMessage.value = true;
                emit('created');

                // Show credentials if provided
                if (response.data.account) {
                    const credsMsg = `Email: ${response.data.account.email}\nPassword: ${response.data.account.password}`;
                    setTimeout(() => {
                        confirmAction(
                            credsMsg,
                            'Account Created — Copy Credentials?',
                            () => {
                                copyToClipboard(credsMsg);
                                close();
                            },
                            { confirmLabel: 'Copy to Clipboard', cancelLabel: 'Close', onClose: () => close() }
                        );
                    }, 500);
                } else {
                    setTimeout(() => {
                        close();
                    }, 1500);
                }
            } else {
                isProcessing.value = false;
                notify('error', response.data.message || 'Failed to create account.');
            }
        })
        .catch((error) => {
            isProcessing.value = false;
            if (error.response?.status === 422) {
                const errors = error.response.data.errors || {};
                formErrors.value = errors;
            } else if (error.response?.data?.message) {
                notify('error', error.response.data.message);
            } else {
                notify('error', 'Failed to create account. Please try again.');
            }
        });
};

const close = () => {
    password.value = '';
    showSuccessMessage.value = false;
    formErrors.value = {};
    isProcessing.value = false;
    emit('close');
};

const isExistingAccount = computed(() => {
    return props.student?.has_account === true;
});
</script>

<template>
    <LoadingOverlay :show="isProcessing" message="Saving... Please wait." />
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ isExistingAccount ? 'View/Reset Student Account' : 'Create Student Account' }}
            </h2>

            <div v-if="!student" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                No student selected.
            </div>

            <template v-else>
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

                <!-- Student Info -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Student Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Student Number:</span>
                            <span class="ml-2 font-medium">{{ student.student_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Name:</span>
                            <span class="ml-2 font-medium">{{ student.name }}</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" v-if="!isExistingAccount">
                    <div class="space-y-4">
                        <!-- Email (Read-only, auto-generated institutional email) -->
                        <div>
                            <InputLabel for="email" value="Institutional Email" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <div
                                    class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 cursor-not-allowed">
                                    {{ generatedEmail }}
                                </div>
                                <button type="button" @click="copyToClipboard(generatedEmail)"
                                    class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    title="Copy to clipboard">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                Auto-generated from student number. This is the student's institutional email.
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Default Password *" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <TextInput id="password" v-model="password" type="text"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': formErrors.password }" required />
                                <button type="button" @click="copyToClipboard(password)"
                                    class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    title="Copy to clipboard">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="formErrors.password" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                Default password is the student number. Student should change it on first login.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="close">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton :disabled="isProcessing">
                            {{ isProcessing ? 'Creating...' : 'Create Account' }}
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Existing Account Info -->
                <div v-else class="space-y-4">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">Account Information</h3>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-blue-700">Email:</span>
                                <span class="ml-2 font-medium text-blue-900">{{ student.account_email || 'N/A' }}</span>
                                <button type="button" @click="copyToClipboard(student.account_email)"
                                    class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-sm text-yellow-800">
                            This student already has an account. To reset the password or modify account settings,
                            please use the account management features.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="close">
                            Close
                        </SecondaryButton>
                    </div>
                </div>
            </template>
        </div>
    </Modal>

    <NotificationDialog :show="notification.show" :type="notification.type" :title="notification.title"
        :message="notification.message" :confirm-label="notification.confirmLabel"
        :cancel-label="notification.cancelLabel" @close="closeNotification" @confirm="handleConfirm" />
</template>
