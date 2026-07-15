<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Backlink;
use App\Services\Audit\AuditLog;
use App\Services\Seo\BacklinkService;

class BacklinkController extends Controller
{
    public function __construct(protected BacklinkService $service = new BacklinkService())
    {
    }

    public function index(Request $request): Response
    {
        $filter = trim((string) $request->query('status', ''));
        $query = Backlink::query();
        if ($filter !== '' && isset(Backlink::STATUSES[$filter])) {
            $query->where('status', $filter);
        }
        // Prospects first (the to-do list), then by authority.
        $links = $query->orderBy('status')->orderBy('domain_authority', 'desc')->get();

        $company = config('company');
        $addr = array_filter([
            $company['address']['line1'] ?? null,
            $company['address']['locality'] ?? null,
            trim(($company['address']['region'] ?? '') . ' ' . ($company['address']['postcode'] ?? '')),
            $company['address']['country'] ?? null,
        ]);

        return $this->view('admin.backlinks.index', [
            'title'    => 'Backlinks & Citations',
            'links'    => $links,
            'summary'  => $this->service->summary(),
            'filter'   => $filter,
            'nap'      => [
                'name'    => $company['legal_name'] ?? 'OptiTide',
                'email'   => $company['email'] ?? '',
                'phone'   => $company['phone'] ?? '',
                'abn'     => $company['abn'] ?? '',
                'website' => rtrim(config('app.url', ''), '/'),
                'address' => implode(', ', $addr),
            ],
        ]);
    }

    /** Load the curated Australian directory starter list (idempotent). */
    public function seed(Request $request): Response
    {
        $added = $this->service->seedStarter();
        AuditLog::record('backlink.seed_directories', null, null, ['added' => $added]);
        Session::flash('success', $added > 0
            ? "Added {$added} Australian directory prospect(s) to work through."
            : 'The starter directories are already loaded.');

        return $this->redirect(route('admin.backlinks.index'));
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'site_name'   => 'required|max:200',
            'site_url'    => 'nullable|max:500',
            'submit_url'  => 'nullable|max:500',
            'type'        => 'required',
            'anchor_text' => 'nullable|max:200',
            'link_url'    => 'nullable|max:500',
            'notes'       => 'nullable|max:1000',
        ]);

        Backlink::create([
            'site_name'   => $data['site_name'],
            'site_url'    => $data['site_url'] ?: null,
            'submit_url'  => $data['submit_url'] ?: null,
            'type'        => isset(Backlink::TYPES[$data['type']]) ? $data['type'] : 'other',
            'status'      => Backlink::STATUS_PROSPECT,
            'anchor_text' => $data['anchor_text'] ?: null,
            'link_url'    => $data['link_url'] ?: null,
            'notes'       => $data['notes'] ?: null,
        ]);
        Session::flash('success', 'Backlink target added.');

        return $this->redirect(route('admin.backlinks.index'));
    }

    public function update(Request $request, string $id): Response
    {
        $link = Backlink::findOrFail($id);
        $status = (string) $request->input('status', $link['status']);

        $update = [];
        if (isset(Backlink::STATUSES[$status])) {
            $update['status'] = $status;
            if ($status === Backlink::STATUS_LIVE) {
                $update['last_checked'] = today();
            }
        }
        foreach (['anchor_text', 'link_url', 'notes'] as $f) {
            if ($request->input($f) !== null) {
                $update[$f] = trim((string) $request->input($f)) ?: null;
            }
        }
        if ($update) {
            Backlink::updateById($id, $update);
        }
        Session::flash('status', 'Backlink updated.');

        return $this->redirect(route('admin.backlinks.index'));
    }

    public function destroy(Request $request, string $id): Response
    {
        Backlink::findOrFail($id);
        Backlink::deleteById($id);
        Session::flash('status', 'Backlink removed.');

        return $this->redirect(route('admin.backlinks.index'));
    }
}
