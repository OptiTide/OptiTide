<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlogController extends Controller
{
    /** Public blog index — published articles only, newest first. */
    public function index(): View
    {
        $posts = Blog::published()
            ->with('author')
            ->orderByDesc('published_at')
            ->paginate(9);

        return view('blog.index', ['posts' => $posts]);
    }

    /** A single published article. Drafts/scheduled 404 (never leak unpublished). */
    public function show(Blog $blog): View
    {
        abort_unless($blog->isPublished(), 404);

        return view('blog.show', ['blog' => $blog]);
    }

    /** XML sitemap of the storefront + published articles. */
    public function sitemap(): Response
    {
        $blogs = Blog::published()->orderByDesc('published_at')->get();

        return response()
            ->view('blog.sitemap', ['blogs' => $blogs])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /client\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
