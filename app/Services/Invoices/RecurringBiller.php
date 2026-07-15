<?php

namespace App\Services\Invoices;

use App\Core\Database;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\Mail\Mail;

/**
 * Generates invoices for recurring engagements (hosting, retainers) that are
 * due, then advances each engagement's next_invoice_date. Since Payoneer/PayID
 * have no native subscriptions, "recurring" means a fresh invoice each cycle.
 * Idempotent per run-day: an engagement is only picked up while its
 * next_invoice_date is on/before today, and that date is advanced inside the
 * same transaction as the invoice creation.
 */
final class RecurringBiller
{
    public function __construct(protected InvoiceService $invoices = new InvoiceService())
    {
    }

    /** @return array{generated:int,invoices:array<int,string>} */
    public function run(bool $autoSend = false, ?string $asOf = null): array
    {
        $asOf ??= today();
        $due = ClientService::dueForInvoicing($asOf);

        $generated = 0;
        $numbers = [];

        foreach ($due as $engagement) {
            $number = Database::instance()->transaction(function () use ($engagement, $autoSend) {
                $current = $engagement['next_invoice_date'];
                $months = Service::intervalMonths($engagement['interval']);
                $next = $this->addMonths($current ?: today(), $months);

                // Atomic claim: advance the schedule ONLY if it still holds the
                // date we picked this row up on. If a concurrent run (or a double
                // daily fire) already advanced it, claimed === 0 and we skip —
                // at-most-once invoicing per cycle.
                $claimed = ClientService::query()
                    ->where('id', $engagement['id'])
                    ->where('next_invoice_date', $current)
                    ->where('status', ClientService::STATUS_ACTIVE)
                    ->update([
                        'next_invoice_date' => $next,
                        'last_invoiced_at'  => today(),
                        'updated_at'        => now(),
                    ]);

                if ($claimed === 0) {
                    return null;
                }

                $client = Client::find($engagement['client_id']);
                if (! $client) {
                    return null;
                }

                $invoice = $this->invoices->create([
                    'client_id' => $engagement['client_id'],
                    'currency'  => $engagement['currency'] ?? config('company.currency', 'AUD'),
                    'status'    => Invoice::STATUS_DRAFT,
                    'notes'     => 'Recurring charge: ' . $engagement['label'],
                ], [[
                    'description'      => $engagement['label'],
                    'quantity'         => 1,
                    'unit_price_cents' => (int) $engagement['price_cents'],
                    'service_id'       => $engagement['service_id'] ?? null,
                ]]);

                if ($autoSend) {
                    $this->invoices->send($invoice['id']);
                }

                return $invoice['number'];
            });

            if ($number !== null) {
                $generated++;
                $numbers[] = $number;
            }
        }

        return ['generated' => $generated, 'invoices' => $numbers];
    }

    /**
     * Add whole months, clamping the day to the target month's length so a
     * month-end anchor never skips a month (Jan 31 + 1 month → Feb 28/29, not
     * Mar 3 as naive strtotime "+1 month" would produce).
     */
    protected function addMonths(string $date, int $months): string
    {
        $dt = new \DateTime($date);
        $day = (int) $dt->format('d');
        $dt->modify('first day of this month')->modify("+$months months");
        $dt->setDate((int) $dt->format('Y'), (int) $dt->format('m'), min($day, (int) $dt->format('t')));

        return $dt->format('Y-m-d');
    }

    /** Mark past-due sent invoices as overdue. */
    public function markOverdue(?string $asOf = null, bool $notify = true): int
    {
        $asOf ??= today();

        $due = Invoice::query()
            ->where('status', Invoice::STATUS_SENT)
            ->where('due_date', '<', $asOf)
            ->get();

        $count = 0;
        foreach ($due as $invoice) {
            Invoice::updateById($invoice['id'], ['status' => Invoice::STATUS_OVERDUE, 'updated_at' => now()]);
            $count++;

            if (! $notify) {
                continue;
            }

            $client = Client::find($invoice['client_id']);
            if ($client && ! empty($client['email'])) {
                Mail::to($client['email'], $client['business_name'])
                    ->subject('Reminder: invoice ' . $invoice['number'] . ' is overdue')
                    ->view('emails.invoice-reminder', [
                        'invoice' => Invoice::find($invoice['id']),
                        'client'  => $client,
                        'payUrl'  => url('pay/' . $invoice['public_token']),
                    ])
                    ->send();
            }
        }

        return $count;
    }
}
