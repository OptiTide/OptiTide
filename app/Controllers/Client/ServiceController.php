<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Services\Audit\AuditLog;
use App\Services\Mail\Mail;
use App\Services\Notifications\OwnerAlert;
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

            $label = $engagement['label'] ?? 'service';
            $client = Client::find($engagement['client_id']);

            // This was the only client-facing state change with no audit row and no
            // notification at all — recurring revenue walked out and nobody was told.
            AuditLog::record('engagement.cancelled', 'client_service', $id, ['label' => $label]);

            // The owner first: this is the event he'd most want pushed at him, because
            // it's the only one where a phone call the same day can save the account.
            OwnerAlert::send(
                'Cancelled: ' . ($client['business_name'] ?? 'A client') . ' — ' . $label,
                ($client['business_name'] ?? 'A client') . ' just cancelled ' . $label . '.',
                array_filter([
                    'Client'  => $client['business_name'] ?? '—',
                    'Contact' => $client['email'] ?? '—',
                    'Service' => $label,
                    'Was'     => ! empty($engagement['price_cents'])
                        ? (new Money((int) $engagement['price_cents'], $engagement['currency'] ?? config('company.currency', 'AUD')))->format()
                        : null,
                ]),
                $client ? url('admin/clients/' . $client['id']) : null,
                'Open the client'
            );

            // And confirm it to the client in writing — their paper trail against
            // "I cancelled and you kept billing me", and ours too.
            if ($client && ! empty($client['email'])) {
                try {
                    Mail::to($client['email'], $client['business_name'] ?? null)
                        ->subject('Cancelled: ' . $label)
                        ->view('emails.service-cancelled', [
                            'client' => $client,
                            'label'  => $label,
                        ])
                        ->send();
                } catch (\Throwable $e) {
                    // Never let a mail outage block a cancellation — it is already done.
                    error_log('Cancellation confirmation failed: ' . $e->getMessage());
                }
            }

            $this->flash('success', 'Your "' . $label . '" has been cancelled. You won\'t be billed again for it.');
        }

        return $this->redirect(route('portal.services'));
    }
}
