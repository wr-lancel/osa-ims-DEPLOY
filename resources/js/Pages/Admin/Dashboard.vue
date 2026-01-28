<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const quickLinks = [
    {
        title: 'Student Records',
        description: 'Manage student information and records',
        route: 'admin.students.index',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
    },
    {
        title: 'Manage Staff',
        description: 'View and manage staff members',
        route: 'admin.staff.index',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
    },
    {
        title: 'Discipline Unit',
        description: 'Handle disciplinary cases and violations',
        route: 'admin.discipline.index',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
    },
    {
        title: 'Organization Unit',
        description: 'Manage student organizations and activities',
        route: 'admin.organizations.index',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    },
    {
        title: 'Sports Unit',
        description: 'Manage sports activities and equipment',
        route: 'admin.sports.index',
        icon: 'M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h-4m4 0v12a2 2 0 01-2 2H5a2 2 0 01-2-2V4h14z'
    }
];

const getEventBadgeClass = (daysUntil) => {
    if (daysUntil === 0) return 'bg-red-100 text-red-800';
    if (daysUntil <= 3) return 'bg-orange-100 text-orange-800';
    if (daysUntil <= 7) return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

const getEventLabel = (daysUntil) => {
    if (daysUntil === 0) return 'Today';
    if (daysUntil === 1) return 'Tomorrow';
    return `In ${daysUntil} days`;
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Admin Dashboard
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Enrolled Students</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_students || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Active Organizations</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_organizations || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Upcoming Events</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.upcoming_events || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Events This Month</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.events_this_month || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Access Cards -->
                <div class="lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Access</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.route"
                            :href="route(link.route)"
                            class="group bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-6 w-6 text-gray-600 group-hover:text-gray-900 transition-colors"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="link.icon"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-base font-medium text-gray-900 group-hover:text-gray-700">
                                        {{ link.title }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ link.description }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="lg:col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Upcoming Events</h3>
                        <Link
                            :href="route('admin.organizations.events.index')"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            View all
                        </Link>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                        <div v-if="upcomingEvents && upcomingEvents.length > 0" class="divide-y divide-gray-200">
                            <div
                                v-for="event in upcomingEvents"
                                :key="event.event_id"
                                class="p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ event.event_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ event.organization_name }}
                                        </p>
                                        <div class="flex items-center mt-2 text-xs text-gray-500">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ event.event_date_display }}
                                            <span v-if="event.start_time" class="ml-2">
                                                {{ event.start_time }}
                                            </span>
                                        </div>
                                        <div v-if="event.venue" class="flex items-center mt-1 text-xs text-gray-500">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ event.venue }}
                                        </div>
                                    </div>
                                    <span
                                        class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap"
                                        :class="getEventBadgeClass(event.days_until)"
                                    >
                                        {{ getEventLabel(event.days_until) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No upcoming events</p>
                            <Link
                                :href="route('admin.organizations.events.index')"
                                class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                Create an event
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
