<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    student: {
        type: Object,
        default: null,
    },
    courses: {
        type: Array,
        required: true,
    },
    activeTerm: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const sections = ref([]);
const loadingSections = ref(false);
const showSuccessMessage = ref(false);
const successMessage = ref('');
const isProcessing = ref(false);
const formErrors = ref({});

const yearLevels = ['1', '2', '3', '4', '5'];

const form = useForm({
    acad_id: '',
    student_number: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    email: '',
    phone: '',
    course_id: '',
    section_id: '',
    year_level: '',
});

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        showSuccessMessage.value = false;
        formErrors.value = {};
        // Always use the active term (locked)
        form.acad_id = props.activeTerm?.calendar_id || '';
        
        if (props.student) {
            // Editing existing student
            form.student_number = props.student.student_number || '';
            form.first_name = props.student.first_name || '';
            form.last_name = props.student.last_name || '';
            form.middle_name = props.student.middle_name || '';
            form.email = props.student.email || '';
            form.phone = props.student.phone || '';
            form.course_id = props.student.course_id || '';
            form.section_id = props.student.section_id || '';
            form.year_level = props.student.year_level || '';
        } else {
            // Creating new student - reset other fields but keep acad_id
            const acadId = form.acad_id;
            form.reset();
            form.acad_id = acadId;
        }
        if (form.course_id) {
            loadSections(form.course_id);
        }
    }
});

watch(() => form.course_id, (courseId) => {
    form.section_id = '';
    sections.value = [];
    if (courseId) {
        loadSections(courseId);
    }
});

const loadSections = async (courseId) => {
    if (!courseId) {
        sections.value = [];
        return;
    }
    
    loadingSections.value = true;
    try {
        const params = { course_id: courseId };
        // Optionally filter by year_level
        if (form.year_level) {
            params.year_level = form.year_level;
        }
        const response = await axios.get(route('admin.students.sections.list'), { params });
        sections.value = response.data || [];
    } catch (error) {
        console.error('Failed to load sections:', error);
        sections.value = [];
    } finally {
        loadingSections.value = false;
    }
};

// Reload sections when year level changes
watch(() => form.year_level, () => {
    if (form.course_id) {
        loadSections(form.course_id);
    }
});

const submit = () => {
    if (isProcessing.value) return;
    
    isProcessing.value = true;
    formErrors.value = {};
    form.clearErrors();
    
    const url = props.student 
        ? route('admin.students.update', props.student.student_number)
        : route('admin.students.store');
    
    const method = props.student ? 'put' : 'post';
    const message = props.student ? 'Student updated successfully!' : 'Student enrolled successfully!';
    
    // Prepare form data
    const formData = {
        acad_id: form.acad_id,
        student_number: form.student_number,
        first_name: form.first_name,
        last_name: form.last_name,
        middle_name: form.middle_name || null,
        email: form.email || null,
        phone: form.phone || null,
        birth_date: null,
        address: null,
        course_id: form.course_id,
        section_id: form.section_id || null,
        year_level: form.year_level,
    };
    
    axios[method](url, formData, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    })
        .then((response) => {
            if (response.data.success) {
                successMessage.value = message;
                showSuccessMessage.value = true;
                emit('saved');
                setTimeout(() => {
                    emit('close');
                    form.reset();
                    formErrors.value = {};
                    showSuccessMessage.value = false;
                    isProcessing.value = false;
                    // Reload the page to refresh student list
                    router.reload({ only: ['students'] });
                }, 1500);
            } else {
                isProcessing.value = false;
                if (response.data.message) {
                    alert(response.data.message);
                }
            }
        })
        .catch((error) => {
            isProcessing.value = false;
            if (error.response?.status === 422) {
                // Handle validation errors
                const errors = error.response.data.errors || {};
                formErrors.value = errors;
                Object.keys(errors).forEach(key => {
                    form.setError(key, errors[key][0]);
                });
            } else if (error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert('Failed to save student. Please try again.');
            }
        });
};

const close = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                {{ student ? 'Edit Student' : 'Add New Student' }}
            </h2>

            <form @submit.prevent="submit">
                <!-- Success Message -->
                <div
                    v-if="showSuccessMessage"
                    class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md"
                >
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                    </div>
                </div>

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
                            Students will be enrolled in the current active term.
                        </p>
                    </div>

                    <!-- Student Number -->
                    <div>
                        <InputLabel for="student_number" value="Student Number *" />
                        <TextInput
                            id="student_number"
                            v-model="form.student_number"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500': form.errors.student_number }"
                            required
                        />
                        <InputError :message="form.errors.student_number" class="mt-2" />
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="first_name" value="First Name *" />
                            <TextInput
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.first_name }"
                                required
                            />
                            <InputError :message="form.errors.first_name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="middle_name" value="Middle Name" />
                            <TextInput
                                id="middle_name"
                                v-model="form.middle_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.middle_name }"
                            />
                            <InputError :message="form.errors.middle_name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="last_name" value="Last Name *" />
                            <TextInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.last_name }"
                                required
                            />
                            <InputError :message="form.errors.last_name" class="mt-2" />
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.email }"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500': form.errors.phone }"
                            />
                            <InputError :message="form.errors.phone" class="mt-2" />
                        </div>
                    </div>

                    <!-- Course, Year Level, and Section -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="course_id" value="Course *" />
                            <select
                                id="course_id"
                                v-model="form.course_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.course_id }"
                                required
                            >
                                <option value="">Select Course</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.course_id"
                                    :value="course.course_id"
                                >
                                    {{ course.course_code }} - {{ course.course_name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.course_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="year_level" value="Year Level *" />
                            <select
                                id="year_level"
                                v-model="form.year_level"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.year_level }"
                                required
                            >
                                <option value="">Select Year Level</option>
                                <option v-for="level in yearLevels" :key="level" :value="level">
                                    Year {{ level }}
                                </option>
                            </select>
                            <InputError :message="form.errors.year_level" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="section_id" value="Section" />
                            <select
                                id="section_id"
                                v-model="form.section_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :class="{ 'border-red-500': form.errors.section_id }"
                                :disabled="!form.course_id || loadingSections"
                            >
                                <option value="">{{ loadingSections ? 'Loading sections...' : 'Select Section (Optional)' }}</option>
                                <option
                                    v-for="section in sections"
                                    :key="section.section_id"
                                    :value="section.section_id"
                                >
                                    {{ section.section_code }} - {{ section.section_name }}
                                    <template v-if="section.year_level"> (Year {{ section.year_level }})</template>
                                </option>
                            </select>
                            <InputError :message="form.errors.section_id" class="mt-2" />
                            <p v-if="form.course_id && sections.length === 0 && !loadingSections" class="mt-1 text-xs text-gray-500">
                                No sections available for this course.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :disabled="isProcessing">
                        {{ isProcessing ? 'Saving...' : (student ? 'Update' : 'Enroll Student') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
