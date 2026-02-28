<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { getEventBadgeClass, getEventLabel } from '@/utils/eventHelpers';

const props = defineProps({
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
    officerOrganizations: {
        type: Array,
        default: () => [],
    },
});

const quickLinks = [
    {
        title: 'My Profile',
        description: 'View and update your personal information',
        route: 'student.profile',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
    {
        title: 'Organizations',
        description: 'View organizations and your memberships',
        route: 'student.organizations.index',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        title: 'Sports',
        description: 'Sports activities and equipment borrowing',
        route: 'student.sports.index',
        icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Guidance',
        description: 'Counseling services and support',
        route: 'student.guidance.index',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
];
</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Student Dashboard
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Officer Organizations Banner (if applicable) -->
            <div v-if="officerOrganizations && officerOrganizations.length > 0" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-indigo-800">
                            You are an officer in {{ officerOrganizations.length }} organization{{ officerOrganizations.length > 1 ? 's' : '' }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Link
                                v-for="org in officerOrganizations"
                                :key="org.org_id"
                                :href="route('student.organizations.show', org.org_id)"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors"
                            >
                                {{ org.org_name }} ({{ org.position }})
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Access -->
                <div class="lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Access</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.route"
                            :href="route(link.route)"
                            class="group bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-6 w-6 text-gray-600 group-hover:text-indigo-600 transition-colors"
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
                                    <h3 class="text-base font-medium text-gray-900 group-hover:text-indigo-600">
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Events</h3>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
