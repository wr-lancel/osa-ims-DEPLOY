<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({ newspaper: Object });

const editing = ref(false);
const isProcessing = ref(false);

const editForm = useForm({
    title: props.newspaper.title,
    issue_number: props.newspaper.issue_number || '',
    excerpt: props.newspaper.excerpt || '',
    body: props.newspaper.body || '',
    status: props.newspaper.status === 'rejected' ? 'pending_review' : props.newspaper.status,
});

function saveEdit() {
    editForm.put(route('student.publications.newspapers.update', props.newspaper.slug), {
        onSuccess: () => { editing.value = false; },
    });
}

function resubmit() {
    editForm.status = 'pending_review';
    saveEdit();
}

function destroy() {
    if (confirm('Delete this newspaper issue?')) {
        isProcessing.value = true;
        router.delete(route('student.publications.newspapers.destroy', props.newspaper.slug), {
            onFinish: () => { isProcessing.value = false; },
        });
    }
}
</script>

<template>
    <Head :title="newspaper.title" />
    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('student.publications.index')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">My Newspaper Issue</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="!editing && newspaper.status !== 'published'" @click="editing = true"
                        class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Edit
                    </button>
                    <button @click="destroy"
                        class="px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-4xl space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                    :class="{
                        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400': newspaper.status === 'draft',
                        'bg-amber-100 text-amber-700': newspaper.status === 'pending_review',
                        'bg-green-100 text-green-700': newspaper.status === 'published',
                        'bg-red-100 text-red-700': newspaper.status === 'rejected',
                    }">
                    {{ newspaper.status.replace('_', ' ') }}
                </span>
                <span v-if="newspaper.issue_number" class="text-sm text-slate-500 dark:text-slate-400">{{ newspaper.issue_number }}</span>
            </div>

            <div v-if="newspaper.rejection_reason" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-700 dark:text-red-400">
                <strong>Rejection reason:</strong> {{ newspaper.rejection_reason }}
            </div>

            <div v-if="!editing" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <img v-if="newspaper.cover_image" :src="newspaper.cover_image" class="w-full h-64 object-cover rounded-lg mb-6" />
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ newspaper.title }}</h1>
                <p v-if="newspaper.excerpt" class="text-slate-500 dark:text-slate-400 mb-6 italic">{{ newspaper.excerpt }}</p>
                <div class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ newspaper.body }}</div>
            </div>

            <form v-else @submit.prevent="saveEdit" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Title</label>
                    <input v-model="editForm.title" type="text"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Issue Number</label>
                    <input v-model="editForm.issue_number" type="text" placeholder="e.g. Vol. 3, Issue 5"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Excerpt</label>
                    <textarea v-model="editForm.excerpt" rows="2"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Body</label>
                    <textarea v-model="editForm.body" rows="12"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-y outline-none focus:ring-2 focus:ring-indigo-500 transition font-mono"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="editForm.processing"
                        class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition">
                        Save Draft
                    </button>
                    <button type="button" @click="resubmit" :disabled="editForm.processing"
                        class="px-6 py-2.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition">
                        Submit for Review
                    </button>
                    <button type="button" @click="editing = false"
                        class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <LoadingOverlay :show="editForm.processing || isProcessing" message="Processing... Please wait." />
    </StudentLayout>
</template>
