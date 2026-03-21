<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const { notification, notify, closeNotification } = useNotification();

const weekendError = ref('');

const onDateChange = () => {
    if (!formData.value.action_at) return;
    const [year, month, day] = formData.value.action_at.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    if (d.getDay() === 0 || d.getDay() === 6) {
        formData.value.action_at = '';
        weekendError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        weekendError.value = '';
    }
};

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    caseItem: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);

const formData = ref({
    guidance_case_id: '',
    note: '',
    action_status: '',
    action_at: '',
});

const errors = ref({});

const statuses = [
    { value: '', label: 'No change' },
    { value: 'pending', label: 'Pending' },
    { value: 'ongoing', label: 'Ongoing' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' },
];

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        errors.value = {};
        if (props.caseItem) {
            formData.value = {
                guidance_case_id: props.caseItem.guidance_case_id || '',
                note: '',
                action_status: '',
                action_at: new Date().toISOString().split('T')[0],
            };
        } else {
            formData.value = {
                guidance_case_id: '',
                note: '',
                action_status: '',
                action_at: new Date().toISOString().split('T')[0],
            };
        }
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    errors.value = {};
    
    const message = 'Action added successfully!';
    
    axios.post(route('admin.guidance.actions.store'), formData.value)
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
                errors.value = error.response.data.errors || {};
            } else if (error.response?.data?.message) {
                notify('error', error.response.data.message);
            } else {
                notify('error', 'Failed to add action. Please try again.');
            }
        });
};

const resetForm = () => {
    formData.value = {
        guidance_case_id: '',
        note: '',
        action_status: '',
        action_at: new Date().toISOString().split('T')[0],
    };
    errors.value = {};
    weekendError.value = '';
};

const close = () => {
    resetForm();
    emit('close');
};
</script>

<template>
    <LoadingOverlay :show="isProcessing" message="Saving... Please wait." />
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Add Action to Case: {{ caseItem?.case_no || '' }}
            </h2>

            <div v-if="showSuccessMessage" class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Note -->
                <div>
                    <InputLabel for="note" value="Note" />
                    <textarea
                        id="note"
                        v-model="formData.note"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.note }"
                        placeholder="Enter action note..."
                    ></textarea>
                    <InputError :message="errors.note?.[0]" />
                </div>

                <!-- Action Status -->
                <div>
                    <InputLabel for="action_status" value="Update Case Status (Optional)" />
                    <select
                        id="action_status"
                        v-model="formData.action_status"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.action_status }"
                    >
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError :message="errors.action_status?.[0]" />
                </div>

                <!-- Action At -->
                <div>
                    <InputLabel for="action_at" value="Action Date" />
                    <input
                        id="action_at"
                        v-model="formData.action_at"
                        type="date"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.action_at }"
                        :min="new Date().toISOString().split('T')[0]"
                        @change="onDateChange"
                    />
                    <p v-if="weekendError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ weekendError }}</p>
                    <InputError :message="errors.action_at?.[0]" />
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Adding...' : 'Add Action' }}
                    </PrimaryButton>
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

