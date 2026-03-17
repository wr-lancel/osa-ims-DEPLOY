<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({ article: Object });
</script>

<template>
    <Head :title="`${article.title} — OSA Publications`" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200">
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center"><div class="h-3 w-3 rounded bg-slate-900 dark:bg-slate-100"></div></div>
                        <span class="text-sm font-bold text-slate-900 dark:text-white">OSAS-IMS</span>
                    </Link>
                    <div class="flex items-center gap-3">
                        <ThemeToggle />
                        <Link :href="route('publications.index')" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">← Publications</Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Cover -->
            <div v-if="article.cover_image" class="rounded-2xl overflow-hidden mb-8 shadow-lg">
                <img :src="article.cover_image" class="w-full max-h-72 object-cover" />
            </div>

            <!-- Meta -->
            <div class="mb-6">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white leading-tight">{{ article.title }}</h1>
                <div class="flex items-center gap-4 mt-4 text-sm text-slate-500 dark:text-slate-400">
                    <span>By {{ article.author_name }}</span>
                    <span v-if="article.published_at">{{ article.published_at }}</span>
                </div>
                <p v-if="article.excerpt" class="mt-4 text-lg text-slate-600 dark:text-slate-400 italic border-l-4 border-indigo-300 dark:border-indigo-700 pl-4 leading-relaxed">{{ article.excerpt }}</p>
            </div>

            <!-- Body -->
            <div class="prose prose-slate dark:prose-invert max-w-none prose-lg" v-html="article.body" />
        </main>
    </div>
</template>
