<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const form = useForm({
    full_name: '',
    student_number: '',
    course: '',
    year_graduated: '',
    contact_number: '',
    email: '',
    purpose: '',
});

const submit = () => {
    form.post(route('good-moral.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Good Moral Certificate Request — OSA" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white dark:bg-slate-900/80 backdrop-blur-lg border-b border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-3">
                    <Link :href="route('welcome')" class="flex items-center gap-3">
                        <img src="/images/OSA-NEW-LOGO.png" alt="OSA Logo" class="h-11 w-11 rounded-xl object-contain border border-slate-200 dark:border-slate-700" />
                        <div class="leading-tight">
                            <div class="text-sm font-bold text-slate-900 dark:text-white">OSA-IMS</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">Information Management System</div>
                        </div>
                    </Link>
                    <div class="flex items-center gap-3">
                        <ThemeToggle />
                        <Link :href="route('login')"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition-colors">
                            Sign In
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-4 sm:px-6 py-14 lg:py-20">

            <!-- Back link -->
            <Link :href="route('welcome')" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 mb-8 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Back to Home
            </Link>

            <!-- Header -->
            <div class="mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 mb-4 transition-colors">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                    <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Office of Student Affairs and Services</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Certificate of Good Moral Character</h1>
                <p class="mt-3 text-slate-500 dark:text-slate-400 leading-relaxed">
                    For alumni and former students of CHCC. Fill out the form below, then pay at the Cashier and pick up your certificate at the Office of the Student Affairs and Services.
                </p>

                <!-- Steps -->
                <div class="mt-6 flex items-center gap-0">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">1</span>
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Submit Form</span>
                    </div>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700 mx-3"></div>
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-slate-300 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center flex-shrink-0">2</span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Pay at Cashier</span>
                    </div>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700 mx-3"></div>
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-slate-300 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center flex-shrink-0">3</span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Pick Up at OSA</span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-8 space-y-6">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input v-model="form.full_name" type="text" placeholder="e.g. Juan Dela Cruz"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.full_name" class="mt-1.5 text-xs text-red-500">{{ form.errors.full_name }}</p>
                    </div>

                    <!-- Student Number -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Student Number <span class="text-red-500">*</span></label>
                        <input v-model="form.student_number" type="text" placeholder="e.g. 2018-12345"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.student_number" class="mt-1.5 text-xs text-red-500">{{ form.errors.student_number }}</p>
                    </div>

                    <!-- Year Graduated -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Year Graduated <span class="text-red-500">*</span></label>
                        <input v-model="form.year_graduated" type="text" placeholder="e.g. 2022"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.year_graduated" class="mt-1.5 text-xs text-red-500">{{ form.errors.year_graduated }}</p>
                    </div>

                    <!-- Course -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Course / Program <span class="text-red-500">*</span></label>
                        <input v-model="form.course" type="text" placeholder="e.g. Bachelor of Science in Information Technology"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.course" class="mt-1.5 text-xs text-red-500">{{ form.errors.course }}</p>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                        <input v-model="form.contact_number" type="text" placeholder="e.g. 09xxxxxxxxx"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.contact_number" class="mt-1.5 text-xs text-red-500">{{ form.errors.contact_number }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" placeholder="e.g. juan@email.com"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors" />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Purpose -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Purpose of Request <span class="text-red-500">*</span></label>
                        <textarea v-model="form.purpose" rows="3" placeholder="e.g. For employment / for scholarship application..."
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors resize-none"></textarea>
                        <p v-if="form.errors.purpose" class="mt-1.5 text-xs text-red-500">{{ form.errors.purpose }}</p>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-indigo-600 text-white text-sm font-semibold hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                    </button>
                </div>
            </form>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200/70 dark:border-slate-800/70 py-8 mt-4 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500 dark:text-slate-400">
                Office of Student Affairs and Services — Concepcion Holy Cross College, Inc.
            </div>
        </footer>

        <LoadingOverlay :show="form.processing" message="Submitting... Please wait." />
    </div>
</template>
