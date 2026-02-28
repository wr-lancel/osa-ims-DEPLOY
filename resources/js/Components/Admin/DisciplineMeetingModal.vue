<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    disciplineId: {
        type: [Number, String],
        default: null,
    },
    meeting: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const form = useForm({
    meeting_date: new Date().toISOString().split('T')[0],
    meeting_time: '',
    location: 'Discipline Office',
    purpose_notes: '',
    status: 'scheduled',
});

const statusOptions = [
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'rescheduled', label: 'Rescheduled' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

watch(() => [props.show, props.meeting], () => {
    if (props.show) {
        if (props.meeting) {
            form.meeting_date = props.meeting.meeting_date || new Date().toISOString().split('T')[0];
            form.meeting_time = props.meeting.meeting_time || '';
            form.location = props.meeting.location || 'Discipline Office';
            form.purpose_notes = props.meeting.purpose_notes || '';
            form.status = props.meeting.status || 'scheduled';
        } else {
            form.reset();
            form.meeting_date = new Date().toISOString().split('T')[0];
            form.location = 'Discipline Office';
            form.status = 'scheduled';
        }
    }
}, { deep: true });

const submit = () => {
    isProcessing.value = true;
    const url = props.meeting
        ? route('admin.discipline.meetings.update', [props.disciplineId, props.meeting.meeting_id])
        : route('admin.discipline.meetings.store', props.disciplineId);
    const method = props.meeting ? 'put' : 'post';
    router[method](url, form, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
        },
        onFinish: () => { isProcessing.value = false; },
    });
};

const close = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                {{ meeting ? 'Edit Meeting' : 'Schedule Meeting' }}
            </h2>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <InputLabel for="meeting_date" value="Meeting Date" />
                    <TextInput
                        id="meeting_date"
                        v-model="form.meeting_date"
                        type="date"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.meeting_date" />
                </div>
                <div class="mb-4">
                    <InputLabel for="meeting_time" value="Meeting Time (optional)" />
                    <TextInput
                        id="meeting_time"
                        v-model="form.meeting_time"
                        type="time"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.meeting_time" />
                </div>
                <div class="mb-4">
                    <InputLabel for="location" value="Location" />
                    <TextInput
                        id="location"
                        v-model="form.location"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Discipline Office"
                    />
                    <InputError :message="form.errors.location" />
                </div>
                <div class="mb-4">
                    <InputLabel for="purpose_notes" value="Purpose / Notes (optional)" />
                    <textarea
                        id="purpose_notes"
                        v-model="form.purpose_notes"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.purpose_notes" />
                </div>
                <div class="mb-6">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option
                            v-for="opt in statusOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.status" />
                </div>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (meeting ? 'Update' : 'Schedule') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
