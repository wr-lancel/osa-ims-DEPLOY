<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    event: {
        type: Object,
        default: null,
    },
    organizations: {
        type: Array,
        default: () => [],
    },
    eventStatuses: {
        type: Array,
        default: () => ['Planning', 'Upcoming', 'Completed'],
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const formErrors = ref({});
const weekendError = ref('');

const officeHours = [
    { value: '08:00', label: '8:00 AM' },
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    { value: '13:00', label: '1:00 PM' },
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
];

const onDateChange = () => {
    if (!form.event_date) return;
    const [year, month, day] = form.event_date.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    if (d.getDay() === 0 || d.getDay() === 6) {
        form.event_date = '';
        weekendError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        weekendError.value = '';
    }
};

const form = useForm({
    org_id: '',
    event_name: '',
    description: '',
    event_date: new Date().toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    venue: '',
    status: 'Planning',
    confirm_date_conflict: '',
});

const showConflictDialog = ref(false);
const conflictMessage = ref('');

const statusOptions = computed(() =>
    props.eventStatuses.map(s => ({ value: s, label: s }))
);

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        formErrors.value = {};
        if (props.event) {
            form.org_id = props.event.org_id?.toString() || '';
            form.event_name = props.event.event_name || '';
            form.description = props.event.description || '';
            form.event_date = props.event.event_date || new Date().toISOString().split('T')[0];
            form.start_time = props.event.start_time || '';
            form.end_time = props.event.end_time || '';
            form.venue = props.event.venue || '';
            form.status = props.event.status || 'Planning';
        } else {
            form.reset();
            form.event_date = new Date().toISOString().split('T')[0];
            form.status = 'Planning';
        }
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    const url = props.event
        ? route('admin.organizations.events.update', props.event.event_id)
        : route('admin.organizations.events.store');

    const method = props.event ? 'put' : 'post';

    router[method](url, form, {
        preserveScroll: true,
        onSuccess: () => {
            form.confirm_date_conflict = '';
            emit('saved');
            close();
        },
        onError: (errors) => {
            if (errors.date_conflict && Array.isArray(errors.date_conflict) && errors.date_conflict[0]) {
                conflictMessage.value = errors.date_conflict[0];
                showConflictDialog.value = true;
                const { date_conflict: _, ...rest } = errors;
                formErrors.value = rest;
            } else {
                formErrors.value = errors;
            }
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const confirmConflictAndSubmit = () => {
    showConflictDialog.value = false;
    form.confirm_date_conflict = '1';
    submit();
};

const close = () => {
    form.reset();
    formErrors.value = {};
    weekendError.value = '';
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ event ? 'Edit Event' : 'Add Event' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Organization -->
                <div class="mb-4">
                    <InputLabel for="org_id" value="Organization" />
                    <select
                        id="org_id"
                        v-model="form.org_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.org_id }"
                    >
                        <option value="">Select Organization (Optional)</option>
                        <option
                            v-for="org in organizations"
                            :key="org.org_id"
                            :value="org.org_id"
                        >
                            {{ org.org_name }}
                        </option>
                    </select>
                    <InputError :message="formErrors.org_id" />
                </div>

                <!-- Event Name -->
                <div class="mb-4">
                    <InputLabel for="event_name" value="Event Name" />
                    <TextInput
                        id="event_name"
                        v-model="form.event_name"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.event_name }"
                        required
                    />
                    <InputError :message="formErrors.event_name" />
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.description }"
                    />
                    <InputError :message="formErrors.description" />
                </div>

                <!-- Event Date -->
                <div class="mb-4">
                    <InputLabel for="event_date" value="Event Date" />
                    <TextInput
                        id="event_date"
                        v-model="form.event_date"
                        type="date"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.event_date }"
                        :min="new Date().toISOString().split('T')[0]"
                        required
                        @change="onDateChange"
                    />
                    <p v-if="weekendError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ weekendError }}</p>
                    <InputError :message="formErrors.event_date" />
                </div>

                <!-- Start Time -->
                <div class="mb-4">
                    <InputLabel for="start_time" value="Start Time" />
                    <select
                        id="start_time"
                        v-model="form.start_time"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.start_time }"
                    >
                        <option value="">— Select Time —</option>
                        <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                    </select>
                    <InputError :message="formErrors.start_time" />
                </div>

                <!-- End Time -->
                <div class="mb-4">
                    <InputLabel for="end_time" value="End Time" />
                    <select
                        id="end_time"
                        v-model="form.end_time"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
                        :class="{ 'border-red-300': formErrors.end_time }"
                    >
                        <option value="">— Select Time —</option>
                        <option v-for="h in officeHours" :key="h.value" :value="h.value">{{ h.label }}</option>
                    </select>
                    <InputError :message="formErrors.end_time" />
                </div>

                <!-- Venue -->
                <div class="mb-4">
                    <InputLabel for="venue" value="Venue" />
                    <TextInput
                        id="venue"
                        v-model="form.venue"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.venue }"
                    />
                    <InputError :message="formErrors.venue" />
                </div>

                <!-- Status -->
                <div class="mb-6">
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

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (event ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>

            <!-- Date conflict confirmation dialog -->
            <div
                v-if="showConflictDialog"
                class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="conflict-dialog-title"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg max-w-md w-full p-6">
                    <h3 id="conflict-dialog-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Event date conflict
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                        {{ conflictMessage }}
                    </p>
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton type="button" @click="showConflictDialog = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="button" @click="confirmConflictAndSubmit">
                            Continue anyway
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

