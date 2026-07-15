<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;
use App\Models\Service;
use App\Support\Money;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');
        $engagements = $clientId ? ClientService::forClient($clientId) : [];

        $active = 0;
        $monthly = 0; // recurring spend normalised to a monthly figure
        foreach ($engagements as $engagement) {
            if ($engagement['status'] === 'active') {
                $active++;
                if ($engagement['billing_type'] === 'recurring') {
                    $months = Service::intervalMonths($engagement['interval']);
                    $monthly += (int) round((int) $engagement['price_cents'] / max(1, $months));
                }
            }
        }

        return $this->view('client.services', [
            'title'        => 'My Services',
            'engagements'  => $engagements,
            'active'       => $active,
            'monthly'      => new Money($monthly, $currency),
        ]);
    }

    /** A client cancels one of their own services (stops future billing). */
    public function cancel(Request $request, string $id): Response
    {
        $engagement = ClientService::findOrFail($id);

        // IDOR guard — must belong to the signed-in client.
        if ((string) $engagement['client_id'] !== (string) Auth::clientId()) {
            $this->abort(404, 'Service not found.');
        }

        if ($engagement['status'] === ClientService::STATUS_ACTIVE) {
            ClientService::updateById($id, [
                'status'            => ClientService::STATUS_CANCELLED,
                'next_invoice_date' => null,
            ]);
            $this->flash('success', 'Your "' . ($engagement['label'] ?? 'service') . '" has been cancelled. You won\'t be billed again for it.');
        }

        return $this->redirect(route('portal.services'));
    }
}
