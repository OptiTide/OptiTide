<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditEntry;
use App\Models\User;

/** Admin-only: the audit trail can expose sensitive actions across all clients. */
class AuditLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can view the audit log.');

        $action = trim((string) $request->query('action', ''));
        $actor  = trim((string) $request->query('actor', ''));
        $page   = max(1, (int) $request->query('page', 1));

        $query = AuditEntry::query();
        if ($action !== '') {
            $query->where('action', $action);
        }
        if ($actor !== '') {
            $query->where('user_id', $actor);
        }

        $total = (clone $query)->count();
        $rows  = $query->orderBy('id', 'desc')
            ->limit(self::PER_PAGE)
            ->offset(($page - 1) * self::PER_PAGE)
            ->get();

        // Distinct action list for the filter dropdown (recent window).
        $actions = [];
        foreach (AuditEntry::query()->orderBy('id', 'desc')->limit(3000)->get() as $r) {
            $actions[$r['action']] = true;
        }
        $actions = array_keys($actions);
        sort($actions);

        return $this->view('admin.audit.index', [
            'title'     => 'Audit Log',
            'rows'      => $rows,
            'actions'   => $actions,
            'staff'     => User::query()->whereRaw("role IN ('admin','staff')")->orderBy('name')->get(),
            'f_action'  => $action,
            'f_actor'   => $actor,
            'page'      => $page,
            'pages'     => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'     => $total,
        ]);
    }
}
