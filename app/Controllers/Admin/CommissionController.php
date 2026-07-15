<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\Commission;
use App\Models\User;
use App\Services\Audit\AuditLog;
use App\Services\Referrals\CommissionService;

class CommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can manage commissions.');

        $commissions = Commission::query()->orderBy('id', 'desc')->get();

        $totals = ['pending' => 0, 'approved' => 0, 'paid' => 0];
        foreach ($commissions as $c) {
            if (isset($totals[$c['status']])) {
                $totals[$c['status']] += (int) $c['amount_cents'];
            }
        }

        return $this->view('admin.commissions.index', [
            'title'          => 'Commissions',
            'commissions'    => $commissions,
            'referrer_names' => array_column(User::all(), 'name', 'id'),
            'client_names'   => array_column(Client::all(), 'business_name', 'id'),
            'totals'         => $totals,
            'currency'       => config('company.currency', 'AUD'),
        ]);
    }

    public function approve(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can action commissions.');
        (new CommissionService())->approve($id);
        AuditLog::record('commission.approved', 'commission', $id);
        Session::flash('success', 'Commission approved.');

        return $this->redirect(route('admin.commissions.index'));
    }

    public function markPaid(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can action commissions.');
        (new CommissionService())->markPaid($id);
        AuditLog::record('commission.paid', 'commission', $id);
        Session::flash('success', 'Commission marked as paid.');

        return $this->redirect(route('admin.commissions.index'));
    }
}
