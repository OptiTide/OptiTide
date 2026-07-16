<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $appUrl = rtrim(config('app.url'), '/');
        $category = trim((string) $request->query('category', ''));

        $posts = Blog::published();
        if ($category !== '') {
            $posts = array_values(array_filter(
                $posts,
                fn ($p) => strcasecmp((string) ($p['category'] ?? ''), $category) === 0
            ));
        }

        $brand = config('company.brand_name');

        return $this->view('public.blog.index', [
            'title'          => 'Blog',
            'seoTitle'       => 'Blog — Web Design, SEO & Marketing Tips for Australian Business | ' . $brand,
            'seoDescription' => 'Practical web design, SEO, social media and small-business marketing advice from ' . $brand . ', an Australian digital agency. Learn how to get found on Google and grow online.',
            'canonical'      => $appUrl . '/blog' . ($category !== '' ? '?category=' . rawurlencode($category) : ''),
            'ogType'         => 'website',
            'posts'          => $posts,
            'categories'     => Blog::categories(),
            'activeCategory' => $category,
            'jsonLd'         => [
                '@context'  => 'https://schema.org',
                '@type'     => 'Blog',
                'name'      => $brand . ' Blog',
                'url'       => $appUrl . '/blog',
                'inLanguage' => 'en-AU',
                'publisher' => ['@type' => 'Organization', 'name' => config('company.legal_name', 'OptiTide'), 'url' => $appUrl],
            ],
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $post = Blog::livePost($slug);
        if (! $post) {
            $this->abort(404, 'Article not found.');
        }

        // Best-effort view counter; never block rendering on it.
        try {
            Blog::updateById($post['id'], ['views' => (int) ($post['views'] ?? 0) + 1]);
        } catch (\Throwable $e) {
            // ignore
        }

        $appUrl = rtrim(config('app.url'), '/');
        $canonical = $appUrl . '/blog/' . $post['slug'];
        $desc = (string) ($post['meta_description'] ?: $post['excerpt'] ?: '');
        $img = $this->imageUrl($post['cover_image'] ?? '', $appUrl);

        $related = array_slice(array_values(array_filter(
            Blog::published(),
            fn ($p) => (string) $p['id'] !== (string) $post['id']
                && strcasecmp((string) ($p['category'] ?? ''), (string) ($post['category'] ?? '')) === 0
        )), 0, 3);
        if ($related === []) {
            $related = array_slice(array_values(array_filter(
                Blog::published(),
                fn ($p) => (string) $p['id'] !== (string) $post['id']
            )), 0, 3);
        }

        $article = array_filter([
            '@type'            => 'BlogPosting',
            'headline'         => $post['title'],
            'description'      => $desc,
            'image'            => $img,
            'datePublished'    => $post['published_at'] ?: null,
            'dateModified'     => $post['updated_at'] ?: ($post['published_at'] ?: null),
            'author'           => ['@type' => 'Organization', 'name' => $post['author'] ?: config('company.brand_name')],
            'publisher'        => ['@type' => 'Organization', 'name' => config('company.legal_name', 'OptiTide'), 'logo' => ['@type' => 'ImageObject', 'url' => $appUrl . '/assets/img/favicon.png']],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'keywords'         => $post['keywords'] ?: null,
            'url'              => $canonical,
            'inLanguage'       => 'en-AU',
        ]);

        return $this->view('public.blog.show', [
            'title'          => $post['title'],
            'seoTitle'       => ($post['meta_title'] ?: $post['title']) . ' | ' . config('company.brand_name'),
            'seoDescription' => $desc,
            'canonical'      => $canonical,
            'ogType'         => 'article',
            'ogImage'        => $img,
            'post'           => $post,
            'related'        => $related,
            'jsonLd'         => [
                '@context' => 'https://schema.org',
                '@graph'   => [
                    $article,
                    ['@type' => 'BreadcrumbList', 'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $appUrl . '/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $appUrl . '/blog'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canonical],
                    ]],
                ],
            ],
        ]);
    }

    /** Absolute URL for a stored cover image (path or full URL), with fallback. */
    protected function imageUrl(string $cover, string $appUrl): string
    {
        $cover = trim($cover);
        if ($cover === '') {
            return $appUrl . '/assets/img/favicon.png';
        }

        return str_starts_with($cover, 'http') ? $cover : $appUrl . '/' . ltrim($cover, '/');
    }
}
