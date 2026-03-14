<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    latestArticles: { type: Array, default: () => [] },
    latestNewspapers: { type: Array, default: () => [] },
    latestGalleries: { type: Array, default: () => [] },
    totalEnrolled: { type: Number, default: 0 },
    coursesOffered: { type: Number, default: 0 },
    totalOrganizations: { type: Number, default: 0 },
});

// Edit this number to update the "Years Serving" stat
const yearsServing = 20;

const scrollTo = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const services = [
    {
        title: 'Discipline Unit',
        description: 'Handles cases of student misconduct and violations. We ensure a fair, transparent, and structured disciplinary process for every student.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Organization Unit',
        description: 'Supports all recognized student organizations — from officer elections and event approvals to membership and meeting management.',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        title: 'Guidance Unit',
        description: 'Provides counseling services and appointment scheduling to help students navigate academic, personal, and emotional challenges.',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
    {
        title: 'Sports Unit',
        description: 'Manages sports equipment borrowing, maintains athlete records, and oversees all campus sports activities and programs.',
        icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Publication Unit',
        description: 'Home to the official campus publication — student articles, newspaper issues, and photo galleries documenting campus life.',
        icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
    },
];
</script>

<template>
    <Head title="OSA Information Management System" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 overflow-x-hidden transition-colors duration-200">
        <!-- Header / Navbar -->
        <header class="sticky top-0 z-50 bg-white dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-3 min-w-0">
                    <!-- Brand -->
                    <div class="flex items-center gap-3 flex-shrink-0 min-w-0">
                        <img src="/images/OSA-NEW-LOGO.png" alt="OSA Logo" class="h-11 w-11 rounded-xl object-contain border border-slate-200 dark:border-slate-700" />
                        <div class="leading-tight">
                            <div class="text-sm font-bold text-slate-900 dark:text-white">OSA-IMS</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Information Management System</div>
                        </div>
                    </div>

                    <!-- Navigation Links & Theme Toggle -->
                    <nav class="flex items-center gap-4 flex-shrink-0 flex-wrap justify-end">
                        <a href="#publications" @click.prevent="scrollTo('publications')" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors hidden sm:block">
                            Publications
                        </a>
                        <a href="#services" @click.prevent="scrollTo('services')" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors hidden sm:block">
                            Services
                        </a>
                        <a href="#good-moral" @click.prevent="scrollTo('good-moral')" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors hidden sm:block">
                            Request Docs
                        </a>
                        <a href="#about" @click.prevent="scrollTo('about')" class="text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors hidden sm:block">
                            About
                        </a>

                        <!-- Facebook -->
                        <a href="https://www.facebook.com/CHCC.StudentAffairsAndServices" target="_blank" rel="noopener noreferrer"
                            class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors hidden sm:block"
                            aria-label="OSA Facebook Page">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                        </a>
                        <ThemeToggle />

                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition-colors">
                                Dashboard
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                            <Link v-else :href="route('login')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition-colors">
                                Sign In
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50/30 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900/20 transition-colors duration-200"></div>
            <div class="absolute top-20 right-0 w-96 h-96 bg-indigo-100/30 dark:bg-indigo-500/10 rounded-full blur-3xl transition-colors duration-200"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-slate-100/50 dark:bg-slate-800/50 rounded-full blur-3xl transition-colors duration-200"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-28 lg:pb-32">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 mb-6 transition-colors">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Concepcion Holy Cross College, Inc.</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 dark:text-white tracking-tight leading-tight transition-colors">
                        Office of Student
                        <span class="block text-indigo-600 dark:text-indigo-400">Affairs & Services</span>
                    </h1>

                    <p class="mt-6 text-lg text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed transition-colors">
                        Serving students of CHCC through discipline, guidance, organizations, sports, and publications — all in one integrated platform.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4 min-w-0">
                        <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-indigo-600 text-white text-sm font-semibold hover:bg-slate-800 dark:hover:bg-indigo-500 shadow-lg shadow-slate-900/10 dark:shadow-indigo-900/20 transition-all hover:shadow-xl">
                            Student Login
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </Link>
                        <Link v-if="canLogin && $page.props.auth.user" :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-indigo-600 text-white text-sm font-semibold hover:bg-slate-800 dark:hover:bg-indigo-500 shadow-lg transition-all hover:shadow-xl">
                            Go to Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </Link>
                        
                    </div>
                </div>
            </div>
        </section>

        <!-- Publications Section -->
        <section id="publications" class="py-20 lg:py-24 bg-white dark:bg-slate-900 border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Latest Publications</h2>
                        <p class="mt-2 text-slate-500 dark:text-slate-400 text-sm">News and stories from our campus publication.</p>
                    </div>
                    <Link :href="route('publications.index')" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline hidden sm:block">
                        View All →
                    </Link>
                </div>

                <div v-if="latestArticles.length" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <Link :href="route('publications.articles.show', latestArticles[0].slug)"
                        class="group md:col-span-2 relative overflow-hidden rounded-2xl bg-slate-900 dark:bg-slate-800 min-h-72 flex flex-col justify-end hover:shadow-xl transition-all duration-300">
                        <img v-if="latestArticles[0].cover_image" :src="latestArticles[0].cover_image"
                            class="absolute inset-0 w-full h-full object-cover opacity-40 group-hover:opacity-50 transition-opacity duration-300" />
                        <div class="relative p-6 sm:p-8">
                            <p class="text-xs font-medium text-indigo-300 mb-2">{{ latestArticles[0].author_name }} · {{ latestArticles[0].published_at }}</p>
                            <h3 class="text-xl sm:text-2xl font-bold text-white leading-snug group-hover:text-indigo-200 transition-colors">{{ latestArticles[0].title }}</h3>
                            <p v-if="latestArticles[0].excerpt" class="mt-2 text-sm text-slate-300 line-clamp-2">{{ latestArticles[0].excerpt }}</p>
                        </div>
                    </Link>
                    <div class="space-y-4">
                        <Link v-for="article in latestArticles.slice(1, 4)" :key="article.article_id"
                            :href="route('publications.articles.show', article.slug)"
                            class="group flex gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700/50 border border-slate-200 dark:border-slate-700 transition-colors">
                            <img v-if="article.cover_image" :src="article.cover_image" class="h-16 w-20 object-cover rounded-lg flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ article.published_at }}</p>
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ article.title }}</h4>
                            </div>
                        </Link>
                    </div>
                </div>

                <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 p-12 text-center mb-12">
                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">No articles published yet</p>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Check back soon for the latest campus news.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Newspaper Issues</h3>
                        <div v-if="latestNewspapers.length" class="space-y-3">
                            <Link v-for="newspaper in latestNewspapers" :key="newspaper.newspaper_id"
                                :href="route('publications.newspapers.show', newspaper.slug)"
                                class="group flex items-center gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-indigo-200 dark:hover:border-indigo-800 transition-colors">
                                <div class="h-14 w-10 rounded bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ newspaper.published_at }}<span v-if="newspaper.issue_number"> · {{ newspaper.issue_number }}</span></p>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ newspaper.title }}</h4>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-slate-200 dark:border-slate-700 p-6 text-center text-sm text-slate-400">
                            No issues published yet.
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Photo Galleries</h3>
                        <div v-if="latestGalleries.length" class="grid grid-cols-2 gap-3">
                            <Link v-for="gallery in latestGalleries" :key="gallery.gallery_id"
                                :href="route('publications.galleries.show', gallery.slug)"
                                class="group relative overflow-hidden rounded-xl aspect-video bg-slate-200 dark:bg-slate-700">
                                <img v-if="gallery.cover_image" :src="gallery.cover_image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <div class="absolute inset-0 bg-black/40 flex flex-col justify-end p-2">
                                    <p class="text-white text-xs font-semibold line-clamp-1">{{ gallery.title }}</p>
                                    <p class="text-white/70 text-[10px]">{{ gallery.photos_count }} photos</p>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-slate-200 dark:border-slate-700 p-6 text-center text-sm text-slate-400">
                            No galleries published yet.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Services Section -->
        <section id="services" class="py-20 lg:py-28 bg-white dark:bg-slate-900 border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Our Services</h2>
                    <p class="mt-3 text-base text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                        The OSA oversees five key units that serve the holistic needs of every student at CHCC.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div v-for="(service, index) in services" :key="index"
                        class="group relative bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-lg hover:shadow-slate-100 dark:hover:shadow-slate-900/50 transition-all duration-300">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/30 group-hover:border-indigo-200 dark:group-hover:border-indigo-800 transition-colors">
                                <svg class="w-5 h-5 text-slate-600 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="service.icon" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ service.title }}</h3>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ service.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Good Moral Certificate Section -->
        <section id="good-moral" class="py-20 lg:py-28 bg-slate-50 dark:bg-slate-900 border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 mb-6 transition-colors">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                            <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">For Alumni & Former Students</span>
                        </div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white transition-colors">Certificate of Good Moral Character</h2>
                        <p class="mt-4 text-base text-slate-500 dark:text-slate-400 leading-relaxed transition-colors">
                            Alumni and former students of CHCC can now request a Certificate of Good Moral Character online — no need to visit the office first. Fill out the form, pay at the Cashier, and pick up your certificate at the Office of the Student Affairs and Services.
                        </p>
                        <Link :href="route('good-moral.create')"
                            class="mt-8 inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-indigo-600 text-white text-sm font-semibold hover:bg-slate-800 dark:hover:bg-indigo-500 shadow-lg shadow-slate-900/10 dark:shadow-indigo-900/20 transition-all hover:shadow-xl">
                            Request a Certificate
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </Link>
                    </div>

                    <!-- 3-Step Process -->
                    <div class="space-y-5">
                        <div class="flex items-start gap-5 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-colors">
                            <div class="h-12 w-12 rounded-xl bg-indigo-600 text-white font-bold text-lg flex items-center justify-center flex-shrink-0">1</div>
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white">Submit Your Request Online</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fill out the online form with your personal and student details, along with the purpose of your request.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-5 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-colors">
                            <div class="h-12 w-12 rounded-xl bg-slate-900 dark:bg-slate-700 text-white font-bold text-lg flex items-center justify-center flex-shrink-0">2</div>
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white">Pay at the Cashier</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Once your request is received, visit the CHCC Cashier to settle the processing fee for the certificate.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-5 p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-colors">
                            <div class="h-12 w-12 rounded-xl bg-emerald-600 text-white font-bold text-lg flex items-center justify-center flex-shrink-0">3</div>
                            <div>
                                <h4 class="font-semibold text-slate-900 dark:text-white">Pick Up at the Office of the Student Affairs and Services</h4>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Your Certificate of Good Moral Character will be prepared and ready for pick-up at the Office of the Student Affairs and Services.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About the OSA / Stats Section -->
        <section id="about" class="py-20 lg:py-28 bg-white dark:bg-slate-900 transition-colors duration-200 scroll-mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 mb-6 transition-colors">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">About the OSA</span>
                        </div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white transition-colors">Office of Student Affairs and Services</h2>
                        <p class="mt-4 text-base text-slate-500 dark:text-slate-400 leading-relaxed transition-colors">
                            The Office of Student Affairs and Services (OSAS) of Concepcion Holy Cross College, Inc. is the central unit that oversees and coordinates all non-academic student programs and services. We are committed to fostering holistic student development — supporting academic success, character formation, and active campus life.
                        </p>
                        <div class="mt-6 space-y-3">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 transition-colors">
                                <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Our Mission</p>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">To provide quality student services that promote the holistic development of every student at CHCC.</p>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 transition-colors">
                                <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Our Vision</p>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">A center of excellence in student affairs, empowering students to become responsible, well-rounded individuals ready to serve the community.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Stats -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-slate-900 dark:bg-slate-800 p-6 text-white transition-colors">
                            <div class="text-3xl font-bold">{{ totalEnrolled.toLocaleString() }}</div>
                            <div class="text-sm text-slate-400 mt-1">Enrolled Students</div>
                        </div>
                        <div class="rounded-2xl bg-indigo-600 dark:bg-indigo-500 p-6 text-white transition-colors">
                            <div class="text-3xl font-bold">{{ coursesOffered }}</div>
                            <div class="text-sm text-indigo-200 mt-1">Courses Offered</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 transition-colors">
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ totalOrganizations }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">Student Organizations</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 transition-colors">
                            <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ yearsServing }}+</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">Years Serving</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white dark:bg-slate-900 border-t border-slate-200/70 dark:border-slate-800/70 py-10 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <img src="/images/OSA-NEW-LOGO.png" alt="OSA Logo" class="h-8 w-8 rounded-xl object-contain border border-slate-200 dark:border-slate-700" />
                        <span class="text-sm font-semibold text-slate-900 dark:text-white transition-colors">OSA-IMS</span>
                    </div>
                    <p class="text-sm text-slate-500 text-center">
                        Office of Student Affairs and Services — Concepcion Holy Cross College, Inc.
                    </p>
                    <a href="https://www.facebook.com/example-osa-page" target="_blank" rel="noopener noreferrer"
                        class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                        </svg>
                        Facebook Page
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>
