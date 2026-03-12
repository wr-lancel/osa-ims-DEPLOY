<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

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
    logo: null,
    remove_logo: false,
    org_code: '',
    description: '',
    type: '',
    status: 'active',
});

const logoPreview = ref(null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    logoPreview.value = null;
};

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
            form.logo = null;
            form.remove_logo = false;
            logoPreview.value = props.organization.logo_url || null;
        } else {
            form.reset();
            form.status = 'active';
            logoPreview.value = null;
        }
    }
});

const submit = () => {
    isProcessing.value = true;
    formErrors.value = {};

    // When dealing with files and the PUT method, Inertia recommends using POST with _method=PUT
    const url = props.organization
        ? route('admin.organizations.update', props.organization.org_id)
        : route('admin.organizations.store');

    if (props.organization) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(url, {
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
    } else {
        form.post(url, {
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
    }
};

const close = () => {
    form.reset();
    formErrors.value = {};
    emit('close');
};
</script>

<template>
    <LoadingOverlay :show="isProcessing" message="Saving organization... Please wait." />
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                {{ organization ? 'Edit Organization' : 'Add Organization' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Organization Logo -->
                <div class="mb-6">
                    <InputLabel value="Organization Logo" class="mb-2" />
                    <div class="flex items-center gap-4">
                        <div class="relative h-20 w-20 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900 flex-shrink-0">
                            <img v-if="logoPreview" :src="logoPreview" class="h-full w-full object-cover" alt="Logo preview" />
                            <svg v-else class="h-8 w-8 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            
                            <button v-if="logoPreview" type="button" @click.prevent="removeLogo" class="absolute top-1 right-1 bg-red-100 text-red-600 dark:text-red-400 rounded-full p-1 hover:bg-red-200 transition-colors" title="Remove Logo">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="flex-1">
                            <input type="file" id="logo" @change="handleLogoChange" accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">PNG, JPG, GIF up to 5MB (Square ratio recommended)</p>
                            <InputError :message="formErrors.logo" class="mt-1" />
                        </div>
                    </div>
                </div>

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
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
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
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100"
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
                        {{ isProcessing ? 'Saving...' : (organization ? 'Update' : 'Create') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

