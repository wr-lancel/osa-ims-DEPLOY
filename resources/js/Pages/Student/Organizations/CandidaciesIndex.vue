<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    candidacies: {
        type: Array,
        default: () => [],
    },
});

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'under_review': return 'bg-blue-100 text-blue-800';
        case 'withdrawn': return 'bg-gray-100 text-gray-800';
        case 'submitted': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const canWithdraw = (status) => status === 'submitted' || status === 'under_review';

const withdraw = (application) => {
    if (!confirm('Are you sure you want to withdraw this candidacy?')) return;
    router.post(route('student.organizations.candidacy.withdraw', application.application_id));
};
</script>

<template>
    <Head title="My Candidacies" />

    <StudentLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-900">
                    My Candidacies
                </h2>
                <Link :href="route('student.organizations.candidacy.create')">
                    <PrimaryButton>Submit Candidacy</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificate of Candidacy Applications</h3>

                <div v-if="candidacies.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="app in candidacies" :key="app.application_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ app.org_name }}</div>
                                    <div class="text-xs text-gray-500">{{ app.org_code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ app.position_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.term_label || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusColor(app.status)"
                                    >
                                        {{ app.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ app.submitted_at || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <Link
                                        :href="route('student.organizations.candidacy.show', app.application_id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        View
                                    </Link>
                                    <button
                                        v-if="canWithdraw(app.status)"
                                        type="button"
                                        class="text-red-600 hover:text-red-900"
                                        @click="withdraw(app)"
                                    >
                                        Withdraw
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-8">
                    <p class="text-sm text-gray-500">You have not submitted any candidacies yet.</p>
                    <Link
                        :href="route('student.organizations.candidacy.create')"
                        class="mt-3 inline-flex text-indigo-600 hover:text-indigo-900 text-sm"
                    >
                        Submit your first candidacy
                    </Link>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
