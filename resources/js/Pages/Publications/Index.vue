<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    articles: Object,
    newspapers: Object,
    galleries: Object,
});
</script>

<template>
    <Head title="Publications — OSAS-IMS" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200">
        <!-- Navbar -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center transition-colors">
                            <div class="h-3 w-3 rounded bg-slate-900 dark:bg-slate-100 transition-colors"></div>
                        </div>
                        <span class="text-sm font-bold text-slate-900 dark:text-white">OSAS-IMS</span>
                    </Link>
                    <div class="flex items-center gap-3">
                        <ThemeToggle />
                        <Link href="/" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">← Back to Home</Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
            <!-- Articles -->
            <section>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Articles</h2>
                <div v-if="articles.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="article in articles.data" :key="article.article_id"
                        :href="route('publications.articles.show', article.slug)"
                        class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg hover:shadow-slate-200/60 dark:hover:shadow-slate-900/60 hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-300">
                        <div class="h-44 bg-slate-100 dark:bg-slate-700 overflow-hidden">
                            <img v-if="article.cover_image" :src="article.cover_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                        </div>
                        <div class="p-5">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">{{ article.published_at }} · {{ article.author_name }}</p>
                            <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ article.title }}</h3>
                            <p v-if="article.excerpt" class="text-sm text-slate-500 dark:text-slate-400 mt-2 line-clamp-2">{{ article.excerpt }}</p>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-slate-500 dark:text-slate-400">No articles published yet.</p>
            </section>

            <!-- Newspapers -->
            <section>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Newspaper Issues</h2>
                <div v-if="newspapers.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link v-for="newspaper in newspapers.data" :key="newspaper.newspaper_id"
                        :href="route('publications.newspapers.show', newspaper.slug)"
                        class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="h-44 bg-slate-100 dark:bg-slate-700 overflow-hidden">
                            <img v-if="newspaper.cover_image" :src="newspaper.cover_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ newspaper.published_at }}</p>
                                <span v-if="newspaper.issue_number" class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ newspaper.issue_number }}</span>
                            </div>
                            <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ newspaper.title }}</h3>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-slate-500 dark:text-slate-400">No newspaper issues published yet.</p>
            </section>

            <!-- Galleries -->
            <section>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Photo Galleries</h2>
                <div v-if="galleries.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link v-for="gallery in galleries.data" :key="gallery.gallery_id"
                        :href="route('publications.galleries.show', gallery.slug)"
                        class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="h-36 bg-slate-100 dark:bg-slate-700 overflow-hidden relative">
                            <img v-if="gallery.cover_image" :src="gallery.cover_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs font-medium px-2 py-0.5 rounded-full">{{ gallery.photos_count }} photos</div>
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ gallery.published_at }}</p>
                            <h3 class="font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors text-sm">{{ gallery.title }}</h3>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-slate-500 dark:text-slate-400">No galleries published yet.</p>
            </section>
        </main>
    </div>
</template>
