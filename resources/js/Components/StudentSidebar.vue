<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const disciplineUnread = computed(() => page.props.discipline_notifications_unread ?? 0);
const canManagePublications = computed(() => page.props.can_manage_publications ?? false);

const navItems = computed(() => {
    const items = [
        { href: route('student.dashboard'), label: 'Home', routeName: 'student.dashboard' },
        { href: route('student.discipline.index'), label: 'Discipline Unit', routeName: 'student.discipline.index' },
        { href: route('student.sports.index'), label: 'Sports Unit', routeName: 'student.sports.index' },
        { href: route('student.organizations.index'), label: 'Organization Unit', routeName: 'student.organizations.index' },
        { href: route('student.guidance.index'), label: 'Guidance Unit', routeName: 'student.guidance.index' },
        { href: route('student.profile'), label: 'My Profile', routeName: 'student.profile' },
    ];
    if (canManagePublications.value) {
        items.splice(5, 0, { href: route('student.publications.index'), label: 'Publications', routeName: 'student.publications.index' });
    }
    return items;
});

const isActive = (routeName) => route().current(routeName);

// Close drawer on route change
watch(() => page.url, () => {
    emit('close');
});
</script>

<template>
    <!-- Desktop Sidebar (unchanged) -->
    <aside class="w-72 hidden md:block">
        <div class="w-full h-screen sticky top-0 bg-white dark:bg-slate-900 border-r border-slate-200/70 dark:border-slate-800/70 transition-colors duration-200">
            <!-- Brand -->
            <div class="px-5 pt-6 pb-4">
                <div class="flex items-center gap-3">
                    <img src="/images/OSA-NEW-LOGO.png" alt="OSA Logo" class="h-12 w-12 rounded-xl object-contain" />
                    <div class="leading-tight">
                        <div class="text-sm font-semibold text-slate-900 dark:text-white transition-colors">Student Portal</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">OSAS-IMS</div>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="px-3 pb-6">
                <div class="text-[11px] font-medium tracking-wide text-slate-400 px-3 mb-2">
                    NAVIGATION
                </div>
                <div class="space-y-1">
                    <Link v-for="item in navItems" :key="item.routeName" :href="item.href"
                        class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition"
                        :class="isActive(item.routeName)
                            ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-sm'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'">
                        <span class="h-8 w-8 rounded-lg flex items-center justify-center border transition"
                            :class="isActive(item.routeName)
                                ? 'border-white/10 bg-white/10 dark:border-indigo-500/30'
                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 group-hover:border-slate-300 dark:group-hover:border-slate-600'" aria-hidden="true">
                            <span class="h-2 w-2 rounded-full transition"
                                :class="isActive(item.routeName) ? 'bg-white' : 'bg-slate-400 dark:bg-slate-500 group-hover:bg-slate-700 dark:group-hover:bg-slate-300'" />
                        </span>
                        <span class="font-medium">{{ item.label }}</span>
                        <span v-if="item.routeName === 'student.discipline.index' && disciplineUnread > 0"
                            class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800">
                            {{ disciplineUnread }}
                        </span>
                        <span v-if="isActive(item.routeName)"
                            class="absolute right-3 h-1.5 w-1.5 rounded-full bg-white/80" aria-hidden="true" />
                    </Link>
                </div>


            </nav>
        </div>
    </aside>

    <!-- Mobile Drawer Overlay -->
    <Teleport to="body">
        <Transition name="drawer">
            <div v-if="mobileOpen" class="fixed inset-0 z-50 md:hidden">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')"></div>

                <!-- Drawer Panel -->
                <div class="fixed inset-y-0 left-0 w-72 bg-white dark:bg-slate-900 shadow-xl flex flex-col transition-colors duration-200">
                    <!-- Header -->
                    <div class="px-5 pt-6 pb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="/images/OSA-NEW-LOGO.png" alt="OSA Logo" class="h-12 w-12 rounded-xl object-contain" />
                            <div class="leading-tight">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white transition-colors">Student Portal</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 transition-colors">OSAS-IMS</div>
                            </div>
                        </div>
                        <!-- Close button -->
                        <button @click="emit('close')"
                            class="p-2 rounded-lg text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            aria-label="Close navigation">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Nav Items -->
                    <nav class="flex-1 px-3 pb-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        <div class="text-[11px] font-medium tracking-wide text-slate-400 px-3 mb-2">
                            NAVIGATION
                        </div>
                        <div class="space-y-1">
                            <Link v-for="item in navItems" :key="item.routeName" :href="item.href"
                                class="group relative flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition"
                                :class="isActive(item.routeName)
                                    ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'">
                                <span
                                    class="h-8 w-8 rounded-lg flex items-center justify-center border transition"
                                    :class="isActive(item.routeName)
                                        ? 'border-white/10 bg-white/10 dark:border-indigo-500/30'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 group-hover:border-slate-300 dark:group-hover:border-slate-600'"
                                    aria-hidden="true">
                                    <span class="h-2 w-2 rounded-full transition"
                                        :class="isActive(item.routeName) ? 'bg-white' : 'bg-slate-400 dark:bg-slate-500 group-hover:bg-slate-700 dark:group-hover:bg-slate-300'" />
                                </span>
                                <span class="font-medium">{{ item.label }}</span>
                                <span
                                    v-if="item.routeName === 'student.discipline.index' && disciplineUnread > 0"
                                    class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                    {{ disciplineUnread }}
                                </span>
                            </Link>
                        </div>
                    </nav>


                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Drawer transition */
.drawer-enter-active,
.drawer-leave-active {
    transition: opacity 0.25s ease;
}

.drawer-enter-active > div:last-child,
.drawer-leave-active > div:last-child {
    transition: transform 0.25s ease;
}

.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
}

.drawer-enter-from > div:last-child,
.drawer-leave-to > div:last-child {
    transform: translateX(-100%);
}
</style>