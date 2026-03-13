<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ article: Object });

const editing = ref(false);
const showRejectModal = ref(false);

const editForm = useForm({
    title: props.article.title,
    excerpt: props.article.excerpt || '',
    body: props.article.body || '',
    status: props.article.status,
});

const reviewForm = useForm({ action: '', rejection_reason: '' });

function saveEdit() {
    editForm.put(route('admin.publications.articles.update', props.article.slug), {
        onSuccess: () => { editing.value = false; },
    });
}

function approve() {
    reviewForm.action = 'approve';
    reviewForm.post(route('admin.publications.articles.review', props.article.slug));
}

function reject() {
    reviewForm.action = 'reject';
    reviewForm.post(route('admin.publications.articles.review', props.article.slug), {
        onSuccess: () => { showRejectModal.value = false; },
    });
}

function destroy() {
    if (confirm('Delete this article? This cannot be undone.')) {
        router.delete(route('admin.publications.articles.destroy', props.article.slug));
    }
}
</script>

<template>
    <Head :title="article.title" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.publications.index', { tab: 'articles' })" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Article</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="!editing" @click="editing = true"
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
            <!-- Status + Review Actions -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                        :class="{
                            'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400': article.status === 'draft',
                            'bg-amber-100 text-amber-700': article.status === 'pending_review',
                            'bg-green-100 text-green-700': article.status === 'published',
                            'bg-red-100 text-red-700': article.status === 'rejected',
                        }">
                        {{ article.status.replace('_', ' ') }}
                    </span>
                    <span class="text-sm text-slate-500 dark:text-slate-400">by {{ article.author_name }}</span>
                </div>
                <div v-if="article.status === 'pending_review'" class="flex items-center gap-2">
                    <button @click="approve" :disabled="reviewForm.processing"
                        class="px-4 py-1.5 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-500 disabled:opacity-50 transition">
                        Approve & Publish
                    </button>
                    <button @click="showRejectModal = true"
                        class="px-4 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-500 transition">
                        Reject
                    </button>
                </div>
            </div>

            <!-- Rejection Reason -->
            <div v-if="article.rejection_reason" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-700 dark:text-red-400">
                <strong>Rejection reason:</strong> {{ article.rejection_reason }}
            </div>

            <!-- View Mode -->
            <div v-if="!editing" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <img v-if="article.cover_image" :src="article.cover_image" class="w-full h-64 object-cover rounded-lg mb-6" />
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ article.title }}</h1>
                <p v-if="article.excerpt" class="text-slate-500 dark:text-slate-400 mb-6 italic">{{ article.excerpt }}</p>
                <div class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ article.body }}</div>
            </div>

            <!-- Edit Mode -->
            <form v-else @submit.prevent="saveEdit" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Title</label>
                    <input v-model="editForm.title" type="text"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Excerpt</label>
                    <textarea v-model="editForm.excerpt" rows="2"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Body</label>
                    <textarea v-model="editForm.body" rows="15"
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-y outline-none focus:ring-2 focus:ring-indigo-500 transition font-mono"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Status</label>
                    <select v-model="editForm.status"
                        class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="editForm.processing"
                        class="px-6 py-2.5 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition">
                        Save Changes
                    </button>
                    <button type="button" @click="editing = false"
                        class="px-6 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reject Article</h3>
                    <textarea v-model="reviewForm.rejection_reason" rows="4" placeholder="Reason for rejection..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none outline-none focus:ring-2 focus:ring-indigo-500 transition mb-4"></textarea>
                    <div class="flex gap-3">
                        <button @click="reject" :disabled="reviewForm.processing"
                            class="flex-1 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-500 disabled:opacity-50 transition">
                            Reject
                        </button>
                        <button @click="showRejectModal = false"
                            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
