<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { ref } from 'vue';

const props = defineProps({ gallery: Object });
const lightboxIndex = ref(null);
</script>

<template>
    <Head :title="`${gallery.title} — OSA Publications`" />

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

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">{{ gallery.title }}</h1>
                <div class="flex items-center gap-4 mt-2 text-sm text-slate-500 dark:text-slate-400">
                    <span>By {{ gallery.author_name }}</span>
                    <span v-if="gallery.published_at">{{ gallery.published_at }}</span>
                    <span>{{ gallery.photos.length }} photos</span>
                </div>
                <p v-if="gallery.description" class="mt-3 text-slate-600 dark:text-slate-400">{{ gallery.description }}</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <div v-for="(photo, index) in gallery.photos" :key="photo.photo_id"
                    @click="lightboxIndex = index"
                    class="group relative overflow-hidden rounded-xl cursor-pointer aspect-square bg-slate-200 dark:bg-slate-700">
                    <img :src="photo.image_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    <div v-if="photo.caption" class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs p-2 opacity-0 group-hover:opacity-100 transition-opacity truncate">{{ photo.caption }}</div>
                </div>
            </div>
        </main>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxIndex !== null"
                class="fixed inset-0 z-50 bg-black/95 flex flex-col items-center justify-center p-4"
                @click.self="lightboxIndex = null">
                <button @click="lightboxIndex = null" class="absolute top-4 right-4 text-white/70 hover:text-white w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition text-2xl">×</button>

                <div class="relative flex items-center max-w-5xl w-full">
                    <button v-if="lightboxIndex > 0" @click="lightboxIndex--"
                        class="absolute -left-4 sm:left-0 z-10 text-white/70 hover:text-white w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition text-2xl">‹</button>

                    <img :src="gallery.photos[lightboxIndex]?.image_path"
                        class="max-h-[80vh] max-w-full mx-auto rounded-xl object-contain" />

                    <button v-if="lightboxIndex < gallery.photos.length - 1" @click="lightboxIndex++"
                        class="absolute -right-4 sm:right-0 z-10 text-white/70 hover:text-white w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition text-2xl">›</button>
                </div>

                <p v-if="gallery.photos[lightboxIndex]?.caption" class="text-white/60 text-sm mt-4 text-center max-w-xl">
                    {{ gallery.photos[lightboxIndex].caption }}
                </p>
                <p class="text-white/40 text-xs mt-2">{{ lightboxIndex + 1 }} / {{ gallery.photos.length }}</p>
            </div>
        </Teleport>
    </div>
</template>
