<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewPublicationRequest;
use App\Http\Requests\StorePublicationArticleRequest;
use App\Http\Requests\StorePublicationGalleryRequest;
use App\Http\Requests\StorePublicationNewspaperRequest;
use App\Http\Requests\UpdatePublicationArticleRequest;
use App\Http\Requests\UpdatePublicationGalleryRequest;
use App\Http\Requests\UpdatePublicationNewspaperRequest;
use App\Models\PublicationArticle;
use App\Models\PublicationGallery;
use App\Models\PublicationGalleryPhoto;
use App\Models\PublicationNewspaper;
use App\Models\StudentOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    // ─────────────────────────── INDEX ───────────────────────────

    public function index(Request $request): Response
    {
        $search = $request->get('search', '');
        $statusFilter = $request->get('status', '');
        $tab = $request->get('tab', 'articles');

        $articleQuery = PublicationArticle::with('author')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->orderBy('created_at', 'desc');

        $newspaperQuery = PublicationNewspaper::with('author')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->orderBy('created_at', 'desc');

        $galleryQuery = PublicationGallery::with('author')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->orderBy('created_at', 'desc');

        return Inertia::render('Admin/Publications/Index', [
            'articles' => $articleQuery->paginate(15)->through(fn($a) => $this->formatArticle($a))->appends($request->query()),
            'newspapers' => $newspaperQuery->paginate(15)->through(fn($n) => $this->formatNewspaper($n))->appends($request->query()),
            'galleries' => $galleryQuery->paginate(15)->through(fn($g) => $this->formatGallery($g))->appends($request->query()),
            'stats' => [
                'total_articles' => PublicationArticle::count(),
                'published_articles' => PublicationArticle::published()->count(),
                'pending_articles' => PublicationArticle::pending()->count(),
                'total_newspapers' => PublicationNewspaper::count(),
                'published_newspapers' => PublicationNewspaper::published()->count(),
                'total_galleries' => PublicationGallery::count(),
                'published_galleries' => PublicationGallery::published()->count(),
            ],
            'filters' => ['search' => $search, 'status' => $statusFilter, 'tab' => $tab],
            'publication_org' => StudentOrganization::where('is_publication_org', true)->first()?->only(['org_id', 'org_name']),
            'organizations' => StudentOrganization::select('org_id', 'org_name')->orderBy('org_name')->get(),
        ]);
    }

    // ─────────────────────────── ARTICLES ───────────────────────────

    public function createArticle(): Response
    {
        return Inertia::render('Admin/Publications/CreateArticle');
    }

    public function storeArticle(StorePublicationArticleRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['publication_admin', 'admin', 'super_admin']);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/articles/covers');
            $coverPath = $request->file('cover_image')->store('publications/articles/covers', 'public');
        }

        $status = $data['status'] ?? 'draft';
        if ($status === 'published' && !$isAdmin) {
            $status = 'pending_review';
        }

        $article = PublicationArticle::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_articles', 'slug'),
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'cover_image' => $coverPath,
            'status' => $status,
            'author_id' => $user->user_id,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return redirect()->route('admin.publications.articles.show', $article->slug)
            ->with('success', 'Article created successfully.');
    }

    public function showArticle(PublicationArticle $article): Response
    {
        $article->load('author', 'reviewer');
        return Inertia::render('Admin/Publications/ShowArticle', [
            'article' => $this->formatArticle($article),
        ]);
    }

    public function updateArticle(UpdatePublicationArticleRequest $request, PublicationArticle $article)
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image) {
                Storage::disk('public')->delete($article->cover_image);
            }
            Storage::disk('public')->makeDirectory('publications/articles/covers');
            $data['cover_image'] = $request->file('cover_image')->store('publications/articles/covers', 'public');
        } else {
            unset($data['cover_image']);
        }

        if (isset($data['title']) && $data['title'] !== $article->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], 'publication_articles', 'slug', $article->article_id, 'article_id');
        }

        $article->update($data);

        return back()->with('success', 'Article updated successfully.');
    }

    public function destroyArticle(PublicationArticle $article)
    {
        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }
        $article->delete();

        return redirect()->route('admin.publications.index', ['tab' => 'articles'])
            ->with('success', 'Article deleted.');
    }

    public function reviewArticle(ReviewPublicationRequest $request, PublicationArticle $article)
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($data['action'] === 'approve') {
            $article->update([
                'status' => 'published',
                'published_at' => now(),
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        } else {
            $article->update([
                'status' => 'rejected',
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        return back()->with('success', 'Article review submitted.');
    }

    // ─────────────────────────── NEWSPAPERS ───────────────────────────

    public function createNewspaper(): Response
    {
        return Inertia::render('Admin/Publications/CreateNewspaper');
    }

    public function storeNewspaper(StorePublicationNewspaperRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['publication_admin', 'admin', 'super_admin']);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/newspapers/covers');
            $coverPath = $request->file('cover_image')->store('publications/newspapers/covers', 'public');
        }

        $status = $data['status'] ?? 'draft';
        if ($status === 'published' && !$isAdmin) {
            $status = 'pending_review';
        }

        $newspaper = PublicationNewspaper::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_newspapers', 'slug'),
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'cover_image' => $coverPath,
            'issue_number' => $data['issue_number'] ?? null,
            'status' => $status,
            'author_id' => $user->user_id,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return redirect()->route('admin.publications.newspapers.show', $newspaper->slug)
            ->with('success', 'Newspaper created successfully.');
    }

    public function showNewspaper(PublicationNewspaper $newspaper): Response
    {
        $newspaper->load('author', 'reviewer');
        return Inertia::render('Admin/Publications/ShowNewspaper', [
            'newspaper' => $this->formatNewspaper($newspaper),
        ]);
    }

    public function updateNewspaper(UpdatePublicationNewspaperRequest $request, PublicationNewspaper $newspaper)
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($newspaper->cover_image) {
                Storage::disk('public')->delete($newspaper->cover_image);
            }
            Storage::disk('public')->makeDirectory('publications/newspapers/covers');
            $data['cover_image'] = $request->file('cover_image')->store('publications/newspapers/covers', 'public');
        } else {
            unset($data['cover_image']);
        }

        if (isset($data['title']) && $data['title'] !== $newspaper->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], 'publication_newspapers', 'slug', $newspaper->newspaper_id, 'newspaper_id');
        }

        $newspaper->update($data);

        return back()->with('success', 'Newspaper updated successfully.');
    }

    public function destroyNewspaper(PublicationNewspaper $newspaper)
    {
        if ($newspaper->cover_image) {
            Storage::disk('public')->delete($newspaper->cover_image);
        }
        $newspaper->delete();

        return redirect()->route('admin.publications.index', ['tab' => 'newspapers'])
            ->with('success', 'Newspaper deleted.');
    }

    public function reviewNewspaper(ReviewPublicationRequest $request, PublicationNewspaper $newspaper)
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($data['action'] === 'approve') {
            $newspaper->update([
                'status' => 'published',
                'published_at' => now(),
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        } else {
            $newspaper->update([
                'status' => 'rejected',
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        return back()->with('success', 'Newspaper review submitted.');
    }

    // ─────────────────────────── GALLERIES ───────────────────────────

    public function createGallery(): Response
    {
        return Inertia::render('Admin/Publications/CreateGallery');
    }

    public function storeGallery(StorePublicationGalleryRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['publication_admin', 'admin', 'super_admin']);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/galleries/covers');
            $coverPath = $request->file('cover_image')->store('publications/galleries/covers', 'public');
        }

        $status = $data['status'] ?? 'draft';
        if ($status === 'published' && !$isAdmin) {
            $status = 'pending_review';
        }

        $gallery = PublicationGallery::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_galleries', 'slug'),
            'description' => $data['description'] ?? null,
            'cover_image' => $coverPath,
            'status' => $status,
            'author_id' => $user->user_id,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        // Upload photos
        if ($request->hasFile('photos')) {
            Storage::disk('public')->makeDirectory("publications/galleries/{$gallery->gallery_id}");
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store("publications/galleries/{$gallery->gallery_id}", 'public');
                PublicationGalleryPhoto::create([
                    'gallery_id' => $gallery->gallery_id,
                    'image_path' => $path,
                    'caption' => $data['captions'][$index] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('admin.publications.galleries.show', $gallery->slug)
            ->with('success', 'Gallery created successfully.');
    }

    public function showGallery(PublicationGallery $gallery): Response
    {
        $gallery->load('author', 'reviewer', 'photos');
        return Inertia::render('Admin/Publications/ShowGallery', [
            'gallery' => $this->formatGallery($gallery),
        ]);
    }

    public function updateGallery(UpdatePublicationGalleryRequest $request, PublicationGallery $gallery)
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($gallery->cover_image) {
                Storage::disk('public')->delete($gallery->cover_image);
            }
            Storage::disk('public')->makeDirectory('publications/galleries/covers');
            $data['cover_image'] = $request->file('cover_image')->store('publications/galleries/covers', 'public');
        } else {
            unset($data['cover_image']);
        }

        if (isset($data['title']) && $data['title'] !== $gallery->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], 'publication_galleries', 'slug', $gallery->gallery_id, 'gallery_id');
        }

        $gallery->update($data);

        return back()->with('success', 'Gallery updated successfully.');
    }

    public function destroyGallery(PublicationGallery $gallery)
    {
        foreach ($gallery->photos as $photo) {
            Storage::disk('public')->delete($photo->image_path);
        }
        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }
        $gallery->delete();

        return redirect()->route('admin.publications.index', ['tab' => 'galleries'])
            ->with('success', 'Gallery deleted.');
    }

    public function reviewGallery(ReviewPublicationRequest $request, PublicationGallery $gallery)
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($data['action'] === 'approve') {
            $gallery->update([
                'status' => 'published',
                'published_at' => now(),
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        } else {
            $gallery->update([
                'status' => 'rejected',
                'reviewed_by' => $user->user_id,
                'reviewed_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        return back()->with('success', 'Gallery review submitted.');
    }

    public function uploadPhotos(Request $request, PublicationGallery $gallery)
    {
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);

        $maxOrder = $gallery->photos()->max('sort_order') ?? -1;

        foreach ($request->file('photos') as $index => $photo) {
            $path = $photo->store("publications/galleries/{$gallery->gallery_id}", 'public');
            PublicationGalleryPhoto::create([
                'gallery_id' => $gallery->gallery_id,
                'image_path' => $path,
                'caption' => $request->input("captions.{$index}"),
                'sort_order' => $maxOrder + $index + 1,
            ]);
        }

        return back()->with('success', 'Photos uploaded.');
    }

    public function deletePhoto(PublicationGallery $gallery, PublicationGalleryPhoto $photo)
    {
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }

    public function reorderPhotos(Request $request, PublicationGallery $gallery)
    {
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        foreach ($request->input('order') as $sortOrder => $photoId) {
            PublicationGalleryPhoto::where('photo_id', $photoId)
                ->where('gallery_id', $gallery->gallery_id)
                ->update(['sort_order' => $sortOrder]);
        }

        return back()->with('success', 'Photos reordered.');
    }

    // ─────────────────────────── SETTINGS ───────────────────────────

    public function togglePublicationOrg(Request $request)
    {
        $request->validate(['org_id' => ['required', 'integer', 'exists:student_org,org_id']]);

        StudentOrganization::query()->update(['is_publication_org' => false]);
        StudentOrganization::where('org_id', $request->org_id)->update(['is_publication_org' => true]);

        return back()->with('success', 'Publication organization updated.');
    }

    // ─────────────────────────── HELPERS ───────────────────────────

    private function uniqueSlug(string $title, string $table, string $column, ?int $ignoreId = null, string $idColumn = 'id'): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (
            \DB::table($table)
                ->where($column, $slug)
                ->when($ignoreId, fn($q) => $q->where($idColumn, '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function formatArticle(PublicationArticle $article): array
    {
        return [
            'article_id' => $article->article_id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'cover_image' => $article->cover_image ? Storage::disk('public')->url($article->cover_image) : null,
            'status' => $article->status,
            'rejection_reason' => $article->rejection_reason,
            'author' => $article->author ? ['user_id' => $article->author->user_id, 'display_name' => $article->author->display_name] : null,
            'reviewer' => $article->reviewer ? ['user_id' => $article->reviewer->user_id, 'display_name' => $article->reviewer->display_name] : null,
            'published_at' => $article->published_at?->format('M d, Y'),
            'reviewed_at' => $article->reviewed_at?->format('M d, Y'),
            'created_at' => $article->created_at->format('M d, Y'),
        ];
    }

    private function formatNewspaper(PublicationNewspaper $newspaper): array
    {
        return [
            'newspaper_id' => $newspaper->newspaper_id,
            'title' => $newspaper->title,
            'slug' => $newspaper->slug,
            'excerpt' => $newspaper->excerpt,
            'body' => $newspaper->body,
            'cover_image' => $newspaper->cover_image ? Storage::disk('public')->url($newspaper->cover_image) : null,
            'issue_number' => $newspaper->issue_number,
            'status' => $newspaper->status,
            'rejection_reason' => $newspaper->rejection_reason,
            'author' => $newspaper->author ? ['user_id' => $newspaper->author->user_id, 'display_name' => $newspaper->author->display_name] : null,
            'reviewer' => $newspaper->reviewer ? ['user_id' => $newspaper->reviewer->user_id, 'display_name' => $newspaper->reviewer->display_name] : null,
            'published_at' => $newspaper->published_at?->format('M d, Y'),
            'reviewed_at' => $newspaper->reviewed_at?->format('M d, Y'),
            'created_at' => $newspaper->created_at->format('M d, Y'),
        ];
    }

    private function formatGallery(PublicationGallery $gallery): array
    {
        return [
            'gallery_id' => $gallery->gallery_id,
            'title' => $gallery->title,
            'slug' => $gallery->slug,
            'description' => $gallery->description,
            'cover_image' => $gallery->cover_image ? Storage::disk('public')->url($gallery->cover_image) : null,
            'status' => $gallery->status,
            'rejection_reason' => $gallery->rejection_reason,
            'author' => $gallery->author ? ['user_id' => $gallery->author->user_id, 'display_name' => $gallery->author->display_name] : null,
            'reviewer' => $gallery->reviewer ? ['user_id' => $gallery->reviewer->user_id, 'display_name' => $gallery->reviewer->display_name] : null,
            'published_at' => $gallery->published_at?->format('M d, Y'),
            'reviewed_at' => $gallery->reviewed_at?->format('M d, Y'),
            'created_at' => $gallery->created_at->format('M d, Y'),
            'photos' => $gallery->relationLoaded('photos') ? $gallery->photos->map(fn($p) => [
                'photo_id' => $p->photo_id,
                'image_path' => Storage::disk('public')->url($p->image_path),
                'caption' => $p->caption,
                'sort_order' => $p->sort_order,
            ]) : [],
            'photos_count' => $gallery->photos()->count(),
        ];
    }
}
