<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    text: {
        type: String,
        default: '',
    },
    existingFileUrl: {
        type: String,
        default: null,
    },
    existingFileName: {
        type: String,
        default: null,
    },
    accept: {
        type: String,
        default: '.pdf,.doc,.docx',
    },
    placeholder: {
        type: String,
        default: 'Type here...',
    },
    rows: {
        type: [Number, String],
        default: 4,
    },
    textError: {
        type: String,
        default: '',
    },
    fileError: {
        type: String,
        default: '',
    },
    helpText: {
        type: String,
        default: 'You may type text, upload a document, or do both.',
    },
});

const emit = defineEmits(['update:text', 'update:file', 'remove-file']);

const fileInput = ref(null);
const selectedFileName = ref(null);

const onTextInput = (e) => {
    emit('update:text', e.target.value);
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFileName.value = file.name;
        emit('update:file', file);
    }
};

const removeFile = () => {
    selectedFileName.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    emit('remove-file');
};

const clearSelectedFile = () => {
    selectedFileName.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    emit('update:file', null);
};
</script>

<template>
    <div class="space-y-2">
        <InputLabel :value="label" />
        <p v-if="helpText" class="text-xs text-gray-400">{{ helpText }}</p>

        <!-- Text area -->
        <textarea
            :value="text"
            @input="onTextInput"
            :rows="rows"
            :placeholder="placeholder"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        />
        <InputError :message="textError" />

        <!-- File upload -->
        <div class="flex items-center gap-3">
            <label
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm text-gray-700 cursor-pointer hover:bg-gray-50 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                {{ selectedFileName || 'Attach file' }}
                <input
                    ref="fileInput"
                    type="file"
                    :accept="accept"
                    class="hidden"
                    @change="onFileChange"
                />
            </label>

            <button
                v-if="selectedFileName"
                type="button"
                class="text-xs text-red-500 hover:text-red-700"
                @click="clearSelectedFile"
            >
                Remove
            </button>
        </div>
        <InputError :message="fileError" />

        <!-- Existing file display -->
        <div v-if="existingFileUrl && !selectedFileName" class="flex items-center gap-2 mt-1 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <a :href="existingFileUrl" target="_blank" class="text-indigo-600 hover:text-indigo-900 underline">
                {{ existingFileName || 'Download current file' }}
            </a>
            <button
                type="button"
                class="text-xs text-red-500 hover:text-red-700"
                @click="removeFile"
            >
                Remove
            </button>
        </div>
    </div>
</template>
