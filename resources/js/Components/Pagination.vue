<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    /** The paginated data object from Laravel */
    data: {
        type: Object,
        required: true,
    },
    /** Preserve filters on page change — route name to re-fetch with */
    routeName: {
        type: String,
        default: null,
    },
    /** Extra query params to preserve (filters, etc.) */
    filters: {
        type: Object,
        default: () => ({}),
    },
    /** The page parameter name (useful for multi-paginator pages) */
    pageName: {
        type: String,
        default: 'page',
    },
});

const perPageOptions = [10, 20, 50, 100];

// Determine the meta info — Laravel paginate() returns it at top-level or inside .meta
const getMeta = () => {
    if (props.data?.meta) return props.data.meta;
    return {
        from: props.data?.from,
        to: props.data?.to,
        total: props.data?.total,
        per_page: props.data?.per_page,
        current_page: props.data?.current_page,
        last_page: props.data?.last_page,
    };
};

const currentPerPage = ref(getMeta().per_page || 20);
const currentPage = ref(getMeta().current_page || 1);

watch(() => props.data, () => {
    const meta = getMeta();
    if (meta.per_page) currentPerPage.value = meta.per_page;
    if (meta.current_page) currentPage.value = meta.current_page;
});

const links = () => props.data?.links || [];

const totalPages = computed(() => getMeta().last_page || 1);
const totalItems = computed(() => getMeta().total || 0);
const showingCount = computed(() => {
    const meta = getMeta();
    if (!meta.from || !meta.to) return 0;
    return meta.to - meta.from + 1;
});

// Build page options for dropdown
const pageOptions = computed(() => {
    const pages = [];
    for (let i = 1; i <= totalPages.value; i++) {
        pages.push(i);
    }
    return pages;
});

// Navigate to a specific page
const goToPage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    const allLinks = links();
    // Find the link for this page
    const targetLink = allLinks.find(l => l.active === false && l.label == String(page)) ||
                       allLinks[page]; // fallback: links[0] = prev, links[1] = page 1, etc.

    // Build URL from current page URL pattern
    if (allLinks.length > 0) {
        // Use the first available link URL as a base and replace page number
        let baseUrl = null;
        for (const l of allLinks) {
            if (l.url) {
                baseUrl = l.url;
                break;
            }
        }
        if (baseUrl) {
            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('page', page);
            router.get(url.pathname + url.search, {}, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    }
};

const onPageChange = () => {
    goToPage(currentPage.value);
};

const onPerPageChange = () => {
    const params = { ...props.filters, perPage: currentPerPage.value };
    params[props.pageName] = 1;

    if (props.routeName) {
        router.get(route(props.routeName), params, {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

// Navigation helpers
const canGoPrev = computed(() => getMeta().current_page > 1);
const canGoNext = computed(() => getMeta().current_page < totalPages.value);

const goFirst = () => goToPage(1);
const goPrev = () => goToPage(getMeta().current_page - 1);
const goNext = () => goToPage(getMeta().current_page + 1);
const goLast = () => goToPage(totalPages.value);

const shouldShow = () => {
    const meta = getMeta();
    return meta.total && meta.total > 0;
};
</script>

<template>
    <div v-if="shouldShow()" class="bg-white px-4 py-2.5 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <!-- Left: Showing count -->
            <p class="text-sm text-gray-600">
                Showing
                <span class="font-semibold text-gray-900">{{ showingCount }}</span>
                of
                <span class="font-semibold text-gray-900">{{ totalItems }}</span>
            </p>

            <!-- Right: Navigation controls -->
            <div class="flex items-center gap-1.5">
                <!-- First page -->
                <button
                    @click="goFirst"
                    :disabled="!canGoPrev"
                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 bg-white text-gray-500 transition-colors"
                    :class="canGoPrev ? 'hover:bg-gray-50 hover:text-gray-700 cursor-pointer' : 'opacity-40 cursor-not-allowed'"
                    title="First page"
                    aria-label="Go to first page"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Previous page -->
                <button
                    @click="goPrev"
                    :disabled="!canGoPrev"
                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 bg-white text-gray-500 transition-colors"
                    :class="canGoPrev ? 'hover:bg-gray-50 hover:text-gray-700 cursor-pointer' : 'opacity-40 cursor-not-allowed'"
                    title="Previous page"
                    aria-label="Go to previous page"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Page number dropdown -->
                <select
                    v-model.number="currentPage"
                    @change="onPageChange"
                    class="h-8 rounded border-gray-300 bg-white text-sm text-gray-700 pl-2 pr-7 py-0 focus:border-indigo-500 focus:ring-indigo-500 cursor-pointer"
                >
                    <option v-for="p in pageOptions" :key="p" :value="p">
                        {{ p }}
                    </option>
                </select>

                <!-- Next page -->
                <button
                    @click="goNext"
                    :disabled="!canGoNext"
                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 bg-white text-gray-500 transition-colors"
                    :class="canGoNext ? 'hover:bg-gray-50 hover:text-gray-700 cursor-pointer' : 'opacity-40 cursor-not-allowed'"
                    title="Next page"
                    aria-label="Go to next page"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Last page -->
                <button
                    @click="goLast"
                    :disabled="!canGoNext"
                    class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 bg-white text-gray-500 transition-colors"
                    :class="canGoNext ? 'hover:bg-gray-50 hover:text-gray-700 cursor-pointer' : 'opacity-40 cursor-not-allowed'"
                    title="Last page"
                    aria-label="Go to last page"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Per-page dropdown -->
                <select
                    v-model.number="currentPerPage"
                    @change="onPerPageChange"
                    class="h-8 rounded border-gray-300 bg-white text-sm text-gray-700 pl-2 pr-7 py-0 focus:border-indigo-500 focus:ring-indigo-500 cursor-pointer ml-1"
                >
                    <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                        {{ opt }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>
