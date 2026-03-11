<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
    officerOrganizations: {
        type: Array,
        default: () => [],
    },
    organizationTypes: {
        type: Array,
        default: () => ['Academic', 'Cultural', 'Governance', 'Special Interest'],
    },
});

const search = ref('');
const filterType = ref('');

const typeOptions = computed(() => [
    { value: '', label: 'All Types' },
    ...props.organizationTypes.map(t => ({ value: t, label: t })),
]);

const filteredOrganizations = computed(() => {
    let result = props.organizations;

    if (search.value) {
        const searchLower = search.value.toLowerCase();
        result = result.filter(org =>
            org.org_name.toLowerCase().includes(searchLower) ||
            org.org_code.toLowerCase().includes(searchLower)
        );
    }

    if (filterType.value) {
        result = result.filter(org => org.type === filterType.value);
    }

    return result;
});
</script>

<template>
    <Head title="Organizations" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white transition-colors">
                    Student Organizations
                </h2>
                <div class="flex items-center gap-3 text-sm">
                    <Link
                        :href="route('student.organizations.candidacies.index')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium transition-colors"
                    >
                        My Candidacies
                    </Link>
                    <Link
                        :href="route('student.organizations.candidacy.create')"
                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium transition-colors"
                    >
                        Run for Position
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- My Organizations (Officer) -->
            <div v-if="officerOrganizations && officerOrganizations.length > 0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">My Organizations (Officer)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="org in officerOrganizations"
                        :key="org.org_id"
                        :href="route('student.organizations.show', org.org_id)"
                        class="group bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-5 hover:shadow-md dark:hover:shadow-indigo-900/20 transition-all"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-indigo-900 dark:text-indigo-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-200 transition-colors">
                                    {{ org.org_name }}
                                </h4>
                                <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-1 transition-colors">{{ org.org_code }}</p>
                            </div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-200 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 transition-colors">
                                {{ org.officer_role }}
                            </span>
                        </div>
                        <p v-if="org.type" class="text-sm text-indigo-700 dark:text-indigo-300 mt-3 transition-colors">{{ org.type }}</p>
                        <div class="flex items-center mt-3 text-xs text-indigo-600 dark:text-indigo-400 transition-colors">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{ org.members_count }} members
                        </div>
                    </Link>
                </div>
            </div>

            <!-- All Organizations -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 transition-colors">All Organizations</h3>
                
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 mb-4 transition-colors">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors">Search</label>
                            <input
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Search organization name or code..."
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 transition-colors dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors">Type</label>
                            <select
                                id="type"
                                v-model="filterType"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 transition-colors dark:bg-gray-700 dark:text-gray-100"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Organizations Grid -->
                <div v-if="filteredOrganizations.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="org in filteredOrganizations"
                        :key="org.org_id"
                        :href="route('student.organizations.show', org.org_id)"
                        class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-md dark:hover:shadow-gray-900/50 transition-all"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ org.org_name }}
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ org.org_code }}</p>
                            </div>
                            <span
                                v-if="org.is_officer"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 transition-colors"
                            >
                                {{ org.officer_role }}
                            </span>
                        </div>
                        
                        <p v-if="org.description" class="text-sm text-gray-600 dark:text-gray-400 mt-3 line-clamp-2 transition-colors">
                            {{ org.description }}
                        </p>
                        
                        <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 transition-colors">
                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 transition-colors">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ org.members_count }} members
                            </div>
                            <span
                                v-if="org.type"
                                class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                {{ org.type }}
                            </span>
                        </div>
                    </Link>
                </div>
                
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-8 text-center transition-colors">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 dark:text-gray-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 transition-colors">No organizations found</p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
