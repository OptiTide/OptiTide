<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Jobs\SendOverdueReminderJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Daily follow-up engine: flips open invoices to overdue past their due date,
 * and dispatches escalating Claude-drafted reminders at 5, 15, and 30 days
 * overdue. Run by the scheduler; also invokable manually.
 */
class ProcessOverdueInvoices extends Command
{
    protected $signature = 'invoices:process-overdue';

    protected $description = 'Mark overdue invoices and dispatch escalating payment reminders';

    /** Reminder number => days overdue that triggers it. */
    protected const THRESHOLDS = [1 => 5, 2 => 15, 3 => 30];

    public function handle(): int
    {
        $today = today();
        $dispatched = 0;

        // Sent invoices past their due date become overdue.
        Invoice::where('status', InvoiceStatus::Sent)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->update(['status' => InvoiceStatus::Overdue]);

        Invoice::open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->each(function (Invoice $invoice) use (&$dispatched) {
                $days = $invoice->daysOverdue();

                // The highest reminder whose threshold has passed (catches up
                // if a run was missed).
                $due = 0;
                foreach (self::THRESHOLDS as $number => $threshold) {
                    if ($days >= $threshold) {
                        $due = $number;
                    }
                }

                if ($due > 0 && $invoice->reminders_sent < $due) {
                    SendOverdueReminderJob::dispatch($invoice->id, $due);
                    $dispatched++;
                }
            });

        $this->info("Overdue invoices processed. Dispatched {$dispatched} reminder(s).");

        return self::SUCCESS;
    }
}
