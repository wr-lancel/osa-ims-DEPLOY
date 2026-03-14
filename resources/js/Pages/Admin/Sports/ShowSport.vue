<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    sport: {
        type: Object,
        required: true,
    },
    athletes: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    availableStudents: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const isProcessing = ref(false);
const showAddModal = ref(false);
const searchQuery = ref(props.filters.search || '');
const showConfirmRemove = ref(false);
const athleteToRemove = ref(null);

const addForm = useForm({
    student_number: '',
});

const studentOptions = computed(() => {
    return props.availableStudents.map(s => ({
        value: s.student_number,
        label: `${s.full_name} (${s.student_number})`,
    }));
});

// Live search with debounce for athletes table
let searchDebounce = null;
watch(searchQuery, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        const params = {};
        if (searchQuery.value) params.search = searchQuery.value;
        router.get(route('admin.sports.sports.show', props.sport.sport_id), params, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 350);
});

const openAddModal = () => {
    addForm.reset();
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
};

const submitAdd = () => {
    addForm.post(route('admin.sports.sports.athletes.store', props.sport.sport_id), {
        preserveScroll: true,
        onSuccess: () => closeAddModal(),
    });
};

const confirmRemove = (athlete) => {
    athleteToRemove.value = athlete;
    showConfirmRemove.value = true;
};

const cancelRemove = () => {
    showConfirmRemove.value = false;
    athleteToRemove.value = null;
};

const removeAthlete = () => {
    if (!athleteToRemove.value) return;
    isProcessing.value = true;
    router.delete(
        route('admin.sports.sports.athletes.destroy', [props.sport.sport_id, athleteToRemove.value.student_number]),
        {
            preserveScroll: true,
            onSuccess: () => cancelRemove(),
            onFinish: () => { isProcessing.value = false; },
        }
    );
};
</script>

<template>

    <Head :title="sport.name" />

    <AdminLayout>
        <LoadingOverlay :show="isProcessing" message="Processing... Please wait." />
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ sport.name }}
                    </h2>
                    <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full"
                        :class="sport.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 dark:text-gray-400'">
                        {{ sport.status }}
                    </span>
                </div>
                <div class="flex space-x-3">
                    <SecondaryButton @click="router.visit(route('admin.sports.athletes'))">
                        Back to Athletes
                    </SecondaryButton>
                    <PrimaryButton @click="openAddModal">
                        Add Player
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Sport Description -->
            <div v-if="sport.description" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ sport.description }}</p>
            </div>

            <!-- Search Filter -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                <div class="max-w-md">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Search Athletes
                    </label>
                    <input id="search" v-model="searchQuery" type="text" placeholder="Student ID or name..."
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-100" />
                </div>
            </div>

            <!-- Athletes Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Student ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Section
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Date Added
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="athletes.data && athletes.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-3xl mb-2">👤</div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">No athletes in this sport yet.</p>
                                    <button @click="openAddModal"
                                        class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                                        Add your first player
                                    </button>
                                </td>
                            </tr>
                            <tr v-for="athlete in athletes.data" :key="athlete.id" class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ athlete.student_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ athlete.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ athlete.course || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ athlete.section || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ athlete.added_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <button @click="confirmRemove(athlete)"
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 font-medium">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="athletes.data && athletes.data.length > 0">
                    <Pagination :paginator="athletes" />
                </div>
            </div>
        </div>

        <!-- Add Player Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Player to {{ sport.name }}</h3>
                    <form @submit.prevent="submitAdd">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Select Student</label>
                            <SearchableSelect v-model="addForm.student_number" :options="studentOptions"
                                placeholder="Search by name or student number..."
                                :error="addForm.errors.student_number" />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeAddModal">Cancel</SecondaryButton>
                            <PrimaryButton type="submit" :disabled="addForm.processing || !addForm.student_number">
                                {{ addForm.processing ? 'Adding...' : 'Add Player' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirm Remove Modal -->
        <div v-if="showConfirmRemove" class="fixed inset-0 z-50 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cancelRemove"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-sm w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Remove Athlete</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                        Are you sure you want to remove
                        <span class="font-semibold">{{ athleteToRemove?.name }}</span>
                        from {{ sport.name }}?
                    </p>
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="cancelRemove">Cancel</SecondaryButton>
                        <button @click="removeAthlete"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
