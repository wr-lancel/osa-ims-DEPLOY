<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $articles = PublicationArticle::where('author_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($a) => $this->formatArticle($a));

        $newspapers = PublicationNewspaper::where('author_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($n) => $this->formatNewspaper($n));

        $galleries = PublicationGallery::where('author_id', $user->user_id)
            ->withCount('photos')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($g) => $this->formatGallery($g));

        return Inertia::render('Student/Publications/Index', [
            'articles' => $articles,
            'newspapers' => $newspapers,
            'galleries' => $galleries,
        ]);
    }

    // ─────────────────────────── ARTICLES ───────────────────────────

    public function createArticle(): Response
    {
        return Inertia::render('Student/Publications/CreateArticle');
    }

    public function storeArticle(StorePublicationArticleRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/articles/covers');
            $coverPath = $request->file('cover_image')->store('publications/articles/covers', 'public');
        }

        $status = isset($data['status']) && $data['status'] === 'draft' ? 'draft' : 'pending_review';

        $article = PublicationArticle::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_articles', 'slug'),
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'cover_image' => $coverPath,
            'status' => $status,
            'author_id' => $user->user_id,
        ]);

        return redirect()->route('student.publications.articles.show', $article->slug)
            ->with('success', 'Article submitted for review.');
    }

    public function showArticle(PublicationArticle $article): Response
    {
        $this->authorizeOwnership($article->author_id);
        $article->load('reviewer');

        return Inertia::render('Student/Publications/ShowArticle', [
            'article' => $this->formatArticle($article),
        ]);
    }

    public function updateArticle(UpdatePublicationArticleRequest $request, PublicationArticle $article)
    {
        $this->authorizeOwnership($article->author_id);
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

        // Resubmit on update unless saving as draft
        $data['status'] = ($data['status'] ?? '') === 'draft' ? 'draft' : 'pending_review';

        $article->update($data);

        return back()->with('success', 'Article updated and resubmitted for review.');
    }

    public function destroyArticle(PublicationArticle $article)
    {
        $this->authorizeOwnership($article->author_id);

        if ($article->cover_image) {
            Storage::disk('public')->delete($article->cover_image);
        }
        $article->delete();

        return redirect()->route('student.publications.index')
            ->with('success', 'Article deleted.');
    }

    // ─────────────────────────── NEWSPAPERS ───────────────────────────

    public function createNewspaper(): Response
    {
        return Inertia::render('Student/Publications/CreateNewspaper');
    }

    public function storeNewspaper(StorePublicationNewspaperRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/newspapers/covers');
            $coverPath = $request->file('cover_image')->store('publications/newspapers/covers', 'public');
        }

        $status = isset($data['status']) && $data['status'] === 'draft' ? 'draft' : 'pending_review';

        $newspaper = PublicationNewspaper::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_newspapers', 'slug'),
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'cover_image' => $coverPath,
            'issue_number' => $data['issue_number'] ?? null,
            'status' => $status,
            'author_id' => $user->user_id,
        ]);

        return redirect()->route('student.publications.newspapers.show', $newspaper->slug)
            ->with('success', 'Newspaper submitted for review.');
    }

    public function showNewspaper(PublicationNewspaper $newspaper): Response
    {
        $this->authorizeOwnership($newspaper->author_id);
        $newspaper->load('reviewer');

        return Inertia::render('Student/Publications/ShowNewspaper', [
            'newspaper' => $this->formatNewspaper($newspaper),
        ]);
    }

    public function updateNewspaper(UpdatePublicationNewspaperRequest $request, PublicationNewspaper $newspaper)
    {
        $this->authorizeOwnership($newspaper->author_id);
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

        $data['status'] = ($data['status'] ?? '') === 'draft' ? 'draft' : 'pending_review';
        $newspaper->update($data);

        return back()->with('success', 'Newspaper updated and resubmitted for review.');
    }

    public function destroyNewspaper(PublicationNewspaper $newspaper)
    {
        $this->authorizeOwnership($newspaper->author_id);

        if ($newspaper->cover_image) {
            Storage::disk('public')->delete($newspaper->cover_image);
        }
        $newspaper->delete();

        return redirect()->route('student.publications.index')
            ->with('success', 'Newspaper deleted.');
    }

    // ─────────────────────────── GALLERIES ───────────────────────────

    public function createGallery(): Response
    {
        return Inertia::render('Student/Publications/CreateGallery');
    }

    public function storeGallery(StorePublicationGalleryRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            Storage::disk('public')->makeDirectory('publications/galleries/covers');
            $coverPath = $request->file('cover_image')->store('publications/galleries/covers', 'public');
        }

        $status = isset($data['status']) && $data['status'] === 'draft' ? 'draft' : 'pending_review';

        $gallery = PublicationGallery::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], 'publication_galleries', 'slug'),
            'description' => $data['description'] ?? null,
            'cover_image' => $coverPath,
            'status' => $status,
            'author_id' => $user->user_id,
        ]);

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

        return redirect()->route('student.publications.galleries.show', $gallery->slug)
            ->with('success', 'Gallery submitted for review.');
    }

    public function showGallery(PublicationGallery $gallery): Response
    {
        $this->authorizeOwnership($gallery->author_id);
        $gallery->load('reviewer', 'photos');

        return Inertia::render('Student/Publications/ShowGallery', [
            'gallery' => $this->formatGallery($gallery),
        ]);
    }

    public function updateGallery(UpdatePublicationGalleryRequest $request, PublicationGallery $gallery)
    {
        $this->authorizeOwnership($gallery->author_id);
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

        $data['status'] = ($data['status'] ?? '') === 'draft' ? 'draft' : 'pending_review';
        $gallery->update($data);

        return back()->with('success', 'Gallery updated and resubmitted for review.');
    }

    public function destroyGallery(PublicationGallery $gallery)
    {
        $this->authorizeOwnership($gallery->author_id);

        foreach ($gallery->photos as $photo) {
            Storage::disk('public')->delete($photo->image_path);
        }
        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }
        $gallery->delete();

        return redirect()->route('student.publications.index')
            ->with('success', 'Gallery deleted.');
    }

    public function uploadPhotos(Request $request, PublicationGallery $gallery)
    {
        $this->authorizeOwnership($gallery->author_id);
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);

        $maxOrder = $gallery->photos()->max('sort_order') ?? -1;

        Storage::disk('public')->makeDirectory("publications/galleries/{$gallery->gallery_id}");
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
        $this->authorizeOwnership($gallery->author_id);
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }

    // ─────────────────────────── HELPERS ───────────────────────────

    private function authorizeOwnership(int $authorId): void
    {
        if (Auth::id() !== $authorId) {
            abort(403);
        }
    }

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
            'published_at' => $article->published_at?->format('M d, Y'),
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
            'published_at' => $newspaper->published_at?->format('M d, Y'),
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
            'published_at' => $gallery->published_at?->format('M d, Y'),
            'created_at' => $gallery->created_at->format('M d, Y'),
            'photos' => $gallery->relationLoaded('photos') ? $gallery->photos->map(fn($p) => [
                'photo_id' => $p->photo_id,
                'image_path' => Storage::disk('public')->url($p->image_path),
                'caption' => $p->caption,
                'sort_order' => $p->sort_order,
            ]) : [],
            'photos_count' => $gallery->photos_count ?? $gallery->photos()->count(),
        ];
    }
}
