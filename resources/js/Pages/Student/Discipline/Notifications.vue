<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const isProcessing = ref(false);

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
    isProcessing.value = true;
    router.post(route('student.discipline.notifications.mark-read'), {
        notification_id: notification.notification_id,
    }, {
        preserveScroll: true,
        onFinish: () => { isProcessing.value = false; },
    });
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
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Discipline Notifications
                </h2>
                <Link
                    :href="route('student.discipline.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm"
                >
                    ← My Violations
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <p v-if="unreadCount > 0" class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    {{ unreadCount }} unread notification(s).
                </p>

                <div v-if="notifications.data && notifications.data.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
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
                            <p class="font-medium text-gray-900 dark:text-white">{{ n.title }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ n.message }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-2">{{ n.created_at }}</p>
                        </Link>
                        <div v-else>
                            <p class="font-medium text-gray-900 dark:text-white">{{ n.title }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ n.message }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-2">{{ n.created_at }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">No discipline notifications.</p>
                </div>

                <div v-if="notifications.links && notifications.links.length > 1" class="mt-4 flex justify-center gap-2">
                    <template v-for="(link, i) in notifications.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 rounded border text-sm"
                            :class="link.active ? 'bg-indigo-100 border-indigo-300 text-indigo-800' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:bg-gray-900'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1 text-gray-400 dark:text-gray-500 dark:text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <LoadingOverlay :show="isProcessing" message="Processing... Please wait." />
    </StudentLayout>
</template>
