<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\LandingPage;
use App\Services\Audit\AuditLog;
use App\Support\Catalog;

/** Admin CRUD for keyword landing pages. */
class LandingPageController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.landing.index', [
            'title' => 'Landing Pages',
            'pages' => LandingPage::query()->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.landing.form', [
            'title' => 'New Landing Page',
            'page'  => null,
            'lines' => $this->serviceLines(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);

        if (! LandingPage::slugAvailable($data['slug'])) {
            Session::flash('error', 'That URL is taken, reserved, or not a valid slug (lowercase words separated by hyphens).');

            return $this->back();
        }

        $page = LandingPage::create($data);
        AuditLog::record('landing.created', 'landing_page', $page['id'], ['slug' => $data['slug']]);
        Session::flash('success', 'Landing page created. Publish it when the content is ready.');

        return $this->redirect(route('admin.landing.edit', ['id' => $page['id']]));
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('admin.landing.form', [
            'title' => 'Edit Landing Page',
            'page'  => LandingPage::findOrFail($id),
            'lines' => $this->serviceLines(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $page = LandingPage::findOrFail($id);
        $data = $this->validated($request);

        if (! LandingPage::slugAvailable($data['slug'], $id)) {
            Session::flash('error', 'That URL is taken, reserved, or not a valid slug.');

            return $this->back();
        }

        LandingPage::updateById($id, $data);
        AuditLog::record('landing.updated', 'landing_page', $id, ['slug' => $data['slug']]);
        Session::flash('success', 'Saved.');

        return $this->redirect(route('admin.landing.edit', ['id' => $id]));
    }

    public function destroy(Request $request, string $id): Response
    {
        $page = LandingPage::findOrFail($id);
        LandingPage::deleteById($id);
        AuditLog::record('landing.deleted', 'landing_page', $id, ['slug' => $page['slug']]);
        Session::flash('status', 'Landing page deleted.');

        return $this->redirect(route('admin.landing.index'));
    }

    /** @return array<string,mixed> */
    protected function validated(Request $request): array
    {
        $data = $this->validate($request, [
            'slug'             => 'required|max:180',
            'title'            => 'required|max:180',
            'meta_title'       => 'nullable|max:180',
            'meta_description' => 'nullable|max:320',
            'keyword'          => 'nullable|max:160',
            'location'         => 'nullable|max:120',
            'service_slug'     => 'nullable|max:60',
            'intro'            => 'nullable|max:600',
            'body'             => 'nullable',
            'status'           => 'required|in:draft,published',
        ], ['meta_description' => 'Meta description']);

        // FAQ pairs arrive as parallel arrays from the repeater.
        $faqs = [];
        $questions = (array) $request->input('faq_q', []);
        $answers = (array) $request->input('faq_a', []);
        foreach ($questions as $i => $q) {
            $q = trim((string) $q);
            $a = trim((string) ($answers[$i] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }

        $status = $data['status'];
        $existingPublishedAt = null;
        if ($id = $request->routeParam('id')) {
            $existingPublishedAt = LandingPage::find($id)['published_at'] ?? null;
        }

        return [
            'slug'             => strtolower(trim($data['slug'])),
            'title'            => $data['title'],
            'meta_title'       => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'keyword'          => $data['keyword'] ?: null,
            'location'         => $data['location'] ?: null,
            'service_slug'     => $data['service_slug'] ?: null,
            'intro'            => $data['intro'] ?: null,
            'body'             => (string) $request->input('body', ''),
            'faqs'             => $faqs !== [] ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null,
            'status'           => $status,
            // Stamp the first publish only — re-saving a live page must not reset
            // its date and make it look newly published.
            'published_at'     => $status === LandingPage::STATUS_PUBLISHED
                ? ($existingPublishedAt ?: now())
                : null,
        ];
    }

    /** Service lines a page can be attached to, for real catalogue pricing. */
    protected function serviceLines(): array
    {
        $lines = [];
        foreach (Catalog::grouped() as $group) {
            $slug = $group['line']['slug'] ?? null;
            if ($slug) {
                $lines[$slug] = $group['line']['name'] ?? $slug;
            }
        }

        return $lines;
    }
}
