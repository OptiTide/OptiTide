<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Mail\OverdueReminder;
use App\Models\Invoice;
use App\Services\AI\ClaudeClient;
use App\Services\AI\ClaudeGenerationException;
use App\Services\AI\ReminderPromptBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the Nth overdue-payment reminder for an invoice, with a Claude-drafted
 * body. Idempotent via a compare-and-swap CLAIM (mirroring
 * StripeWebhookController): the reminder is recorded atomically *before* the
 * email is sent, so a concurrent worker or a daily re-dispatch after a crash
 * can't send the same reminder twice. If the send itself fails the claim is
 * released so a retry re-sends rather than silently dropping the reminder.
 */
class SendOverdueReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId, public int $reminderNumber) {}

    public function handle(ClaudeClient $claude, ReminderPromptBuilder $builder): void
    {
        $invoice = Invoice::with('user')->find($this->invoiceId);

        if ($invoice === null || ! $invoice->isOverdue()) {
            return;
        }

        $previous = $invoice->reminders_sent;

        // Atomically claim this reminder. Only the update that flips
        // reminders_sent from "< N" to N wins; every other racer sees 0 rows.
        $claimed = Invoice::where('id', $invoice->id)
            ->where('reminders_sent', '<', $this->reminderNumber)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->update([
                'reminders_sent' => $this->reminderNumber,
                'last_reminded_at' => now(),
            ]);

        if ($claimed === 0) {
            return; // Already sent (or superseded) by another run.
        }

        try {
            $body = $claude->generate($builder->system(), $builder->user($invoice, $this->reminderNumber));
        } catch (ClaudeGenerationException) {
            // A payment reminder is too important to skip on an AI hiccup —
            // fall back to a plain, correct template.
            $body = $this->fallbackBody($invoice);
        }

        try {
            Mail::to($invoice->user->email)->send(new OverdueReminder($invoice, trim($body)));
        } catch (Throwable $e) {
            // The mail never went out — release the claim so a retry re-sends.
            Invoice::where('id', $invoice->id)
                ->where('reminders_sent', $this->reminderNumber)
                ->update(['reminders_sent' => $previous]);

            throw $e;
        }
    }

    protected function fallbackBody(Invoice $invoice): string
    {
        $outstanding = $invoice->total->subtract($invoice->amount_paid)->format();

        return "Hi {$invoice->user->name},\n\n"
            ."This is a reminder that invoice {$invoice->invoice_number} for {$outstanding} "
            ."is now {$invoice->daysOverdue()} days overdue. We'd appreciate payment at your earliest convenience. "
            .'If you have already paid or need to discuss this, please reply to this email.';
    }
}
