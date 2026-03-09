<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    {
        title: 'Discipline Unit',
        description: 'Track and manage disciplinary cases, violations, and conduct reports with a transparent workflow.',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Organization Unit',
        description: 'Manage student organizations, events, officer elections, and meeting schedules in one place.',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        title: 'Guidance Unit',
        description: 'Schedule counseling appointments, monitor guidance cases, and provide student support services.',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
    {
        title: 'Sports Unit',
        description: 'Handle equipment borrowing, manage athlete records, and oversee sports activities efficiently.',
        icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
];
</script>

<template>
    <Head title="OSA Information Management System" />

    <div class="min-h-screen bg-slate-50 overflow-x-hidden">
        <!-- Header / Navbar -->
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-3 min-w-0">
                    <!-- Brand -->
                    <div class="flex items-center gap-3 flex-shrink-0 min-w-0">
                        <div class="h-9 w-9 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center">
                            <div class="h-4 w-4 rounded bg-slate-900"></div>
                        </div>
                        <div class="leading-tight">
                            <div class="text-sm font-bold text-slate-900">OSA-IMS</div>
                            <div class="text-[11px] text-slate-500">Information Management System</div>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <nav v-if="canLogin" class="flex items-center gap-2 flex-shrink-0 flex-wrap justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors"
                        >
                            Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <template v-else>


                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors"
                            >
                                Sign In
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden">
            <!-- Subtle background pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50/30"></div>
            <div class="absolute top-20 right-0 w-96 h-96 bg-indigo-100/30 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-slate-100/50 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-28 lg:pb-32">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 mb-6">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-indigo-700">Office of Student Affairs</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 tracking-tight leading-tight">
                        Student Affairs
                        <span class="block text-indigo-600">Management System</span>
                    </h1>

                    <p class="mt-6 text-lg text-slate-600 max-w-2xl leading-relaxed">
                        A comprehensive platform for managing student services — from discipline and guidance to organizations and sports. Streamline workflows, track records, and empower both students and administrators.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4 min-w-0">
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-lg shadow-slate-900/10 transition-all hover:shadow-xl hover:shadow-slate-900/20"
                        >
                            Get Started
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                        <Link
                            v-if="canLogin && $page.props.auth.user"
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-lg shadow-slate-900/10 transition-all hover:shadow-xl hover:shadow-slate-900/20"
                        >
                            Go to Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Explore Features
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 lg:py-28 bg-white border-y border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900">Core Modules</h2>
                    <p class="mt-3 text-base text-slate-500 max-w-xl mx-auto">
                        Four integrated modules designed to cover every aspect of student affairs management.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="group relative bg-white rounded-2xl border border-slate-200 p-6 hover:border-slate-300 hover:shadow-lg hover:shadow-slate-100 transition-all duration-300"
                    >
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center group-hover:bg-indigo-50 group-hover:border-indigo-200 transition-colors">
                                <svg class="w-5 h-5 text-slate-600 group-hover:text-indigo-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900">
                                {{ feature.title }}
                            </h3>
                        </div>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Workflow / Stats Section -->
        <section class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">Designed for Efficiency</h2>
                        <p class="mt-4 text-base text-slate-500 leading-relaxed">
                            From student record tracking to guidance appointments, every workflow is streamlined for the Office of Student Affairs.
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-sm text-slate-700">Role-based access for administrators, staff, and students</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-sm text-slate-700">Real-time notifications for case updates and appointments</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-sm text-slate-700">PDF export and CSV import for bulk record management</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-sm text-slate-700">Visual progress tracking for cases and appointments</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Stats/highlights -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-slate-900 p-6 text-white">
                            <div class="text-3xl font-bold">4</div>
                            <div class="text-sm text-slate-400 mt-1">Core Modules</div>
                        </div>
                        <div class="rounded-2xl bg-indigo-600 p-6 text-white">
                            <div class="text-3xl font-bold">2</div>
                            <div class="text-sm text-indigo-200 mt-1">User Portals</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <div class="text-3xl font-bold text-slate-900">100%</div>
                            <div class="text-sm text-slate-500 mt-1">Web-Based</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <div class="text-3xl font-bold text-slate-900">24/7</div>
                            <div class="text-sm text-slate-500 mt-1">Accessible</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200/70 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center">
                            <div class="h-3 w-3 rounded bg-slate-900"></div>
                        </div>
                        <span class="text-sm font-semibold text-slate-900">OSA-IMS</span>
                    </div>
                    <p class="text-sm text-slate-500">
                        Office of Student Affairs &mdash; Information Management System
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
