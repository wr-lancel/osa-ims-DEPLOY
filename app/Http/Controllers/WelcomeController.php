<?php

namespace App\Http\Controllers;

use App\Models\PublicationArticle;
use App\Models\PublicationGallery;
use App\Models\PublicationNewspaper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        $latestArticles = PublicationArticle::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($a) => [
                'article_id' => $a->article_id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->excerpt,
                'cover_image' => $a->cover_image ? Storage::disk('public')->url($a->cover_image) : null,
                'author_name' => $a->author?->display_name ?? 'OSA Publication',
                'published_at' => $a->published_at?->format('M d, Y'),
            ]);

        $latestNewspapers = PublicationNewspaper::published()
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($n) => [
                'newspaper_id' => $n->newspaper_id,
                'title' => $n->title,
                'slug' => $n->slug,
                'cover_image' => $n->cover_image ? Storage::disk('public')->url($n->cover_image) : null,
                'issue_number' => $n->issue_number,
                'published_at' => $n->published_at?->format('M d, Y'),
            ]);

        $latestGalleries = PublicationGallery::with('photos')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(fn($g) => [
                'gallery_id' => $g->gallery_id,
                'title' => $g->title,
                'slug' => $g->slug,
                'cover_image' => $g->cover_image ? Storage::disk('public')->url($g->cover_image) : null,
                'photos_count' => $g->photos->count(),
                'published_at' => $g->published_at?->format('M d, Y'),
                'preview_photos' => $g->photos->take(4)->map(fn($p) => Storage::disk('public')->url($p->image_path))->values(),
            ]);

        $totalEnrolled      = DB::table('enrolled_students')->where('enrollment_status', 'enrolled')->count();
        $coursesOffered     = DB::table('courses')->count();
        $totalOrganizations = DB::table('student_org')->count();

        return Inertia::render('Welcome', [
            'canLogin'           => Route::has('login'),
            'canRegister'        => Route::has('register'),
            'latestArticles'     => $latestArticles,
            'latestNewspapers'   => $latestNewspapers,
            'latestGalleries'    => $latestGalleries,
            'totalEnrolled'      => $totalEnrolled,
            'coursesOffered'     => $coursesOffered,
            'totalOrganizations' => $totalOrganizations,
        ]);
    }
}
