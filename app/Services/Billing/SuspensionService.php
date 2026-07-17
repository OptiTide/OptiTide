<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Services\Mail\Mail;

/**
 * Auto-suspend clients whose invoices have been overdue past the grace period,
 * and lift the suspension once they've cleared what's owed. Suspending pauses
 * the client's active services (so recurring billing stops) and e-mails them.
 */
final class SuspensionService
{
    public function run(?int $graceDays = null, ?string $asOf = null): array
    {
        $graceDays = $graceDays ?? (int) config('billing.suspend_after_days', 30);
        $asOf = $asOf ?: today();
        $cutoff = date('Y-m-d', strtotime($asOf . ' -' . max(0, $graceDays) . ' days'));

        $overdue = Invoice::query()
            ->where('status', Invoice::STATUS_OVERDUE)
            ->where('due_date', '<', $cutoff)
            ->get();

        $clientIds = array_values(array_unique(array_filter(array_map(
            fn ($i) => $i['client_id'] ?? null,
            $overdue
        ))));

        $suspended = 0;
        foreach ($clientIds as $cid) {
            $client = Client::find($cid);
            if (! $client || $client['status'] !== Client::STATUS_ACTIVE) {
                continue;
            }

            Client::updateById($cid, ['status' => Client::STATUS_SUSPENDED]);

            foreach (ClientService::query()->where('client_id', $cid)->where('status', ClientService::STATUS_ACTIVE)->get() as $cs) {
                ClientService::updateById($cs['id'], ['status' => ClientService::STATUS_PAUSED]);
            }

            if (! empty($client['email'])) {
                try {
                    Mail::to($client['email'], $client['business_name'])
                        ->subject('Your ' . config('company.brand_name') . ' account is on hold')
                        ->view('emails.account-suspended', ['client' => $client])
                        ->send();
                } catch (\Throwable $e) {
                    // never let a mail hiccup abort the run
                }
            }

            $suspended++;
        }

        return ['suspended' => $suspended];
    }

    /**
     * If a suspended client has no remaining unpaid/overdue invoices, reactivate
     * them and resume their paused services. Called after a payment is recorded.
     */
    public function reactivateIfCleared(int|string $clientId): bool
    {
        $client = Client::find($clientId);
        if (! $client || $client['status'] !== Client::STATUS_SUSPENDED) {
            return false;
        }

        // Only an OVERDUE invoice keeps a client suspended. A future, not-yet-due
        // installment (still SENT) must not block reactivation once they've paid
        // what was actually overdue.
        $stillOverdue = Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', Invoice::STATUS_OVERDUE)
            ->exists();
        if ($stillOverdue) {
            return false;
        }

        Client::updateById($clientId, ['status' => Client::STATUS_ACTIVE]);
        foreach (ClientService::query()->where('client_id', $clientId)->where('status', ClientService::STATUS_PAUSED)->get() as $cs) {
            ClientService::updateById($cs['id'], ['status' => ClientService::STATUS_ACTIVE]);
        }

        return true;
    }
}
