<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    complaint: {
        type: Object,
        required: true,
    },
    history: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    status: props.complaint.status,
    remarks: '',
});

const submit = () => {
    form.put(route('admin.discipline.complaints.update', props.complaint.complaint_id), {
        preserveScroll: true,
        onSuccess: () => form.remarks = '',
    });
};

const getStatusColor = (s) => {
    if (s === 'resolved') return 'bg-green-100 text-green-800';
    if (s === 'dismissed') return 'bg-gray-100 text-gray-800';
    if (s === 'escalated') return 'bg-red-100 text-red-800';
    if (s === 'under_review') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

const formatStatus = (s) => s ? s.replace(/_/g, ' ') : '';
</script>

<template>
    <Head :title="`Complaint #${complaint.complaint_id}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">
                        Complaint #{{ complaint.complaint_id }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1" v-if="complaint.complainant">
                        {{ complaint.complainant.full_name }} ({{ complaint.complainant.student_number }})
                    </p>
                </div>
                <Link
                    :href="route('admin.discipline.complaints.index')"
                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                >
                    ← Complaints Inbox
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">{{ $page.props.flash.success }}</p>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Complaint Details</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Subject</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.category }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date Submitted</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.date_submitted }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Incident Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.incident_date }}</dd>
                    </div>
                    <div class="md:col-span-2" v-if="complaint.location">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.location }}</dd>
                    </div>
                    <div class="md:col-span-2" v-if="complaint.respondent">
                        <dt class="text-sm font-medium text-gray-500">Respondent</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ complaint.respondent.full_name }} ({{ complaint.respondent.student_number }})</dd>
                    </div>
                    <div class="md:col-span-4">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ complaint.description }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
                <form @submit.prevent="submit" class="space-y-4 max-w-2xl">
                    <div>
                        <InputLabel for="status" value="Status *" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="s in statusOptions"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="remarks" value="Remarks (optional)" />
                        <Textarea
                            id="remarks"
                            v-model="form.remarks"
                            class="mt-1 block w-full"
                            rows="3"
                            placeholder="Add a note for the student..."
                        />
                        <InputError :message="form.errors.remarks" class="mt-1" />
                    </div>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        Update Complaint
                    </PrimaryButton>
                </form>
            </div>

            <div v-if="history.length > 0" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status History</h3>
                <ul class="space-y-4">
                    <li
                        v-for="(h, i) in history"
                        :key="i"
                        class="flex gap-4 border-l-2 border-gray-200 pl-4 py-2"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">
                                <span v-if="h.old_status" class="capitalize">{{ formatStatus(h.old_status) }}</span>
                                <span v-if="h.old_status"> → </span>
                                <span class="font-medium capitalize">{{ formatStatus(h.new_status) }}</span>
                            </p>
                            <p v-if="h.remarks" class="text-sm text-gray-600 mt-1">{{ h.remarks }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ h.created_at }} <span v-if="h.changed_by">· {{ h.changed_by }}</span></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
