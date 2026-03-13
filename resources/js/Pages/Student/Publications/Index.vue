<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    articles: Array,
    newspapers: Array,
    galleries: Array,
});

const statusColors = {
    draft: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
    pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};
</script>

<template>
    <Head title="My Publications" />
    <StudentLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">My Publications</h2>
        </template>

        <div class="space-y-8">
            <!-- Articles -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Articles ({{ articles.length }})</h3>
                    <Link :href="route('student.publications.articles.create')"
                        class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition">
                        + New Article
                    </Link>
                </div>
                <div v-if="articles.length" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/50">
                    <div v-for="article in articles" :key="article.article_id" class="flex items-center justify-between p-4 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <img v-if="article.cover_image" :src="article.cover_image" class="h-10 w-14 object-cover rounded-lg flex-shrink-0" />
                            <div class="h-10 w-14 bg-slate-100 dark:bg-slate-700 rounded-lg flex-shrink-0 flex items-center justify-center" v-else>
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ article.title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ article.created_at }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize hidden sm:inline-block" :class="statusColors[article.status]">
                                {{ article.status.replace('_', ' ') }}
                            </span>
                            <Link :href="route('student.publications.articles.show', article.slug)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">
                                View
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-8 text-center text-sm text-slate-400">
                    No articles yet. Create your first one!
                </div>
            </section>

            <!-- Newspapers -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Newspaper Issues ({{ newspapers.length }})</h3>
                    <Link :href="route('student.publications.newspapers.create')"
                        class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition">
                        + New Issue
                    </Link>
                </div>
                <div v-if="newspapers.length" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/50">
                    <div v-for="newspaper in newspapers" :key="newspaper.newspaper_id" class="flex items-center justify-between p-4 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ newspaper.title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ newspaper.issue_number || 'No issue number' }} · {{ newspaper.created_at }}</p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize hidden sm:inline-block" :class="statusColors[newspaper.status]">
                                {{ newspaper.status.replace('_', ' ') }}
                            </span>
                            <Link :href="route('student.publications.newspapers.show', newspaper.slug)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">View</Link>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-8 text-center text-sm text-slate-400">
                    No newspaper issues yet.
                </div>
            </section>

            <!-- Galleries -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Photo Galleries ({{ galleries.length }})</h3>
                    <Link :href="route('student.publications.galleries.create')"
                        class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition">
                        + New Gallery
                    </Link>
                </div>
                <div v-if="galleries.length" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/50">
                    <div v-for="gallery in galleries" :key="gallery.gallery_id" class="flex items-center justify-between p-4 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <img v-if="gallery.cover_image" :src="gallery.cover_image" class="h-10 w-14 object-cover rounded-lg flex-shrink-0" />
                            <div class="h-10 w-14 bg-slate-100 dark:bg-slate-700 rounded-lg flex-shrink-0 flex items-center justify-center" v-else>
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ gallery.title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ gallery.photos_count }} photos · {{ gallery.created_at }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize hidden sm:inline-block" :class="statusColors[gallery.status]">
                                {{ gallery.status.replace('_', ' ') }}
                            </span>
                            <Link :href="route('student.publications.galleries.show', gallery.slug)" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">View</Link>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-8 text-center text-sm text-slate-400">
                    No galleries yet.
                </div>
            </section>
        </div>
    </StudentLayout>
</template>
