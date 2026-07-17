<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\InstallmentRequest;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\Audit\AuditLog;
use App\Services\Billing\InstallmentService;
use App\Services\Invoices\InvoiceService;

/**
 * Admin approval of client-requested instalment / hardship payment plans. Until
 * approved no invoices exist; approving issues the split invoices, declining
 * issues a single pay-in-full invoice instead.
 */
class InstallmentRequestController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.installments.index', [
            'title'        => 'Payment Plans',
            'requests'     => InstallmentRequest::pending(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
            'service_names' => array_column(Service::all(), 'name', 'id'),
        ]);
    }

    public function approve(Request $request, string $id): Response
    {
        $req = InstallmentRequest::findOrFail($id);

        // CLAIM before issuing. The old check-then-act (read pending, issue invoices,
        // then mark approved) let a double-click — or two admins at once — both pass
        // the check and both issue the FULL invoice set, double-billing the client.
        // The compare-and-swap lets exactly one caller win; the loser issues nothing.
        $claimed = \App\Core\Database::instance()->affecting(
            'UPDATE installment_requests SET status = ? WHERE id = ? AND status = ?',
            [InstallmentRequest::STATUS_APPROVED, $id, InstallmentRequest::STATUS_PENDING]
        );
        if ($claimed === 0) {
            Session::flash('error', 'That request has already been actioned.');

            return $this->redirectRoute('admin.installments.index');
        }

        $installments = new InstallmentService();
        $plan = $installments->resolvePlan($req['category'], $req['plan_key']);
        $schedule = $installments->schedule((int) $req['price_cents'], $plan);
        $svc = $req['service_id'] ? Service::find($req['service_id']) : null;
        $recurring = $svc && $svc['billing_type'] === Service::BILLING_RECURRING;
        $yearly = $schedule['months'] >= 12;

        $invoices = new InvoiceService();
        $count = count($schedule['rows']);
        foreach ($schedule['rows'] as $i => $row) {
            $desc = $svc['name'] ?? 'Service';
            if ($yearly) {
                $desc .= ' — 12 months';
            } elseif ($recurring) {
                $desc .= ' — first period';
            }
            if ($row['label'] !== '') {
                $desc .= ' (' . $row['label'] . ')';
            }
            if ($count > 1) {
                $desc .= ' [' . ($i + 1) . ' of ' . $count . ']';
            }

            $invoices->create([
                'client_id'  => $req['client_id'],
                'status'     => Invoice::STATUS_SENT,
                'issue_date' => today(),
                'due_date'   => date('Y-m-d', strtotime('+' . $row['due_days'] . ' days')),
                'notes'      => 'Approved payment plan.',
            ], [
                ['description' => $desc, 'quantity' => 1, 'unit_price_cents' => $row['amount_cents'], 'service_id' => $req['service_id']],
            ]);
        }

        // Status already flipped to approved by the CAS claim above.
        AuditLog::record('installment.approved', 'installment_request', $id, ['invoices' => $count]);
        Session::flash('success', 'Payment plan approved — ' . $count . ' invoice(s) issued.');

        return $this->redirectRoute('admin.installments.index');
    }

    public function decline(Request $request, string $id): Response
    {
        $req = InstallmentRequest::findOrFail($id);

        // Same claim-before-act as approve(): a double-click would otherwise issue two
        // pay-in-full invoices for one declined request.
        $claimed = \App\Core\Database::instance()->affecting(
            'UPDATE installment_requests SET status = ? WHERE id = ? AND status = ?',
            [InstallmentRequest::STATUS_DECLINED, $id, InstallmentRequest::STATUS_PENDING]
        );
        if ($claimed === 0) {
            Session::flash('error', 'That request has already been actioned.');

            return $this->redirectRoute('admin.installments.index');
        }

        $svc = $req['service_id'] ? Service::find($req['service_id']) : null;
        (new InvoiceService())->create([
            'client_id'  => $req['client_id'],
            'status'     => Invoice::STATUS_SENT,
            'issue_date' => today(),
            'notes'      => 'Payment plan not approved — payable in full.',
        ], [
            ['description' => ($svc['name'] ?? 'Service') . ' (pay in full)', 'quantity' => 1, 'unit_price_cents' => (int) $req['price_cents'], 'service_id' => $req['service_id']],
        ]);

        // Status already flipped to declined by the CAS claim above.
        AuditLog::record('installment.declined', 'installment_request', $id);
        Session::flash('success', 'Plan declined — a pay-in-full invoice was issued.');

        return $this->redirectRoute('admin.installments.index');
    }
}
