<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    articles: Object,
    newspapers: Object,
    galleries: Object,
    stats: Object,
    filters: Object,
    publication_org: Object,
    organizations: Array,
});

const activeTab = ref(props.filters?.tab || 'articles');
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 500);
});

function applyFilters() {
    router.get(route('admin.publications.index'), {
        search: search.value,
        status: statusFilter.value,
        tab: activeTab.value,
    }, { preserveState: true, replace: true });
}

function setTab(tab) {
    activeTab.value = tab;
    router.get(route('admin.publications.index'), {
        search: search.value,
        status: statusFilter.value,
        tab: tab,
    }, { preserveState: true, replace: true });
}

function togglePublicationOrg(orgId) {
    router.post(route('admin.publications.settings.toggle-org'), { org_id: orgId }, {
        preserveState: true,
        onSuccess: () => {},
    });
}

const statusColors = {
    draft: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    pending_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
};
</script>

<template>
    <Head title="Publications" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Publications</h2>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_articles }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Total Articles</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.published_articles }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Published Articles</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.pending_articles }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pending Review</div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_galleries }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Galleries</div>
                </div>
            </div>

            <!-- Publication Org Setting -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Publication Organization</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Current: <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ publication_org?.org_name || 'None set' }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                            @change="e => togglePublicationOrg(e.target.value)"
                        >
                            <option value="">Select organization...</option>
                            <option v-for="org in organizations" :key="org.org_id" :value="org.org_id">{{ org.org_name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <!-- Tabs + Search -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <!-- Tabs -->
                        <div class="flex gap-1 bg-slate-100 dark:bg-slate-900/50 rounded-lg p-1">
                            <button v-for="tab in ['articles', 'newspapers', 'galleries']" :key="tab"
                                @click="setTab(tab)"
                                class="px-4 py-1.5 rounded-md text-sm font-medium capitalize transition"
                                :class="activeTab === tab
                                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
                                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                                {{ tab }}
                            </button>
                        </div>

                        <div class="flex items-center gap-2 ml-auto">
                            <!-- Search -->
                            <input v-model="search" type="search" placeholder="Search..."
                                class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 w-48 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400" />

                            <!-- Status filter -->
                            <select v-model="statusFilter" @change="applyFilters"
                                class="text-sm border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="pending_review">Pending Review</option>
                                <option value="published">Published</option>
                                <option value="rejected">Rejected</option>
                            </select>

                            <!-- Create button -->
                            <Link v-if="activeTab === 'articles'" :href="route('admin.publications.articles.create')"
                                class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition whitespace-nowrap">
                                + New Article
                            </Link>
                            <Link v-else-if="activeTab === 'newspapers'" :href="route('admin.publications.newspapers.create')"
                                class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition whitespace-nowrap">
                                + New Newspaper
                            </Link>
                            <Link v-else :href="route('admin.publications.galleries.create')"
                                class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 transition whitespace-nowrap">
                                + New Gallery
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Articles Table -->
                <div v-if="activeTab === 'articles'" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs text-slate-500 dark:text-slate-400 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Title</th>
                                <th class="px-4 py-3 text-left font-medium">Author</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Date</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="article in articles.data" :key="article.article_id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white line-clamp-1 max-w-xs">{{ article.title }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ article.author?.display_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColors[article.status]">
                                        {{ article.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ article.created_at }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.publications.articles.show', article.slug)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!articles.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">No articles found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Newspapers Table -->
                <div v-if="activeTab === 'newspapers'" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs text-slate-500 dark:text-slate-400 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Title</th>
                                <th class="px-4 py-3 text-left font-medium">Issue</th>
                                <th class="px-4 py-3 text-left font-medium">Author</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="newspaper in newspapers.data" :key="newspaper.newspaper_id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white line-clamp-1 max-w-xs">{{ newspaper.title }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ newspaper.issue_number || '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ newspaper.author?.display_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColors[newspaper.status]">
                                        {{ newspaper.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.publications.newspapers.show', newspaper.slug)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!newspapers.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">No newspapers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Galleries Table -->
                <div v-if="activeTab === 'galleries'" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs text-slate-500 dark:text-slate-400 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Title</th>
                                <th class="px-4 py-3 text-left font-medium">Photos</th>
                                <th class="px-4 py-3 text-left font-medium">Author</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <tr v-for="gallery in galleries.data" :key="gallery.gallery_id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white line-clamp-1 max-w-xs">{{ gallery.title }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ gallery.photos_count }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ gallery.author?.display_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColors[gallery.status]">
                                        {{ gallery.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.publications.galleries.show', gallery.slug)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!galleries.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">No galleries found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
