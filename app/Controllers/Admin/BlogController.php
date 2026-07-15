<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.blogs.index', [
            'title' => 'Blog',
            'posts' => Blog::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.blogs.form', [
            'title' => 'New Article',
            'post'  => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $post = Blog::create($this->blogData($request, null));
        Session::flash('success', 'Article created.');

        return $this->redirect(route('admin.blogs.edit', ['id' => $post['id']]));
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('admin.blogs.form', [
            'title' => 'Edit Article',
            'post'  => Blog::findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $existing = Blog::findOrFail($id);
        Blog::updateById($id, $this->blogData($request, $existing));
        Session::flash('success', 'Article saved.');

        return $this->redirect(route('admin.blogs.edit', ['id' => $id]));
    }

    public function destroy(Request $request, string $id): Response
    {
        Blog::findOrFail($id);
        Blog::deleteById($id);
        Session::flash('status', 'Article deleted.');

        return $this->redirect(route('admin.blogs.index'));
    }

    protected function blogData(Request $request, ?array $existing): array
    {
        $data = $this->validate($request, [
            'title'            => 'required|max:200',
            'slug'             => 'nullable|max:200',
            'excerpt'          => 'nullable|max:500',
            'body'             => 'required',
            'category'         => 'nullable|max:100',
            'author'           => 'nullable|max:120',
            'keywords'         => 'nullable|max:255',
            'meta_title'       => 'nullable|max:255',
            'meta_description' => 'nullable|max:320',
            'cover_image'      => 'nullable|max:255',
            'status'           => 'required|in:draft,published',
            'published_at'     => 'nullable',
        ]);

        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['title'];
        $slug = Blog::uniqueSlug($slugSource, $existing['id'] ?? null);

        $pd = trim((string) ($data['published_at'] ?? ''));
        $pd = $pd !== '' ? $pd . ' 00:00:00' : '';

        if ($data['status'] === Blog::STATUS_PUBLISHED) {
            $publishedAt = $pd !== '' ? $pd : ($existing['published_at'] ?? null);
            if (! $publishedAt) {
                $publishedAt = now();
            }
        } else {
            // Keep any previously chosen date on a draft; don't invent one.
            $publishedAt = $pd !== '' ? $pd : ($existing['published_at'] ?? null);
        }

        return [
            'title'            => $data['title'],
            'slug'             => $slug,
            'excerpt'          => $data['excerpt'] ?: null,
            'body'             => $data['body'],
            'category'         => $data['category'] ?: null,
            'author'           => $data['author'] ?: 'OptiTide',
            'keywords'         => $data['keywords'] ?: null,
            'meta_title'       => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'cover_image'      => $data['cover_image'] ?: null,
            'status'           => $data['status'],
            'published_at'     => $publishedAt,
        ];
    }
}
