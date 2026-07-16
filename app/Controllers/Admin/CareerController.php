<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Services\Audit\AuditLog;
use App\Support\Upload;

/**
 * Admin: post and manage job openings, and review applications.
 *
 * Applications carry personal data and CVs, so every route here is staff-only
 * (enforced by the admin route group) and the CV is streamed rather than served
 * from the webroot.
 */
class CareerController extends Controller
{
    // --- Openings -----------------------------------------------------------

    public function index(Request $request): Response
    {
        $roles = JobOpening::ordered();

        // Application counts per role, so the list shows where the interest is.
        $counts = [];
        foreach (JobApplication::feed() as $app) {
            $key = $app['job_opening_id'] ?? 0;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $this->view('admin.careers.index', [
            'title'     => 'Careers',
            'roles'     => $roles,
            'counts'    => $counts,
            'newCount'  => JobApplication::countNew(),
            'generalCount' => $counts[0] ?? 0,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('admin.careers.form', [
            'title' => 'New Role',
            'role'  => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $role = JobOpening::create($this->roleData($request, null));
        AuditLog::record('job_opening.created', 'job_opening', $role['id'], ['title' => $role['title']]);
        Session::flash('success', 'Role created. It stays hidden until you set it to Open.');

        return $this->redirect(route('admin.careers.edit', ['id' => $role['id']]));
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('admin.careers.form', [
            'title' => 'Edit Role',
            'role'  => JobOpening::findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $existing = JobOpening::findOrFail($id);
        JobOpening::updateById($id, $this->roleData($request, $existing));
        AuditLog::record('job_opening.updated', 'job_opening', $id, ['title' => $existing['title']]);
        Session::flash('success', 'Role saved.');

        return $this->redirect(route('admin.careers.edit', ['id' => $id]));
    }

    public function destroy(Request $request, string $id): Response
    {
        $role = JobOpening::findOrFail($id);
        // The FK is ON DELETE SET NULL, so applications survive with their
        // role_title snapshot intact rather than vanishing with the role.
        JobOpening::deleteById($id);
        AuditLog::record('job_opening.deleted', 'job_opening', $id, ['title' => $role['title']]);
        Session::flash('status', 'Role deleted. Applications for it were kept.');

        return $this->redirect(route('admin.careers.index'));
    }

    // --- Applications -------------------------------------------------------

    public function applications(Request $request): Response
    {
        $jobId = $request->input('role', '');
        $status = (string) $request->input('status', '');

        return $this->view('admin.careers.applications', [
            'title'        => 'Applications',
            'applications' => JobApplication::feed(
                $jobId === '' || $jobId === 'general' ? null : (int) $jobId,
                $status
            ),
            'onlyGeneral'  => $jobId === 'general',
            'roles'        => JobOpening::ordered(),
            'activeRole'   => (string) $jobId,
            'activeStatus' => $status,
        ]);
    }

    public function application(Request $request, string $id): Response
    {
        return $this->view('admin.careers.application', [
            'title'       => 'Application',
            'application' => JobApplication::findOrFail($id),
        ]);
    }

    public function updateApplication(Request $request, string $id): Response
    {
        $application = JobApplication::findOrFail($id);

        $data = $this->validate($request, [
            'status'      => 'required|in:' . implode(',', array_keys(JobApplication::STATUSES)),
            'staff_notes' => 'nullable|max:4000',
        ]);

        JobApplication::updateById($id, [
            'status'      => $data['status'],
            'staff_notes' => $data['staff_notes'] ?: null,
        ]);
        AuditLog::record('job_application.updated', 'job_application', $id, [
            'from' => $application['status'],
            'to'   => $data['status'],
        ]);
        Session::flash('success', 'Application updated.');

        return $this->redirect(route('admin.careers.application', ['id' => $id]));
    }

    /**
     * Stream a CV. The path comes from the DB row (never the request), and
     * Upload::path() re-pins it inside storage/ — so even a tampered row can't
     * read an arbitrary file. Always an attachment + nosniff: a CV is untrusted
     * content and must never render in the browser as HTML.
     */
    public function resume(Request $request, string $id): Response
    {
        $application = JobApplication::findOrFail($id);

        $path = Upload::path($application['resume_path'] ?? null);
        if ($path === null) {
            $this->abort(404, 'That CV is no longer available.');
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            $this->abort(404, 'That CV could not be read.');
        }

        // The subject id already identifies the record — don't copy the
        // applicant's email into the audit log, which outlives the deletion we
        // promise them in the privacy policy.
        AuditLog::record('job_application.resume_downloaded', 'job_application', $id);

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return Response::download(
            $contents,
            $application['resume_name'] ?: ('resume.' . $ext),
            Upload::mimeFor($ext)
        )->header('X-Content-Type-Options', 'nosniff');
    }

    public function destroyApplication(Request $request, string $id): Response
    {
        $application = JobApplication::findOrFail($id);

        // Delete the CV too — leaving someone's personal data on disk after
        // they've been removed from the system is exactly what we shouldn't do.
        Upload::delete($application['resume_path'] ?? null);
        JobApplication::deleteById($id);
        // Deliberately no applicant details in the meta: an erasure record that
        // re-persists the very data it erased isn't an erasure.
        AuditLog::record('job_application.deleted', 'job_application', $id);
        Session::flash('status', 'Application and CV deleted.');

        return $this->redirect(route('admin.careers.applications'));
    }

    // --- Shared -------------------------------------------------------------

    /** @param array<string,mixed>|null $existing */
    private function roleData(Request $request, ?array $existing): array
    {
        $data = $this->validate($request, [
            'title'            => 'required|max:200',
            'department'       => 'nullable|max:120',
            'location'         => 'required|max:160',
            'employment_type'  => 'required|in:' . implode(',', array_keys(JobOpening::EMPLOYMENT_TYPES)),
            'workplace_type'   => 'required|in:' . implode(',', array_keys(JobOpening::WORKPLACE_TYPES)),
            'summary'          => 'nullable|max:400',
            'description'      => 'required|max:8000',
            'responsibilities' => 'nullable|max:4000',
            'requirements'     => 'nullable|max:4000',
            'benefits'         => 'nullable|max:4000',
            'salary_min'       => 'nullable|numeric',
            'salary_max'       => 'nullable|numeric',
            'salary_period'    => 'required|in:' . implode(',', array_keys(JobOpening::SALARY_PERIODS)),
            'status'           => 'required|in:' . implode(',', array_keys(JobOpening::STATUSES)),
            'sort_order'       => 'nullable|integer',
            'closes_at'        => 'nullable|date',
        ]);

        // Dollars in the form, cents in the DB — the house money rule.
        $toCents = fn ($v) => ($v === null || $v === '') ? null : (int) round((float) $v * 100);
        $min = $toCents($data['salary_min'] ?? null);
        $max = $toCents($data['salary_max'] ?? null);
        // Tolerate the range being entered backwards rather than publishing
        // "$120,000 – $90,000".
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $status = $data['status'];

        return [
            'title'            => $data['title'],
            'slug'             => JobOpening::uniqueSlug($data['title'], $existing['id'] ?? null),
            'department'       => $data['department'] ?: null,
            'location'         => $data['location'],
            'employment_type'  => $data['employment_type'],
            'workplace_type'   => $data['workplace_type'],
            'summary'          => $data['summary'] ?: null,
            'description'      => $data['description'],
            'responsibilities' => $data['responsibilities'] ?: null,
            'requirements'     => $data['requirements'] ?: null,
            'benefits'         => $data['benefits'] ?: null,
            'salary_min_cents' => $min,
            'salary_max_cents' => $max,
            'salary_currency'  => config('company.currency') ?: 'AUD',
            'salary_period'    => $data['salary_period'],
            'salary_visible'   => $request->boolean('salary_visible') ? 1 : 0,
            'status'           => $status,
            'sort_order'       => (int) ($data['sort_order'] ?: 0),
            // Stamp posted_at the first time a role actually goes live, so
            // JobPosting datePosted reflects publication, not row creation.
            'posted_at'        => $status === JobOpening::STATUS_OPEN
                ? ($existing['posted_at'] ?? null) ?: now()
                : ($existing['posted_at'] ?? null),
            'closes_at'        => $data['closes_at'] ?: null,
        ];
    }
}
