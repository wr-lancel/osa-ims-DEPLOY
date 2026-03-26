<?php

namespace App\Http\Controllers;

use App\Models\PublicationArticle;
use App\Models\PublicationGallery;
use App\Models\PublicationNewspaper;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPublicationController extends Controller
{
    public function index(): Response
    {
        $articles = PublicationArticle::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        $newspapers = PublicationNewspaper::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(6);

        $galleries = PublicationGallery::with(['author', 'photos'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(8);

        return Inertia::render('Publications/Index', [
            'articles' => $articles->through(fn($a) => $this->formatArticle($a)),
            'newspapers' => $newspapers->through(fn($n) => $this->formatNewspaper($n)),
            'galleries' => $galleries->through(fn($g) => $this->formatGallery($g)),
        ]);
    }

    public function showArticle(PublicationArticle $article): Response
    {
        if ($article->status !== 'published') {
            abort(404);
        }

        $article->load('author');

        return Inertia::render('Publications/Article', [
            'article' => $this->formatArticle($article),
        ]);
    }

    public function showNewspaper(PublicationNewspaper $newspaper): Response
    {
        if ($newspaper->status !== 'published') {
            abort(404);
        }

        $newspaper->load('author');

        return Inertia::render('Publications/Newspaper', [
            'newspaper' => $this->formatNewspaper($newspaper),
        ]);
    }

    public function showGallery(PublicationGallery $gallery): Response
    {
        if ($gallery->status !== 'published') {
            abort(404);
        }

        $gallery->load('author', 'photos');

        return Inertia::render('Publications/Gallery', [
            'gallery' => $this->formatGallery($gallery),
        ]);
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
            'author_name' => $article->author?->display_name ?? 'OSA Publication',
            'published_at' => $article->published_at?->format('M d, Y'),
            'published_at_raw' => $article->published_at?->toISOString(),
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
            'author_name' => $newspaper->author?->display_name ?? 'OSA Publication',
            'published_at' => $newspaper->published_at?->format('M d, Y'),
            'published_at_raw' => $newspaper->published_at?->toISOString(),
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
            'author_name' => $gallery->author?->display_name ?? 'OSA Publication',
            'published_at' => $gallery->published_at?->format('M d, Y'),
            'published_at_raw' => $gallery->published_at?->toISOString(),
            'photos' => $gallery->relationLoaded('photos') ? $gallery->photos->map(fn($p) => [
                'photo_id' => $p->photo_id,
                'image_path' => Storage::disk('public')->url($p->image_path),
                'caption' => $p->caption,
            ]) : [],
            'photos_count' => $gallery->relationLoaded('photos') ? $gallery->photos->count() : 0,
        ];
    }
}
