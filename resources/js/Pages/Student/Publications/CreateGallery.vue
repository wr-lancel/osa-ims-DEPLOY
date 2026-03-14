<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const form = useForm({
    title: '',
    description: '',
    cover_image: null,
    status: 'pending_review',
    photos: [],
    captions: [],
});

const coverPreview = ref(null);
const photoPreviews = ref([]);

function onCoverChange(e) {
    const file = e.target.files[0];
    if (file) { form.cover_image = file; coverPreview.value = URL.createObjectURL(file); }
}

function onPhotosChange(e) {
    const files = Array.from(e.target.files);
    form.photos = files;
    form.captions = files.map(() => '');
    photoPreviews.value = files.map(f => URL.createObjectURL(f));
}

function removePhoto(index) {
    form.photos.splice(index, 1);
    form.captions.splice(index, 1);
    photoPreviews.value.splice(index, 1);
}

function submit() {
    form.post(route('student.publications.galleries.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="New Gallery" />
    <StudentLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('student.publications.index')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Create Photo Gallery</h2>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6 max-w-3xl">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" placeholder="Gallery name..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="3" placeholder="Describe the event or occasion..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 resize-none outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cover Image</label>
                    <img v-if="coverPreview" :src="coverPreview" class="h-28 w-full object-cover rounded-lg mb-2 border border-slate-200 dark:border-slate-700" />
                    <input type="file" accept="image/*" @change="onCoverChange"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Photos</label>
                    <input type="file" accept="image/*" multiple @change="onPhotosChange"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition" />
                    <div v-if="photoPreviews.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                        <div v-for="(preview, i) in photoPreviews" :key="i" class="relative group">
                            <img :src="preview" class="w-full h-24 object-cover rounded-lg border border-slate-200 dark:border-slate-700" />
                            <button type="button" @click="removePhoto(i)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                            <input v-model="form.captions[i]" type="text" placeholder="Caption..."
                                class="w-full mt-1 border border-slate-300 dark:border-slate-600 rounded px-2 py-1 text-xs bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 outline-none" />
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Save As</label>
                    <select v-model="form.status" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <option value="draft">Draft</option>
                        <option value="pending_review">Submit for Review</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition">
                    {{ form.processing ? 'Submitting...' : 'Submit Gallery' }}
                </button>
                <Link :href="route('student.publications.index')" class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">Cancel</Link>
            </div>
        </form>

        <LoadingOverlay :show="form.processing" message="Submitting... Please wait." />
    </StudentLayout>
</template>
