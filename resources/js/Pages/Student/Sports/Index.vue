<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    borrowings: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    item_name: '',
    description: '',
    borrow_date: new Date().toISOString().split('T')[0],
    expected_return_date: '',
    notes: '',
});

const statusFilter = ref(props.filters.status || '');

const submitForm = () => {
    form.post(route('student.sports.borrowings.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.borrow_date = new Date().toISOString().split('T')[0];
        },
    });
};

const filterByStatus = (status) => {
    statusFilter.value = status;
    router.get(route('student.sports.index'), { status: status || null }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusBadgeClass = (status, statusColor) => {
    const colorMap = {
        'yellow': 'bg-yellow-100 text-yellow-800',
        'green': 'bg-green-100 text-green-800',
        'red': 'bg-red-100 text-red-800',
        'blue': 'bg-blue-100 text-blue-800',
        'gray': 'bg-gray-100 text-gray-800',
    };
    return colorMap[statusColor] || 'bg-gray-100 text-gray-800';
};

const goToDetail = (borrowing) => {
    router.visit(route('student.sports.borrowings.show', borrowing.borrowing_id));
};
</script>

<template>
    <Head title="Sports Unit - Equipment Borrowing" />

    <StudentLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900">
                Sports Unit - Equipment Borrowing
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ $page.props.flash.success }}</p>
            </div>

            <!-- Borrowing Form -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Equipment Borrowing</h3>
                    
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <InputLabel for="item_name" value="Item Name *" />
                            <TextInput
                                id="item_name"
                                v-model="form.item_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.item_name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <Textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="borrow_date" value="Borrow Date *" />
                                <TextInput
                                    id="borrow_date"
                                    v-model="form.borrow_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.borrow_date" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="expected_return_date" value="Expected Return Date *" />
                                <TextInput
                                    id="expected_return_date"
                                    v-model="form.expected_return_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    :min="form.borrow_date"
                                    required
                                />
                                <InputError :message="form.errors.expected_return_date" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="notes" value="Notes" />
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                                placeholder="Any additional notes or special requests..."
                            />
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Borrowing History -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">My Borrowing History</h3>
                        
                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <label for="status_filter" class="text-sm text-gray-700">Filter:</label>
                            <select
                                id="status_filter"
                                v-model="statusFilter"
                                @change="filterByStatus(statusFilter)"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 active:bg-gray-100 transition cursor-pointer"
                                @click="goToDetail(borrowing)"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-gray-900">{{ borrowing.item_name }}</span>
                                    <span
                                        class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                        :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)"
                                    >
                                        {{ borrowing.status }}
                                    </span>
                                </div>
                                <p v-if="borrowing.description" class="text-sm text-gray-500 mb-2 line-clamp-1">{{ borrowing.description }}</p>
                                <div class="flex items-center gap-3 text-xs text-gray-500">
                                    <span>Borrow: {{ borrowing.borrow_date }}</span>
                                    <span>Return: {{ borrowing.expected_return_date }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table Layout -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Item Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Borrow Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Expected Return
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="borrowing in borrowings.data"
                                        :key="borrowing.borrowing_id"
                                        class="hover:bg-gray-50 cursor-pointer"
                                        @click="goToDetail(borrowing)"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ borrowing.item_name }}</div>
                                            <div v-if="borrowing.description" class="text-sm text-gray-500 truncate max-w-xs">
                                                {{ borrowing.description }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ borrowing.borrow_date }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ borrowing.expected_return_date }}
                                        </td>
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
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="borrowings" />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No borrowings found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by submitting a borrowing request above.</p>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

