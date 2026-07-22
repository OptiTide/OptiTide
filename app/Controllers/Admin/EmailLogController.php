<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\EmailLog;

/**
 * Admin-only: the email log holds message bodies addressed to every client, so
 * it is a cross-client view of personal correspondence. Staff get the helpdesk;
 * this stays with administrators, matching the audit log.
 */
class EmailLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can view the email log.');

        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        [$rows, $total] = EmailLog::page($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return $this->view('admin.emails.index', [
            'title'    => 'Email Log',
            'rows'     => $rows,
            'counts'   => EmailLog::statusCounts(),
            'f_search' => $filters['search'],
            'f_status' => $filters['status'],
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'    => $total,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can view the email log.');

        $row = EmailLog::find($id);
        if (! $row) {
            $this->abort(404, 'Email not found.');
        }

        return $this->view('admin.emails.show', [
            'title'       => 'Email',
            'row'         => $row,
            'attachments' => json_decode((string) ($row['attachments'] ?? ''), true) ?: [],
        ]);
    }

    /**
     * The stored body, rendered in isolation.
     *
     * Served into a sandboxed iframe rather than inlined into the admin page.
     * The body is HTML we generated, but it interpolates client-supplied values
     * (names, ticket text, form answers), so pasting it into a trusted admin
     * page would make any unescaped value a script running with an admin's
     * session. The sandbox denies scripts and same-origin outright, so it cannot
     * matter either way.
     */
    public function body(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can view the email log.');

        $row = EmailLog::find($id);
        if (! $row) {
            $this->abort(404, 'Email not found.');
        }

        return (new Response((string) ($row['body_html'] ?? '')))
            ->header('Content-Type', 'text/html; charset=utf-8')
            // Belt and braces alongside the iframe sandbox: no scripts, no
            // framing by anyone else, and never indexed.
            ->header('Content-Security-Policy', "default-src 'none'; img-src data: https:; style-src 'unsafe-inline'")
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
