<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatusProgressBar from '@/Components/StatusProgressBar.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    borrowing: {
        type: Object,
        required: true,
    },
});

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

const borrowerLabel = () => {
    const b = props.borrowing.borrower;
    if (!b) return 'N/A';
    return `${b.name} (${b.number || b.id})`;
};
</script>

<template>

    <Head title="Borrowing Details" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    Borrowing Details
                </h2>
                <Link :href="route('admin.sports.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Back to Sports Unit
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ borrowing.item_name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Request ID: #{{ borrowing.borrowing_id }}</p>
                    </div>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                        :class="getStatusBadgeClass(borrowing.status, borrowing.status_color)">
                        {{ borrowing.status }}
                    </span>
                </div>
                <StatusProgressBar :steps="[
                    { value: 'pending', label: 'Pending' },
                    { value: 'approved', label: 'Approved' },
                    { value: 'borrowed', label: 'Borrowed' },
                    { value: 'returned', label: 'Returned' },
                ]" :current-status="borrowing.status" :terminal-statuses="['rejected', 'overdue']" :editable="true"
                    @update:status="(newStatus) => router.put(route('admin.sports.borrowings.updateStatus', borrowing.borrowing_id), { status: newStatus }, { preserveScroll: true })" />
            </div>

            <!-- Request Information -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Borrower</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowerLabel() }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Item Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowing.item_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Borrow Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowing.borrow_date }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Expected Return Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowing.expected_return_date }}</dd>
                        </div>

                        <div v-if="borrowing.return_date">
                            <dt class="text-sm font-medium text-gray-500">Return Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowing.return_date }}</dd>
                        </div>

                        <div v-if="borrowing.description" class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ borrowing.description }}</dd>
                        </div>

                        <div v-if="borrowing.notes" class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ borrowing.notes }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Admin Response -->
            <div v-if="borrowing.admin_remarks || borrowing.approved_at || borrowing.rejected_at"
                class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Response</h3>

                    <dl class="space-y-4">
                        <div v-if="borrowing.approved_at">
                            <dt class="text-sm font-medium text-gray-500">Approved On</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ borrowing.approved_at }}
                                <span v-if="borrowing.approver_name" class="text-gray-500">
                                    by {{ borrowing.approver_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="borrowing.rejected_at">
                            <dt class="text-sm font-medium text-gray-500">Rejected On</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ borrowing.rejected_at }}
                                <span v-if="borrowing.rejector_name" class="text-gray-500">
                                    by {{ borrowing.rejector_name }}
                                </span>
                            </dd>
                        </div>

                        <div v-if="borrowing.admin_remarks">
                            <dt class="text-sm font-medium text-gray-500">Admin Remarks</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ borrowing.admin_remarks }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Actions (if borrowed, allow mark returned) -->
            <div v-if="borrowing.status === 'Borrowed' || borrowing.status === 'borrowed'" class="flex justify-end">
                <PrimaryButton @click="router.put(route('admin.sports.borrowings.update', borrowing.borrowing_id), {
                    item_name: borrowing.item_name,
                    description: borrowing.description,
                    borrow_date: borrowing.borrow_date,
                    expected_return_date: borrowing.expected_return_date,
                    status: 'returned',
                    return_date: new Date().toISOString().split('T')[0],
                    notes: borrowing.notes,
                    admin_remarks: borrowing.admin_remarks,
                }, { preserveScroll: false })">
                    Mark as Returned
                </PrimaryButton>
            </div>

            <!-- Back Button -->
            <div class="flex justify-end">
                <Link :href="route('admin.sports.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to Sports Unit
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
