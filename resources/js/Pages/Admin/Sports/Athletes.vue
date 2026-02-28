<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    sports: {
        type: Array,
        default: () => [],
    },
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const selectedSport = ref(null);

const addForm = useForm({
    name: '',
    description: '',
});

const editForm = useForm({
    name: '',
    description: '',
    status: 'active',
});

const openAddModal = () => {
    addForm.reset();
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
};

const openEditModal = (sport) => {
    selectedSport.value = sport;
    editForm.name = sport.name;
    editForm.description = sport.description || '';
    editForm.status = sport.status;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedSport.value = null;
    editForm.reset();
};

const submitAdd = () => {
    addForm.post(route('admin.sports.sports.store'), {
        preserveScroll: true,
        onSuccess: () => closeAddModal(),
    });
};

const submitEdit = () => {
    if (!selectedSport.value) return;
    editForm.put(route('admin.sports.sports.update', selectedSport.value.sport_id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

const goToSport = (sport) => {
    router.visit(route('admin.sports.sports.show', sport.sport_id));
};

const sportColors = [
    'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500',
    'bg-pink-500', 'bg-teal-500', 'bg-indigo-500', 'bg-red-500',
    'bg-cyan-500', 'bg-amber-500',
];

const getColorClass = (index) => sportColors[index % sportColors.length];

const sportIcons = ['🏀', '⚽', '🏐', '🏸', '🏊', '🎾', '🏓', '🥊', '🏋️', '⚾'];
const getIcon = (index) => sportIcons[index % sportIcons.length];
</script>

<template>
    <Head title="Athletes" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Athletes
                </h2>
                <div class="flex space-x-3">
                    <SecondaryButton @click="router.visit(route('admin.sports.index'))">
                        Back to Sports Unit
                    </SecondaryButton>
                    <PrimaryButton @click="openAddModal">
                        Add Sport
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Sports Cards Grid -->
            <div v-if="sports.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div
                    v-for="(sport, index) in sports"
                    :key="sport.sport_id"
                    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer group overflow-hidden"
                    @click="goToSport(sport)"
                >
                    <!-- Color accent bar -->
                    <div :class="[getColorClass(index), 'h-2']"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ getIcon(index) }}</span>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                        {{ sport.name }}
                                    </h3>
                                    <span
                                        class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full"
                                        :class="sport.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    >
                                        {{ sport.status }}
                                    </span>
                                </div>
                            </div>
                            <!-- Edit button (stop propagation) -->
                            <button
                                class="p-1 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 opacity-0 group-hover:opacity-100 transition-opacity"
                                @click.stop="openEditModal(sport)"
                                title="Edit sport"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="sport.description" class="text-sm text-gray-500 mb-3 line-clamp-2">
                            {{ sport.description }}
                        </p>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="font-medium">{{ sport.athletes_count }}</span>
                            <span class="ml-1">{{ sport.athletes_count === 1 ? 'athlete' : 'athletes' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-lg border border-gray-200 shadow-sm p-12 text-center">
                <div class="text-4xl mb-4">🏆</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No sports added yet</h3>
                <p class="text-sm text-gray-500 mb-6">Get started by adding your first sport.</p>
                <PrimaryButton @click="openAddModal">
                    Add Sport
                </PrimaryButton>
            </div>
        </div>

        <!-- Add Sport Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Add New Sport</h3>
                    <form @submit.prevent="submitAdd">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sport Name</label>
                            <input
                                v-model="addForm.name"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g. Basketball"
                                required
                            />
                            <p v-if="addForm.errors.name" class="mt-1 text-sm text-red-600">{{ addForm.errors.name }}</p>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea
                                v-model="addForm.description"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Brief description of the sport..."
                            ></textarea>
                            <p v-if="addForm.errors.description" class="mt-1 text-sm text-red-600">{{ addForm.errors.description }}</p>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeAddModal">Cancel</SecondaryButton>
                            <PrimaryButton type="submit" :disabled="addForm.processing">
                                {{ addForm.processing ? 'Creating...' : 'Create Sport' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Sport Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeEditModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Sport</h3>
                    <form @submit.prevent="submitEdit">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sport Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="editForm.status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeEditModal">Cancel</SecondaryButton>
                            <PrimaryButton type="submit" :disabled="editForm.processing">
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
