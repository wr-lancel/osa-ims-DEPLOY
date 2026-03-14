<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const props = defineProps({
    requests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    counts: { type: Object, default: () => ({}) },
});

const flash = computed(() => usePage().props.flash || {});

// Search & filter
const search = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || '');

const applyFilter = (status) => {
    activeStatus.value = status;
    router.get(route('admin.good-moral.index'), { status: status || undefined, search: search.value || undefined }, { preserveState: true, replace: true });
};

const applySearch = () => {
    router.get(route('admin.good-moral.index'), { status: activeStatus.value || undefined, search: search.value || undefined }, { preserveState: true, replace: true });
};

// Update modal
const showModal = ref(false);
const selectedRequest = ref(null);

const updateForm = useForm({
    status: '',
    admin_notes: '',
});

const openModal = (req) => {
    selectedRequest.value = req;
    updateForm.status = req.status;
    updateForm.admin_notes = req.admin_notes || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedRequest.value = null;
};

const saveUpdate = () => {
    updateForm.put(route('admin.good-moral.update', selectedRequest.value.id), {
        onSuccess: () => closeModal(),
    });
};

const statusTabs = [
    { key: '', label: 'All', color: 'slate' },
    { key: 'pending', label: 'Pending', color: 'amber' },
    { key: 'payment_verified', label: 'Payment Verified', color: 'blue' },
    { key: 'ready_for_pickup', label: 'Ready for Pickup', color: 'indigo' },
    { key: 'released', label: 'Released', color: 'emerald' },
];

const statusBadgeClass = (status) => ({
    'pending':          'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'payment_verified': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'ready_for_pickup': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    'released':         'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
}[status] || 'bg-slate-100 text-slate-600');

const statusLabel = (status) => ({
    'pending':          'Pending',
    'payment_verified': 'Payment Verified',
    'ready_for_pickup': 'Ready for Pickup',
    'released':         'Released',
}[status] || status);

const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Good Moral Requests" />

    <AdminLayout>
        <LoadingOverlay :show="updateForm.processing" message="Processing... Please wait." />
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Good Moral Requests</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Certificate of Good Moral Character requests from alumni</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    {{ counts.all }} Total Requests
                </span>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Flash -->
            <div v-if="flash.success" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl text-sm text-emerald-700 dark:text-emerald-400 font-medium">
                {{ flash.success }}
            </div>

            <!-- Status Tabs + Search -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl overflow-x-auto">
                    <button v-for="tab in statusTabs" :key="tab.key"
                        @click="applyFilter(tab.key)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors"
                        :class="activeStatus === tab.key
                            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                        {{ tab.label }}
                        <span class="ml-1 opacity-60">{{ counts[tab.key || 'all'] ?? '' }}</span>
                    </button>
                </div>
                <div class="flex-1 relative">
                    <input v-model="search" @keyup.enter="applySearch" type="text" placeholder="Search by name, student no., or email..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors" />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/80 uppercase border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Name</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Student No.</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Course</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Year Grad.</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Submitted</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Status</th>
                                <th class="px-5 py-3 font-semibold whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="req in requests.data" :key="req.id"
                                class="border-b border-slate-100 dark:border-slate-700/50 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ req.full_name }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ req.email }}</div>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ req.student_number }}</td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300 max-w-[180px]">
                                    <span class="line-clamp-1">{{ req.course }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ req.year_graduated }}</td>
                                <td class="px-5 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ formatDate(req.created_at) }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold" :class="statusBadgeClass(req.status)">
                                        {{ statusLabel(req.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <button @click="openModal(req)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                        Update
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">
                                    No requests found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="requests.last_page > 1" class="flex items-center justify-between px-5 py-4 border-t border-slate-100 dark:border-slate-700/50">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Showing {{ requests.from }}–{{ requests.to }} of {{ requests.total }} results
                    </span>
                    <div class="flex items-center gap-1">
                        <a v-for="link in requests.links" :key="link.label"
                            v-html="link.label"
                            :href="link.url || '#'"
                            @click.prevent="link.url && router.get(link.url)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active
                                ? 'bg-slate-900 dark:bg-indigo-600 text-white'
                                : link.url
                                    ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                                    : 'text-slate-300 dark:text-slate-600 cursor-default'" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <!-- Modal Header -->
                        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Update Request</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedRequest?.full_name }}</p>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-4">
                            <!-- Request Details -->
                            <div class="grid grid-cols-2 gap-3 p-4 bg-slate-50 dark:bg-slate-700/30 rounded-xl text-sm">
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">Student No.</p>
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ selectedRequest?.student_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">Course</p>
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ selectedRequest?.course }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">Year Graduated</p>
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ selectedRequest?.year_graduated }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">Contact</p>
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ selectedRequest?.contact_number }}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs text-slate-400 mb-0.5">Purpose</p>
                                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ selectedRequest?.purpose }}</p>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Status</label>
                                <select v-model="updateForm.status"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                                    <option value="pending">Pending</option>
                                    <option value="payment_verified">Payment Verified</option>
                                    <option value="ready_for_pickup">Ready for Pickup</option>
                                    <option value="released">Released</option>
                                </select>
                                <p v-if="updateForm.errors.status" class="mt-1.5 text-xs text-red-500">{{ updateForm.errors.status }}</p>
                            </div>

                            <!-- Admin Notes -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                                <textarea v-model="updateForm.admin_notes" rows="3" placeholder="Add any notes or instructions for this request..."
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-3">
                            <button @click="closeModal" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                Cancel
                            </button>
                            <button @click="saveUpdate" :disabled="updateForm.processing"
                                class="px-5 py-2 rounded-xl text-sm font-semibold bg-slate-900 dark:bg-indigo-600 text-white hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition-colors">
                                {{ updateForm.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
