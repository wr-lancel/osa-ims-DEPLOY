<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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
    router.post(route('student.discipline.notifications.mark-read'), {
        notification_id: notification.notification_id,
    }, { preserveScroll: true });
};

const goToCase = (notification) => {
    if (notification.related_case_id) {
        return route('student.discipline.show', notification.related_case_id);
    }
    return null;
};
</script>

<template>
    <Head title="Discipline Notifications" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Discipline Notifications
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                >
                    ← My Violations
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <p v-if="unreadCount > 0" class="text-sm text-gray-600 mb-4">
                    {{ unreadCount }} unread notification(s).
                </p>

                <div v-if="notifications.data && notifications.data.length > 0" class="divide-y divide-gray-200">
                    <div
                        v-for="n in notifications.data"
                        :key="n.notification_id"
                        class="py-4 first:pt-0"
                        :class="{ 'bg-indigo-50/50': !n.is_read }"
                    >
                        <Link
                            v-if="n.related_case_id"
                            :href="route('student.discipline.show', n.related_case_id)"
                            class="block"
                            @click="markAsRead(n)"
                        >
                            <p class="font-medium text-gray-900">{{ n.title }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ n.message }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ n.created_at }}</p>
                        </Link>
                        <div v-else>
                            <p class="font-medium text-gray-900">{{ n.title }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ n.message }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ n.created_at }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500">No discipline notifications.</p>
                </div>

                <div v-if="notifications.links && notifications.links.length > 1" class="mt-4 flex justify-center gap-2">
                    <template v-for="(link, i) in notifications.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 rounded border text-sm"
                            :class="link.active ? 'bg-indigo-100 border-indigo-300 text-indigo-800' : 'border-gray-300 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1 text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
