<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    notifications: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    unreadCount: {
        type: Number,
        default: 0,
    },
});

const markAsRead = (notification) => {
    if (notification.is_read) return;
    router.post(route('student.notifications.mark-read'), {
        notification_id: notification.notification_id,
    }, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post(route('student.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

const getTypeLabel = (type) => {
    switch (type) {
        case 'discipline': return 'Discipline';
        case 'org_meeting': return 'Organization Meeting';
        case 'complaint': return 'Complaint';
        default: return type;
    }
};

const getTypeColor = (type) => {
    switch (type) {
        case 'discipline': return 'bg-red-100 text-red-800';
        case 'org_meeting': return 'bg-blue-100 text-blue-800';
        case 'complaint': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getNotificationLink = (notification) => {
    if (notification.type === 'discipline' && notification.related_case_id) {
        return route('student.discipline.show', notification.related_case_id);
    }
    return null;
};
</script>

<template>

    <Head title="Notifications" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Notifications
                </h2>
                <div class="flex items-center space-x-3">
                    <span v-if="unreadCount > 0" class="text-sm text-gray-500">
                        {{ unreadCount }} unread
                    </span>
                    <SecondaryButton v-if="unreadCount > 0" @click="markAllAsRead">
                        Mark All as Read
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div v-if="notifications.data && notifications.data.length > 0" class="divide-y divide-gray-200">
                    <div v-for="n in notifications.data" :key="n.notification_id" class="px-6 py-4 transition"
                        :class="{ 'bg-indigo-50/50': !n.is_read }">
                        <component :is="getNotificationLink(n) ? 'a' : 'div'" :href="getNotificationLink(n)"
                            @click="markAsRead(n)" class="block">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                            :class="getTypeColor(n.type)">
                                            {{ getTypeLabel(n.type) }}
                                        </span>
                                        <span v-if="!n.is_read" class="h-2 w-2 rounded-full bg-indigo-500" />
                                    </div>
                                    <p class="font-medium text-gray-900">{{ n.title }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ n.message }}</p>
                                </div>
                                <span class="text-xs text-gray-400 ml-4 whitespace-nowrap">{{ n.created_at }}</span>
                            </div>
                        </component>
                    </div>
                </div>

                <div v-else class="text-center py-12 px-6">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">No notifications yet.</p>
                </div>

                <!-- Pagination -->
                <Pagination :data="notifications" />
            </div>
        </div>
    </StudentLayout>
</template>
