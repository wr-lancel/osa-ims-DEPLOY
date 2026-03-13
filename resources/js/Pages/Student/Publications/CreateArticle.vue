<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    title: '',
    body: '',
    excerpt: '',
    cover_image: null,
    status: 'draft',
});

const coverPreview = ref(null);

function onCoverChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        coverPreview.value = URL.createObjectURL(file);
    }
}

function saveDraft() {
    form.status = 'draft';
    form.post(route('student.publications.articles.store'), { forceFormData: true });
}

function submit() {
    form.status = 'pending_review';
    form.post(route('student.publications.articles.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="New Article" />
    <StudentLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('student.publications.index')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">New Article</h2>
            </div>
        </template>

        <div class="space-y-6 max-w-4xl">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" placeholder="Article title..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Excerpt</label>
                    <textarea v-model="form.excerpt" rows="2" placeholder="Short summary (optional)..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cover Image</label>
                    <div v-if="coverPreview" class="mb-3">
                        <img :src="coverPreview" class="h-40 w-full object-cover rounded-lg border border-slate-200 dark:border-slate-700" />
                    </div>
                    <input type="file" accept="image/*" @change="onCoverChange"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Body <span class="text-red-500">*</span></label>
                    <textarea v-model="form.body" rows="12" placeholder="Write article content here..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 resize-y focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition font-mono"></textarea>
                    <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="saveDraft" :disabled="form.processing"
                    class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition">
                    Save as Draft
                </button>
                <button type="button" @click="submit" :disabled="form.processing"
                    class="px-6 py-2.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition">
                    {{ form.processing ? 'Submitting...' : 'Submit for Review' }}
                </button>
                <Link :href="route('student.publications.index')"
                    class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Cancel
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
