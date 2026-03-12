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
    appointments: {
        type: Array,
        default: () => [],
    },
    activeBorrowings: {
        type: Array,
        default: () => [],
    },
    complaints: {
        type: Array,
        default: () => [],
    },
    notifications: {
        type: Array,
        default: () => [],
    },
    officerActivities: {
        type: Array,
        default: () => [],
    },
});



const quickActions = [
    { title: 'Book Guidance', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', route: 'student.guidance.index', gradient: 'from-blue-500 to-indigo-600', shadow: 'shadow-blue-500/30' },
    { title: 'Borrow Sports Eq.', icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', route: 'student.sports.index', gradient: 'from-emerald-400 to-teal-500', shadow: 'shadow-emerald-500/30' },
    { title: 'File Complaint', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', route: 'student.discipline.complaints.create', gradient: 'from-rose-400 to-red-500', shadow: 'shadow-rose-500/30' },
    { title: 'Organizations', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', route: 'student.organizations.index', gradient: 'from-amber-400 to-orange-500', shadow: 'shadow-amber-500/30' },
];

</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
                    Welcome back 👋
                </h2>
                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400 bg-white/50 dark:bg-gray-800/50 backdrop-blur px-4 py-2 rounded-xl border border-gray-100 dark:border-gray-700/50">
                    {{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) }}
                </div>
            </div>
        </template>

        <div class="space-y-8 pb-10 mt-6">
            <!-- QUICK ACTIONS -->
            <section>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        v-for="action in quickActions"
                        :key="action.title"
                        :href="route(action.route)"
                        class="relative overflow-hidden rounded-2xl p-6 group transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg"
                        :class="[`bg-gradient-to-br ${action.gradient}`, action.shadow]"
                    >
                        <!-- Glass shine effect -->
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl group-hover:blur-xl transition-all duration-500"></div>
                        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-20 h-20 bg-black opacity-10 rounded-full blur-xl group-hover:blur-2xl transition-all duration-500"></div>

                        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center">
                            <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl mb-3 text-white">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
                                </svg>
                            </div>
                            <h3 class="text-white font-semibold tracking-wide text-sm">{{ action.title }}</h3>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- BENTO BOX MAIN GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT COLUMN (Main Content) -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- Officer Section -->
                    <div v-if="officerOrganizations && officerOrganizations.length > 0" class="space-y-6">
                        <!-- Officer Banner -->
                        <div class="bg-gradient-to-r from-indigo-900 to-slate-800 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
                            <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                                <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <h3 class="text-xl font-bold mb-1 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Officer Dashboard
                                </h3>
                                <p class="text-indigo-200 text-sm mb-4">You are an active officer in {{ officerOrganizations.length }} organization(s).</p>
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-for="org in officerOrganizations"
                                        :key="org.org_id"
                                        :href="route('student.organizations.show', org.org_id)"
                                        class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition-all duration-300"
                                    >
                                        {{ org.org_name }} • <span class="text-indigo-300 ml-1">{{ org.position }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Organization Activities Box -->
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/10 dark:to-purple-900/10 backdrop-blur-xl border border-indigo-100/50 dark:border-indigo-700/30 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center justify-between mb-5">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Your Organization's Activities</h3>
                                    <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">Events & Meetings for organizations you manage</p>
                                </div>
                                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                            </div>
                            
                            <div v-if="officerActivities.length > 0" class="space-y-4">
                                <div v-for="activity in officerActivities" :key="activity.id" class="flex items-start p-4 bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100/50 dark:border-gray-700/50 hover:shadow-md transition-all cursor-pointer">
                                    <div class="flex-shrink-0 mr-4">
                                        <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center font-bold" :class="activity.color">
                                            <span class="text-[10px] uppercase tracking-wider opacity-80">{{ activity.type === 'Event' ? 'O-Evt' : 'O-Mtg' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ activity.title }}</h4>
                                        <div class="flex items-center mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ activity.date }}
                                        </div>
                                        <div v-if="activity.venue" class="flex items-center mt-1 text-xs text-gray-400 dark:text-gray-500">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ activity.venue }}
                                        </div>
                                    </div>
                                    <button class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming organization activities.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- My Schedule Box -->
                        <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">My Schedule</h3>
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            </div>
                            
                            <div v-if="appointments.length > 0" class="space-y-4">
                                <div v-for="app in appointments" :key="app.id" class="flex items-start p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700/50 border border-transparent hover:border-gray-100 dark:hover:border-gray-600 transition-colors cursor-pointer">
                                    <div class="h-2 w-2 rounded-full mt-2 mr-3" :class="app.status === 'Approved' ? 'bg-emerald-500' : (app.status === 'Pending' ? 'bg-amber-500' : 'bg-blue-500')"></div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ app.type }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ app.date }}</p>
                                    </div>
                                    <span class="text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded-full" :class="app.statusColor">
                                        {{ app.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming appointments.</p>
                            </div>
                        </div>

                        <!-- Active Borrowings Box -->
                        <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Equipment</h3>
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                            </div>

                            <div v-if="activeBorrowings.length > 0" class="space-y-3">
                                <div v-for="item in activeBorrowings" :key="item.id" class="relative overflow-hidden bg-gradient-to-br from-orange-50 to-rose-50 dark:from-orange-900/20 dark:to-rose-900/20 border border-orange-100 dark:border-orange-900/30 p-4 rounded-2xl">
                                    <div v-if="item.isDueSoon" class="absolute top-0 right-0 bg-rose-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg">
                                        DUE SOON
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ item.item }}</h4>
                                    <div class="flex items-center mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Due: {{ item.dueDate }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No active sports borrowings.</p>
                            </div>
                        </div>
                        
                    </div>

                    <!-- My Complaints / Cases -->
                    <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Discipline & Complaints</h3>
                            <Link :href="route('student.discipline.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hidden sm:block">View All</Link>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-500 bg-gray-50/50 dark:bg-gray-700/30 uppercase rounded-t-xl">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold rounded-tl-xl whitespace-nowrap">Subject</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Date</th>
                                        <th class="px-4 py-3 font-semibold rounded-tr-xl whitespace-nowrap">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="comp in complaints" :key="comp.id" class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200">{{ comp.subject }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ comp.date }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-1 rounded-lg text-xs font-semibold">
                                                {{ comp.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="complaints.length === 0">
                                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No recent complaints found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN (Sidebar) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Notifications Box -->
                    <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Recent Updates</h3>
                            <span v-if="notifications.length > 0" class="bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 text-xs font-bold px-3 py-1 rounded-full shadow-sm">{{ notifications.length }} New</span>
                        </div>
                        
                        <div v-if="notifications.length > 0" class="space-y-5">
                            <div v-for="notif in notifications" :key="notif.id" class="flex items-start group cursor-pointer group-hover:bg-gray-50/50 dark:group-hover:bg-gray-700/20 p-2 -mx-2 rounded-xl transition-colors">
                                <div class="flex-shrink-0 p-2.5 rounded-2xl" :class="notif.color">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="notif.icon" />
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ notif.title }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">{{ notif.message }}</p>
                                    <span class="text-[10px] font-medium text-gray-400 mt-1.5 block">{{ notif.time }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                             <p class="text-sm text-gray-500 dark:text-gray-400">No new notifications.</p>
                        </div>
                    </div>

                    <!-- Upcoming Events Box -->
                    <div class="bg-white dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700/50 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Campus Events</h3>
                            <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        
                        <div v-if="upcomingEvents && upcomingEvents.length > 0" class="space-y-4">
                            <!-- Taking only first 3 events for brevity in the sidebar -->
                            <div v-for="event in upcomingEvents.slice(0, 3)" :key="event.event_id" class="group border-l-[3px] border-purple-500 pl-4 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-r-xl transition-colors cursor-pointer -ml-1 pr-2">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-1">{{ event.event_name }}</h4>
                                <div class="flex items-center mt-1 text-xs text-purple-600/80 dark:text-purple-400/80 font-medium tracking-wide">
                                    {{ event.event_date_display }} <span v-if="event.start_time" class="ml-1.5 opacity-70">• {{ event.start_time }}</span>
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 font-medium">{{ event.organization_name }}</p>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="font-medium">No upcoming events found</p>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </StudentLayout>
</template>
