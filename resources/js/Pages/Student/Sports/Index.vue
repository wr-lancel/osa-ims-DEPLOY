<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import SortableHeader from '@/Components/SortableHeader.vue';

const props = defineProps({
    borrowings: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    equipmentList: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    item_name: '',
    description: '',
    borrow_date: new Date().toISOString().split('T')[0],
    expected_return_date: '',
    notes: '',
});

const selectedEquipment = ref('');
const formRef = ref(null);
const statusFilter = ref(props.filters.status || '');
const sortBy = ref(props.filters.sort_by || '');
const sortDir = ref(props.filters.sort_dir || 'desc');

const selectEquipment = (name) => {
    if (selectedEquipment.value === name) {
        // Deselect if clicking the same card
        selectedEquipment.value = '';
        form.item_name = '';
    } else {
        selectedEquipment.value = name;
        form.item_name = name;
        // Scroll to form
        formRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// If user types manually, clear the card selection
const onItemNameInput = () => {
    if (form.item_name !== selectedEquipment.value) {
        selectedEquipment.value = '';
    }
};

const weekendError = ref('');
const borrowDateError = ref('');
const returnDateError = ref('');

const validateWeekday = (dateStr) => {
    if (!dateStr) return true;
    const [year, month, day] = dateStr.split('-').map(Number);
    const d = new Date(year, month - 1, day);
    return d.getDay() !== 0 && d.getDay() !== 6;
};

const onBorrowDateChange = () => {
    if (!validateWeekday(form.borrow_date)) {
        form.borrow_date = '';
        borrowDateError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        borrowDateError.value = '';
    }
};

const onReturnDateChange = () => {
    if (!validateWeekday(form.expected_return_date)) {
        form.expected_return_date = '';
        returnDateError.value = 'Weekends are not available. Please select a weekday (Monday – Friday).';
    } else {
        returnDateError.value = '';
    }
};

const submitForm = () => {
    form.post(route('student.sports.borrowings.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.borrow_date = new Date().toISOString().split('T')[0];
            selectedEquipment.value = '';
            borrowDateError.value = '';
            returnDateError.value = '';
        },
    });
};

const filterByStatus = (status) => {
    statusFilter.value = status;
    router.get(route('student.sports.index'), {
        status: status || null,
        sort_by: sortBy.value || undefined,
        sort_dir: sortDir.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
    });
};

const handleSort = ({ column, dir }) => {
    sortBy.value = column;
    sortDir.value = dir;
    router.get(route('student.sports.index'), {
        status: statusFilter.value || undefined,
        sort_by: column,
        sort_dir: dir,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusBadgeClass = (status, statusColor) => {
    const colorMap = {
        'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'green': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'red': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'blue': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'gray': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return colorMap[statusColor] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const goToDetail = (borrowing) => {
    router.visit(route('student.sports.borrowings.show', borrowing.borrowing_id));
};
</script>

<template>
    <Head title="Sports Unit - Equipment Borrowing" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Sports Unit
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Borrow Equipment Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                <!-- Card Header -->
                <div class="flex items-start justify-between p-6 pb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Borrow Equipment</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Submit a request to borrow sports equipment</p>
                    </div>
                    <!-- Sports icon -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="px-6 pb-6 space-y-5">
                    <!-- Policy Notice -->
                    <div class="flex items-start gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <svg class="flex-shrink-0 w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            Equipment must be returned within the specified date. Late returns may result in borrowing privileges suspension.
                        </p>
                    </div>

                    <!-- Available Equipment Grid -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Available Equipment for Borrowing</h4>

                        <div v-if="equipmentList.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <button
                                v-for="item in equipmentList"
                                :key="item"
                                type="button"
                                @click="selectEquipment(item)"
                                class="relative flex items-center justify-center px-4 py-3.5 rounded-xl border-2 text-sm font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                :class="selectedEquipment === item
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm'
                                    : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:border-indigo-300 dark:hover:border-indigo-500 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400'"
                            >
                                <!-- Selected checkmark -->
                                <span
                                    v-if="selectedEquipment === item"
                                    class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-indigo-500 flex items-center justify-center"
                                >
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                {{ item }}
                            </button>
                        </div>

                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
                            No equipment configured. Contact an administrator.
                        </p>

                        <p v-if="selectedEquipment" class="mt-2 text-xs text-indigo-600 dark:text-indigo-400">
                            <span class="font-medium">{{ selectedEquipment }}</span> selected — fill in the form below to submit your request.
                        </p>
                    </div>

                    <!-- Borrow Form -->
                    <div ref="formRef" class="border-t border-gray-100 dark:border-gray-700 pt-5">
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <!-- Item Name -->
                            <div>
                                <InputLabel for="item_name" value="Equipment Name *" />
                                <TextInput
                                    id="item_name"
                                    v-model="form.item_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Select from above or type equipment name"
                                    required
                                    @input="onItemNameInput"
                                />
                                <InputError :message="form.errors.item_name" class="mt-2" />
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Description" />
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full"
                                    rows="2"
                                    placeholder="Additional details about your borrowing request..."
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="borrow_date" value="Borrow Date *" />
                                    <TextInput
                                        id="borrow_date"
                                        v-model="form.borrow_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        :min="new Date().toISOString().split('T')[0]"
                                        required
                                        @change="onBorrowDateChange"
                                    />
                                    <p v-if="borrowDateError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ borrowDateError }}</p>
                                    <InputError :message="form.errors.borrow_date" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="expected_return_date" value="Expected Return Date *" />
                                    <TextInput
                                        id="expected_return_date"
                                        v-model="form.expected_return_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        :min="form.borrow_date || new Date().toISOString().split('T')[0]"
                                        required
                                        @change="onReturnDateChange"
                                    />
                                    <p v-if="returnDateError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ returnDateError }}</p>
                                    <InputError :message="form.errors.expected_return_date" class="mt-2" />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <InputLabel for="notes" value="Notes" />
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full"
                                    rows="2"
                                    placeholder="Any special requests or additional notes..."
                                />
                                <InputError :message="form.errors.notes" class="mt-2" />
                            </div>

                            <!-- Submit Button — full width, matching mockup -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full flex items-center justify-center px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                            >
                                {{ form.processing ? 'Submitting...' : 'Request to Borrow Equipment' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Borrowing History -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Borrowing History</h3>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label for="status_filter" class="text-sm text-gray-700 dark:text-gray-200">Filter:</label>
                            <select
                                id="status_filter"
                                v-model="statusFilter"
                                @change="filterByStatus(statusFilter)"
                                class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm dark:bg-gray-700 dark:text-gray-100"
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="borrowed">Borrowed</option>
                                <option value="returned">Returned</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                    </div>

                    <!-- Borrowings Content -->
                    <div v-if="borrowings.data && borrowings.data.length > 0">
                        <!-- Mobile Card Layout -->
                        <div class="md:hidden space-y-3">
                            <div
                                v-for="borrowing in borrowings.data"
                                :key="'m-' + borrowing.borrowing_id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
                                @click="goToDetail(borrowing)"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ borrowing.item_name }}</span>
                                    <span
                                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                        :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)"
                                    >
                                        {{ borrowing.status }}
                                    </span>
                                </div>
                                <p v-if="borrowing.description" class="text-sm text-gray-500 dark:text-gray-400 mb-2 line-clamp-1">{{ borrowing.description }}</p>
                                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Borrow: {{ borrowing.borrow_date }}</span>
                                    <span>Return: {{ borrowing.expected_return_date }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table Layout -->
                        <div class="hidden md:block overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                                        <SortableHeader column="borrow_date" label="Borrow Date" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                        <SortableHeader column="expected_return_date" label="Expected Return" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                        <SortableHeader column="status" label="Status" :currentSort="sortBy" :currentDir="sortDir" @sort="handleSort" />
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="borrowing in borrowings.data"
                                        :key="borrowing.borrowing_id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                                        @click="goToDetail(borrowing)"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ borrowing.item_name }}</div>
                                            <div v-if="borrowing.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ borrowing.description }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ borrowing.borrow_date }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ borrowing.expected_return_date }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)"
                                            >
                                                {{ borrowing.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" @click.stop>
                                            <Link
                                                :href="route('student.sports.borrowings.show', borrowing.borrowing_id)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                            >
                                                View Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Pagination :data="borrowings" />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No borrowings found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by selecting equipment above and submitting a request.</p>
                    </div>
                </div>
            </div>
        </div>

        <LoadingOverlay :show="form.processing" message="Submitting... Please wait." />
    </StudentLayout>
</template>
