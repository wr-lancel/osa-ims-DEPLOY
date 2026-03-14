<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import { useNotification } from '@/composables/useNotification';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const { notification, notify, closeNotification } = useNotification();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    caseItem: {
        type: Object,
        default: null,
    },
    employees: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);
const enrollments = ref([]);
const loadingEnrollments = ref(false);

const formData = ref({
    enrollment_id: '',
    case_no: '',
    case_type: 'counseling',
    concern: '',
    status: 'pending',
    assigned_staff_id: '',
    requested_at: '',
});

const errors = ref({});

const caseTypes = [
    { value: 'counseling', label: 'Counseling' },
    { value: 'consultation', label: 'Consultation' },
    { value: 'referral', label: 'Referral' },
];

const statuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'ongoing', label: 'Ongoing' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' },
];

const loadEnrollments = async (search = '') => {
    if (loadingEnrollments.value) return;
    
    loadingEnrollments.value = true;
    try {
        const response = await axios.get(route('admin.guidance.enrollments.list'), {
            params: { search },
        });
        if (response.data.success) {
            enrollments.value = response.data.enrollments;
        }
    } catch (error) {
        console.error('Failed to load enrollments:', error);
    } finally {
        loadingEnrollments.value = false;
    }
};

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        errors.value = {};
        if (props.caseItem) {
            formData.value = {
                enrollment_id: props.caseItem.enrollment_id || '',
                case_no: props.caseItem.case_no || '',
                case_type: props.caseItem.case_type || 'counseling',
                concern: props.caseItem.concern || '',
                status: props.caseItem.status || 'pending',
                assigned_staff_id: props.caseItem.assigned_staff_id || '',
                requested_at: props.caseItem.requested_at ? props.caseItem.requested_at.split(' ')[0] : '',
            };
        } else {
            formData.value = {
                enrollment_id: '',
                case_no: '',
                case_type: 'counseling',
                concern: '',
                status: 'pending',
                assigned_staff_id: '',
                requested_at: new Date().toISOString().split('T')[0],
            };
        }
        loadEnrollments();
    }
});

onMounted(() => {
    if (props.show) {
        loadEnrollments();
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    errors.value = {};
    
    const url = props.caseItem 
        ? route('admin.guidance.update', props.caseItem.guidance_case_id)
        : route('admin.guidance.store');
    
    const method = props.caseItem ? 'put' : 'post';
    const message = props.caseItem ? 'Case updated successfully!' : 'Case created successfully!';
    
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
                notify('error', 'Failed to save case. Please try again.');
            }
        });
};

const resetForm = () => {
    formData.value = {
        enrollment_id: '',
        case_no: '',
        case_type: 'counseling',
        concern: '',
        status: 'pending',
        assigned_staff_id: '',
        requested_at: new Date().toISOString().split('T')[0],
    };
    errors.value = {};
};

const close = () => {
    resetForm();
    emit('close');
};

const getEnrollmentLabel = (enrollment) => {
    if (!enrollment) return '';
    const student = enrollment.student;
    const section = enrollment.section;
    if (student && section) {
        return `${student.full_name} (${student.student_number}) - ${section.section_name}`;
    }
    return '';
};
</script>

<template>
    <LoadingOverlay :show="isProcessing" message="Saving... Please wait." />
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ caseItem ? 'Edit Guidance Case' : 'Add Guidance Case' }}
            </h2>

            <div v-if="showSuccessMessage" class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Enrollment -->
                <div>
                    <InputLabel for="enrollment_id" value="Student Enrollment *" />
                    <select
                        id="enrollment_id"
                        v-model="formData.enrollment_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.enrollment_id }"
                    >
                        <option value="">Select enrollment...</option>
                        <option v-for="enrollment in enrollments" :key="enrollment.enrollment_id" :value="enrollment.enrollment_id">
                            {{ getEnrollmentLabel(enrollment) }}
                        </option>
                    </select>
                    <InputError :message="errors.enrollment_id?.[0]" />
                </div>

                <!-- Case No -->
                <div>
                    <InputLabel for="case_no" value="Case Number *" />
                    <TextInput
                        id="case_no"
                        v-model="formData.case_no"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': errors.case_no }"
                        required
                    />
                    <InputError :message="errors.case_no?.[0]" />
                </div>

                <!-- Case Type -->
                <div>
                    <InputLabel for="case_type" value="Case Type *" />
                    <select
                        id="case_type"
                        v-model="formData.case_type"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.case_type }"
                        required
                    >
                        <option v-for="type in caseTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="errors.case_type?.[0]" />
                </div>

                <!-- Concern -->
                <div>
                    <InputLabel for="concern" value="Concern" />
                    <textarea
                        id="concern"
                        v-model="formData.concern"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.concern }"
                    ></textarea>
                    <InputError :message="errors.concern?.[0]" />
                </div>

                <!-- Status -->
                <div>
                    <InputLabel for="status" value="Status *" />
                    <select
                        id="status"
                        v-model="formData.status"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.status }"
                        required
                    >
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError :message="errors.status?.[0]" />
                </div>

                <!-- Assigned Staff -->
                <div>
                    <InputLabel for="assigned_staff_id" value="Assigned Staff" />
                    <select
                        id="assigned_staff_id"
                        v-model="formData.assigned_staff_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': errors.assigned_staff_id }"
                    >
                        <option value="">Unassigned</option>
                        <option v-for="employee in employees" :key="employee.employee_id" :value="employee.employee_id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                    <InputError :message="errors.assigned_staff_id?.[0]" />
                </div>

                <!-- Requested At -->
                <div>
                    <InputLabel for="requested_at" value="Requested At" />
                    <TextInput
                        id="requested_at"
                        v-model="formData.requested_at"
                        type="date"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': errors.requested_at }"
                    />
                    <InputError :message="errors.requested_at?.[0]" />
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (caseItem ? 'Update' : 'Create') }}
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

