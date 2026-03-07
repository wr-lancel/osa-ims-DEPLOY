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
    organization: {
        type: Object,
        default: null,
    },
    organizationTypes: {
        type: Array,
        default: () => ['Academic', 'Cultural', 'Governance', 'Special Interest'],
    },
});

const emit = defineEmits(['close', 'saved']);

const isProcessing = ref(false);
const formErrors = ref({});

const form = useForm({
    org_name: '',
    org_code: '',
    description: '',
    type: '',
    status: 'active',
});

const typeOptions = computed(() => [
    { value: '', label: 'Select Type' },
    ...props.organizationTypes.map(t => ({ value: t, label: t })),
]);

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        formErrors.value = {};
        if (props.organization) {
            form.org_name = props.organization.org_name || '';
            form.org_code = props.organization.org_code || '';
            form.description = props.organization.description || '';
            form.type = props.organization.type || '';
            form.status = props.organization.status || 'active';
        } else {
            form.reset();
            form.status = 'active';
        }
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    const url = props.organization
        ? route('admin.organizations.update', props.organization.org_id)
        : route('admin.organizations.store');

    const method = props.organization ? 'put' : 'post';

    router[method](url, form, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
        onError: (errors) => {
            formErrors.value = errors;
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const close = () => {
    form.reset();
    formErrors.value = {};
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                {{ organization ? 'Edit Organization' : 'Add Organization' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Organization Name -->
                <div class="mb-4">
                    <InputLabel for="org_name" value="Organization Name" />
                    <TextInput
                        id="org_name"
                        v-model="form.org_name"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.org_name }"
                        required
                    />
                    <InputError :message="formErrors.org_name" />
                </div>

                <!-- Organization Code -->
                <div class="mb-4">
                    <InputLabel for="org_code" value="Organization Code" />
                    <TextInput
                        id="org_code"
                        v-model="form.org_code"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{ 'border-red-300': formErrors.org_code }"
                        required
                    />
                    <InputError :message="formErrors.org_code" />
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.description }"
                    />
                    <InputError :message="formErrors.description" />
                </div>

                <!-- Type -->
                <div class="mb-4">
                    <InputLabel for="type" value="Type" />
                    <select
                        id="type"
                        v-model="form.type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        :class="{ 'border-red-300': formErrors.type }"
                    >
                        <option
                            v-for="option in typeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="formErrors.type" />
                </div>

                <!-- Status -->
                <div class="mb-6">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        {{ isProcessing ? 'Saving...' : (organization ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

