<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import NotificationDialog from '@/Components/NotificationDialog.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { useNotification } from '@/composables/useNotification';

const { notification, notify, closeNotification } = useNotification();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    importResult: {
        type: Object,
        default: null,
    },
    activeTerm: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    acad_id: '',
    file: null,
});

const fileInput = ref(null);

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        form.reset();
        // Set to the active term (locked)
        form.acad_id = props.activeTerm?.calendar_id || '';
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
});

const handleFileChange = (event) => {
    form.file = event.target.files[0];
};

const canSubmit = computed(() => {
    return props.activeTerm && form.file && !form.processing;
});

const submit = () => {
    if (!props.activeTerm) {
        notify('warning', 'No active term set. Please set an active term in Settings first.');
        return;
    }
    if (!form.file) {
        return;
    }

    form.acad_id = props.activeTerm.calendar_id;
    form.post(route('admin.students.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            close();
        },
    });
};

const close = () => {
    form.reset();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    emit('close');
};

const downloadTemplate = () => {
    // Create a simple CSV template with year_level column
    const template = [
        ['student_number', 'first_name', 'last_name', 'middle_name', 'email', 'phone', 'course_code', 'course_name', 'section_name', 'year_level'],
        ['2024-001', 'John', 'Doe', 'M', 'john.doe@example.com', '09123456789', 'BSIT', 'Bachelor of Science in Information Technology', 'A', '1'],
        ['2024-002', 'Jane', 'Smith', '', 'jane.smith@example.com', '09987654321', 'BSCS', 'Bachelor of Science in Computer Science', 'B', '2'],
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([template], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'student_import_template.csv';
    a.click();
    window.URL.revokeObjectURL(url);
};

const downloadErrorReport = () => {
    if (!props.importResult?.errors?.length) return;

    const csv = [
        ['Row', 'Student Number', 'Error'],
        ...props.importResult.errors.map(e => [
            e.row || '',
            e.student_number || '',
            `"${(e.error || '').replace(/"/g, '""')}"`,
        ])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'import_errors_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
};
</script>

<template>
    <LoadingOverlay :show="form.processing" message="Importing records... This might take a while for large files." />
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                Import Students from Excel
            </h2>

            <!-- Import Results -->
            <div v-if="importResult" class="mb-6 p-4 rounded-lg" :class="{
                'bg-green-50 border border-green-200': importResult.failed === 0,
                'bg-yellow-50 border border-yellow-200': importResult.failed > 0 && importResult.failed < (importResult.inserted + importResult.updated),
                'bg-red-50 border border-red-200': importResult.failed === (importResult.inserted + importResult.updated)
            }">
                <h3 class="font-semibold mb-2">
                    Import Results
                </h3>
                <ul class="text-sm space-y-1">
                    <li class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        Inserted: {{ importResult.inserted || 0 }}
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        Updated: {{ importResult.updated || 0 }}
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        Failed: {{ importResult.failed || 0 }}
                    </li>
                </ul>
                <div v-if="importResult.errors && importResult.errors.length > 0" class="mt-3">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-medium text-sm">Errors:</h4>
                        <button
                            type="button"
                            @click="downloadErrorReport"
                            class="text-xs text-indigo-600 hover:text-indigo-800 underline"
                        >
                            Download Error Report
                        </button>
                    </div>
                    <ul class="text-xs space-y-1 max-h-32 overflow-y-auto bg-white bg-opacity-50 p-2 rounded">
                        <li v-for="(error, index) in importResult.errors.slice(0, 10)" :key="index" class="text-red-700">
                            Row {{ error.row }} ({{ error.student_number }}): {{ error.error }}
                        </li>
                        <li v-if="importResult.errors.length > 10" class="text-gray-500 italic">
                            ... and {{ importResult.errors.length - 10 }} more errors. Download the full report above.
                        </li>
                    </ul>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <!-- Academic Term Display (Locked to Active Term) -->
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <InputLabel value="Academic Term" class="text-green-900" />
                        <div class="mt-1 flex items-center gap-2">
                            <span v-if="activeTerm" class="text-lg font-semibold text-green-800">
                                {{ activeTerm.display_label }}
                            </span>
                            <span
                                v-if="activeTerm"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                            >
                                Active Term
                            </span>
                            <span v-if="!activeTerm" class="text-sm text-red-600">
                                No active term set. Please set an active term in Settings first.
                            </span>
                        </div>
                        <InputError :message="form.errors.acad_id" class="mt-2" />
                        <p class="mt-1 text-xs text-green-700">
                            All imported students will be enrolled in the current active term.
                        </p>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <InputLabel for="file" value="Excel File (.xlsx, .xls, .csv) *" />
                        <input
                            ref="fileInput"
                            id="file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleFileChange"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800"
                        />
                        <InputError :message="form.errors.file" class="mt-2" />
                        <p class="mt-2 text-sm text-gray-500">
                            Upload an Excel file with student data. Maximum file size: 10MB.
                        </p>
                    </div>

                    <!-- Template Info -->
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="font-medium text-sm text-gray-900 mb-2">Supported Columns (auto-detected):</h4>
                        <p class="text-xs text-gray-600 mb-2">
                            Upload your enrolled list directly — the system will auto-detect columns by header name.
                        </p>
                        <ul class="text-xs text-gray-600 list-disc list-inside space-y-1">
                            <li><strong>Student Number</strong> — headers like: Student, Student No, ID <span class="text-red-500">(required)</span></li>
                            <li><strong>Last Name</strong> — headers like: Last Name, Surname</li>
                            <li><strong>First Name</strong> — headers like: First Name, Given Name</li>
                            <li><strong>Middle Name</strong> — headers like: Middle Name, MI</li>
                            <li><strong>Year Level</strong> — headers like: Yr, Year Level <span class="text-red-500">(required)</span></li>
                            <li><strong>Course</strong> — headers like: Course, Program <span class="text-red-500">(required, must match existing)</span></li>
                            <li><strong>Section</strong> — headers like: Section, Sec (auto-extracts letter from e.g. "BSHM-B" → "B")</li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-2">
                            Other columns (Sex, Address, Contact, etc.) will be ignored — students can fill those in their profile.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Close
                    </SecondaryButton>
                    <PrimaryButton :disabled="!canSubmit">
                        {{ form.processing ? 'Importing...' : 'Import' }}
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
