<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Exceptions\ValidationException;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Services\Mail\Mail;
use App\Support\Captcha;
use App\Support\Upload;
use App\Support\UploadException;

/**
 * The public careers pages + application intake.
 *
 * Roles are read from the admin-managed job_openings table — nothing here
 * hard-codes a job, so the page is always whatever is actually being hired for.
 * When there are no open roles the page still works: it invites a general
 * expression of interest rather than pretending roles exist.
 */
class CareerController extends Controller
{
    public function index(Request $request): Response
    {
        $brand = config('company.brand_name');
        $roles = JobOpening::open();

        return $this->view('public.pages.careers', [
            'seoTitle'       => 'Careers at ' . $brand . ' — Join Our Team',
            'seoDescription' => $roles === []
                ? 'We\'re always keen to hear from talented people in web design, SEO, social media and hosting. Send ' . $brand . ' an expression of interest.'
                : 'Open roles at ' . $brand . ', an Australian-owned digital agency. ' . self::roleTeaser($roles),
            'canonical'      => rtrim(config('app.url'), '/') . '/careers',
            'roles'          => $roles,
            'captcha'        => Captcha::question(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $role = JobOpening::liveRole($slug);
        if (! $role) {
            // A filled role is a dead link people still reach from job boards, so
            // send them to the live list instead of a bare 404 wall.
            return $this->view('public.pages.career-gone', [
                'seoTitle'       => 'That role has closed — ' . config('company.brand_name'),
                'seoDescription' => 'This role is no longer open. See what else we\'re hiring for.',
                // Point the stale URL at the live list rather than letting the
                // layout default the canonical to the homepage.
                'canonical'      => rtrim(config('app.url'), '/') . '/careers',
                'roles'          => JobOpening::open(),
            ], 404);
        }

        return $this->view('public.pages.career', [
            'seoTitle'       => $role['title'] . ' — Careers at ' . config('company.brand_name'),
            'seoDescription' => trim((string) ($role['summary'] ?: mb_substr(strip_tags((string) $role['description']), 0, 155))),
            'canonical'      => rtrim(config('app.url'), '/') . '/careers/' . $role['slug'],
            'role'           => $role,
            'jsonLd'         => self::jobPostingSchema($role),
            'captcha'        => Captcha::question(),
        ]);
    }

    /**
     * Handle an application. Same defences as the contact form (honeypot, rate
     * limit, captcha, CSRF) plus a strictly validated CV upload.
     */
    public function apply(Request $request): Response
    {
        $slug = trim((string) $request->input('role', ''));
        $role = $slug !== '' ? JobOpening::liveRole($slug) : null;
        $return = $role ? route('careers.show', ['slug' => $role['slug']]) : route('careers.index');
        $back = fn () => $this->redirect($return . '#apply');

        // Applying for a role that just closed must not silently become a
        // general application — say so.
        if ($slug !== '' && ! $role) {
            Session::flash('error', 'Sorry — that role closed while you were applying. You\'re welcome to send a general application below.');

            return $this->redirect(route('careers.index') . '#apply');
        }

        // Honeypot — a bot filling the hidden field gets a silent OK.
        if ($request->filled('website')) {
            Session::flash('success', 'Thanks — your application has been received.');

            return $back();
        }

        $key = 'careers:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Session::flash('error', 'You\'ve sent a few applications already — please try again later or email us directly.');

            return $back();
        }

        // Unlike the framework's default (which renders a 422 error page), a
        // failed application redirects back with the fields repopulated. People
        // write real paragraphs here — losing them over one typo means we lose
        // the applicant.
        try {
            $data = $this->validate($request, [
                'name'          => 'required|max:160',
                'email'         => 'required|email|max:200',
                'phone'         => 'nullable|max:60',
                'location'      => 'nullable|max:160',
                'linkedin_url'  => 'nullable|url|max:300',
                'portfolio_url' => 'nullable|url|max:300',
                'cover_letter'  => 'required|max:6000',
                'captcha'       => 'required',
            ], [
                'linkedin_url'  => 'LinkedIn link',
                'portfolio_url' => 'Portfolio link',
                'cover_letter'  => 'Your note',
                'captcha'       => 'Quick check',
            ]);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errors);
            $this->rememberInput($request);

            return $back();
        }

        // Verified after field validation so a field error never silently
        // consumes the challenge.
        if (! Captcha::verify($request->input('captcha'))) {
            // Mark the field too, not just a banner — the question is re-rolled
            // on the next render, so the applicant needs to see which box to fix.
            Session::flash('errors', ['captcha' => 'That answer wasn\'t right — here\'s a fresh question.']);
            Session::flash('error', 'The quick-check answer was incorrect — please try again.');
            $this->rememberInput($request);

            return $back();
        }

        // A CV is optional (some people lead with a portfolio), but if one is
        // attached it must pass every check before anything is stored.
        $resume = null;
        if ($file = $request->file('resume')) {
            try {
                $resume = Upload::store($file, 'careers', Upload::RESUME_TYPES);
            } catch (UploadException $e) {
                Session::flash('error', $e->getMessage());
                $this->rememberInput($request);

                return $back();
            }
        }

        RateLimiter::hit($key, 3600);

        try {
            $application = JobApplication::create([
                'job_opening_id' => $role['id'] ?? null,
                'role_title'     => $role['title'] ?? 'General application',
                'name'           => $data['name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?: null,
                'location'       => $data['location'] ?: null,
                'linkedin_url'   => $data['linkedin_url'] ?: null,
                'portfolio_url'  => $data['portfolio_url'] ?: null,
                'cover_letter'   => $data['cover_letter'],
                'resume_path'    => $resume['path'] ?? null,
                'resume_name'    => $resume['name'] ?? null,
                'resume_size'    => $resume['size'] ?? null,
                'status'         => JobApplication::STATUS_NEW,
                'ip'             => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // The row is the only thing that knows where the CV lives. If it
            // never lands, the file is unreachable by the delete path and would
            // sit on disk forever — so take it back out.
            Upload::delete($resume['path'] ?? null);
            logger('Careers application insert failed.', ['error' => $e->getMessage()]);
            Session::flash('error', 'Something went wrong saving your application — please try again, or email us directly.');
            $this->rememberInput($request);

            return $back();
        }

        // The application is already saved, so a mail outage must not lose it or
        // show the applicant an error.
        try {
            Mail::to(config('company.email'), config('company.legal_name'))
                ->replyTo($data['email'])
                ->subject('New application: ' . ($role['title'] ?? 'General') . ' — ' . $data['name'])
                ->view('emails.job-application', [
                    'application' => $application,
                    'role'        => $role,
                    'reviewUrl'   => url('admin/careers/applications/' . $application['id']),
                ])
                ->send();

            Mail::to($data['email'], $data['name'])
                ->subject('We received your application — ' . config('company.brand_name'))
                ->view('emails.job-application-received', [
                    'name' => $data['name'],
                    'role' => $role['title'] ?? null,
                ])
                ->send();
        } catch (\Throwable $e) {
            logger('Careers application mail failed.', ['application' => $application['id'], 'error' => $e->getMessage()]);
        }

        Session::flash('success', 'Thanks ' . $data['name'] . ' — your application is in. We read every one and will be in touch if there\'s a fit.');

        return $back();
    }

    /**
     * Flash the typed fields back so old() can repopulate the form. The captcha
     * answer is deliberately not kept — it's single-use and a fresh question is
     * generated on the next render.
     */
    private function rememberInput(Request $request): void
    {
        $keep = ['name', 'email', 'phone', 'location', 'linkedin_url', 'portfolio_url', 'cover_letter'];
        $old = [];
        foreach ($keep as $field) {
            $old[$field] = (string) $request->input($field, '');
        }
        Session::flash('_old', $old);
    }

    /**
     * schema.org JobPosting — this is what makes a role eligible to appear in
     * Google Jobs, so the required fields must all be present and correctly typed.
     */
    private static function jobPostingSchema(array $role): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $company = config('company');

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'JobPosting',
            'title'       => $role['title'],
            'description' => self::schemaDescription($role),
            'identifier'  => [
                '@type' => 'PropertyValue',
                'name'  => $company['brand_name'],
                'value' => 'OT-JOB-' . $role['id'],
            ],
            'datePosted'   => substr((string) ($role['posted_at'] ?: $role['created_at']), 0, 10),
            'employmentType' => JobOpening::SCHEMA_EMPLOYMENT_TYPES[$role['employment_type']] ?? 'FULL_TIME',
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name'  => $company['legal_name'] ?: $company['brand_name'],
                'sameAs' => $appUrl,
                'logo'  => $appUrl . '/assets/img/logo.png',
            ],
            'directApply' => true,
            'url'         => $appUrl . '/careers/' . $role['slug'],
        ];

        if (! empty($role['closes_at'])) {
            $schema['validThrough'] = substr((string) $role['closes_at'], 0, 10);
        }

        // A remote role must use jobLocationType + applicantLocationRequirements;
        // using a street address for a remote job is a Google Jobs violation.
        if ($role['workplace_type'] === 'remote') {
            $schema['jobLocationType'] = 'TELECOMMUTE';
            $schema['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => 'AU'];
        }

        $addr = array_filter([
            'streetAddress'   => $company['address']['line1'] ?? null,
            'addressLocality' => $company['address']['locality'] ?? null,
            'addressRegion'   => $company['address']['region'] ?? null,
            'postalCode'      => $company['address']['postcode'] ?? null,
        ]);
        if ($role['workplace_type'] !== 'remote' || $addr !== []) {
            $schema['jobLocation'] = [
                '@type'   => 'Place',
                'address' => array_merge(['@type' => 'PostalAddress', 'addressCountry' => 'AU'], $addr),
            ];
        }

        // Only publish a salary we actually chose to show.
        if (! empty($role['salary_visible']) && ($role['salary_min_cents'] !== null || $role['salary_max_cents'] !== null)) {
            $min = $role['salary_min_cents'] !== null ? (int) $role['salary_min_cents'] / 100 : null;
            $max = $role['salary_max_cents'] !== null ? (int) $role['salary_max_cents'] / 100 : null;
            $value = ['@type' => 'QuantitativeValue', 'unitText' => strtoupper((string) $role['salary_period'])];
            if ($min !== null && $max !== null && $min !== $max) {
                $value['minValue'] = $min;
                $value['maxValue'] = $max;
            } else {
                $value['value'] = $min ?? $max;
            }
            $schema['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => $role['salary_currency'] ?: 'AUD',
                'value'    => $value,
            ];
        }

        return $schema;
    }

    /** Google wants the full role description, including the lists. */
    private static function schemaDescription(array $role): string
    {
        $html = '<p>' . e((string) ($role['summary'] ?: '')) . '</p>';
        $html .= '<p>' . nl2br(e((string) ($role['description'] ?: ''))) . '</p>';

        foreach ([
            'What you\'ll do'  => JobOpening::lines($role['responsibilities'] ?? null),
            'What we\'re after' => JobOpening::lines($role['requirements'] ?? null),
        ] as $heading => $items) {
            if ($items === []) {
                continue;
            }
            $html .= '<h3>' . e($heading) . '</h3><ul>';
            foreach ($items as $item) {
                $html .= '<li>' . e($item) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private static function roleTeaser(array $roles): string
    {
        $titles = array_slice(array_column($roles, 'title'), 0, 4);

        return 'Now hiring: ' . implode(', ', $titles) . '.';
    }
}
