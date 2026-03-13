<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ gallery: Object });

const showRejectModal = ref(false);
const lightboxIndex = ref(null);
const newPhotos = ref([]);
const newPhotoPreviews = ref([]);
const newCaptions = ref([]);

const reviewForm = useForm({ action: 'approve', rejection_reason: '' });
const uploadForm = useForm({ photos: [], captions: [] });
const editForm = useForm({
    title: props.gallery.title,
    description: props.gallery.description || '',
    cover_image: null,
    status: props.gallery.status,
});

const coverPreview = ref(props.gallery.cover_image);

function onCoverChange(e) {
    const file = e.target.files[0];
    if (file) { editForm.cover_image = file; coverPreview.value = URL.createObjectURL(file); }
}

function onNewPhotos(e) {
    const files = Array.from(e.target.files);
    newPhotos.value = files;
    newCaptions.value = files.map(() => '');
    newPhotoPreviews.value = files.map(f => URL.createObjectURL(f));
    uploadForm.photos = files;
    uploadForm.captions = newCaptions.value;
}

function uploadPhotos() {
    uploadForm.photos = newPhotos.value;
    uploadForm.captions = newCaptions.value;
    uploadForm.post(route('admin.publications.galleries.photos.upload', props.gallery.slug), {
        forceFormData: true,
        onSuccess: () => { newPhotos.value = []; newPhotoPreviews.value = []; newCaptions.value = []; },
    });
}

function deletePhoto(photoId) {
    if (confirm('Delete this photo?')) {
        router.delete(route('admin.publications.galleries.photos.destroy', [props.gallery.slug, photoId]));
    }
}

function approve() {
    reviewForm.action = 'approve';
    reviewForm.post(route('admin.publications.galleries.review', props.gallery.slug));
}

function reject() {
    reviewForm.action = 'reject';
    reviewForm.post(route('admin.publications.galleries.review', props.gallery.slug), {
        onSuccess: () => { showRejectModal.value = false; },
    });
}

function saveEdit() {
    editForm.put(route('admin.publications.galleries.update', props.gallery.slug), { forceFormData: true });
}

function deleteGallery() {
    if (confirm('Delete this gallery and all its photos?')) {
        router.delete(route('admin.publications.galleries.destroy', props.gallery.slug));
    }
}

const statusColors = {
    draft: 'bg-slate-100 text-slate-600',
    pending_review: 'bg-amber-100 text-amber-700',
    published: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};
</script>

<template>
    <Head :title="gallery.title" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.publications.index', { tab: 'galleries' })" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ gallery.title }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColors[gallery.status]">
                        {{ gallery.status.replace('_', ' ') }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="gallery.status === 'pending_review'" @click="approve" class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-500 transition">Approve</button>
                    <button v-if="gallery.status === 'pending_review'" @click="showRejectModal = true" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-500 transition">Reject</button>
                    <button @click="deleteGallery" class="px-4 py-2 rounded-lg border border-red-200 text-red-600 text-sm font-medium hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-900/20 transition">Delete</button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl space-y-6">
            <!-- Edit Details -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-5">
                <h3 class="font-semibold text-slate-900 dark:text-white">Gallery Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Title</label>
                        <input v-model="editForm.title" type="text" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Status</label>
                        <select v-model="editForm.status" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <textarea v-model="editForm.description" rows="2" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cover Image</label>
                    <img v-if="coverPreview" :src="coverPreview" class="h-24 w-full object-cover rounded-lg mb-2 border border-slate-200 dark:border-slate-700" />
                    <input type="file" accept="image/*" @change="onCoverChange" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition" />
                </div>
                <button @click="saveEdit" :disabled="editForm.processing" class="px-5 py-2 rounded-lg bg-slate-900 dark:bg-indigo-600 text-white text-sm font-medium hover:bg-slate-800 dark:hover:bg-indigo-500 disabled:opacity-50 transition">
                    {{ editForm.processing ? 'Saving...' : 'Save Details' }}
                </button>
            </div>

            <!-- Photos -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900 dark:text-white">Photos ({{ gallery.photos.length }})</h3>
                </div>

                <div v-if="gallery.rejection_reason" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-400">
                    <strong>Rejection reason:</strong> {{ gallery.rejection_reason }}
                </div>

                <div v-if="gallery.photos.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <div v-for="photo in gallery.photos" :key="photo.photo_id" class="relative group">
                        <img :src="photo.image_path" @click="lightboxIndex = gallery.photos.indexOf(photo)"
                            class="w-full h-28 object-cover rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:opacity-90 transition" />
                        <button @click="deletePhoto(photo.photo_id)"
                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition">
                            ×
                        </button>
                        <p v-if="photo.caption" class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">{{ photo.caption }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 text-center py-4">No photos yet.</p>

                <!-- Upload more photos -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-4 space-y-3">
                    <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">Add Photos</h4>
                    <input type="file" accept="image/*" multiple @change="onNewPhotos"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 transition" />

                    <div v-if="newPhotoPreviews.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div v-for="(preview, i) in newPhotoPreviews" :key="i">
                            <img :src="preview" class="w-full h-24 object-cover rounded-lg border border-slate-200 dark:border-slate-700" />
                            <input v-model="newCaptions[i]" type="text" placeholder="Caption..."
                                class="w-full mt-1 border border-slate-300 dark:border-slate-600 rounded px-2 py-1 text-xs bg-white dark:bg-slate-700 text-slate-900 dark:text-white outline-none" />
                        </div>
                    </div>

                    <button v-if="newPhotos.length" @click="uploadPhotos" :disabled="uploadForm.processing"
                        class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-500 disabled:opacity-50 transition">
                        {{ uploadForm.processing ? 'Uploading...' : `Upload ${newPhotos.length} Photo(s)` }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxIndex !== null" class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click.self="lightboxIndex = null">
                <button @click="lightboxIndex = null" class="absolute top-4 right-4 text-white text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition">×</button>
                <button v-if="lightboxIndex > 0" @click="lightboxIndex--" class="absolute left-4 text-white text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition">‹</button>
                <img :src="gallery.photos[lightboxIndex]?.image_path" class="max-h-screen max-w-full rounded-lg" />
                <button v-if="lightboxIndex < gallery.photos.length - 1" @click="lightboxIndex++" class="absolute right-4 text-white text-2xl font-bold w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition">›</button>
            </div>
        </Teleport>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Reject Gallery</h3>
                    <textarea v-model="reviewForm.rejection_reason" rows="4" placeholder="Reason for rejection..."
                        class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2.5 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white resize-none outline-none focus:ring-2 focus:ring-red-500 transition" />
                    <div class="flex gap-3 mt-4">
                        <button @click="reject" :disabled="reviewForm.processing" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-500 disabled:opacity-50 transition">Confirm Reject</button>
                        <button @click="showRejectModal = false" class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">Cancel</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
