<?php

namespace App\Services\AI;

use App\Models\Invoice;

/**
 * Builds the prompt for a personalised, context-aware overdue payment reminder
 * email body. Tone escalates gently with the reminder number.
 */
class ReminderPromptBuilder
{
    public function system(): string
    {
        return <<<'PROMPT'
        You write short, professional payment reminder emails for OptiTide, an
        Australian digital agency, on behalf of the accounts team.

        <rules>
        - Output ONLY the plain-text email body — no subject line, no markdown,
          no signature block (the template adds "Thanks, The OptiTide Team").
        - Keep it to 3–5 short sentences. Warm and professional, never
          threatening or aggressive.
        - Reference the specific invoice number, amount, and how overdue it is.
        - First reminder: friendly nudge, assume it was an oversight.
          Second: politely firmer. Final: clear but courteous, note that the
          account may be paused if unpaid, and invite them to reply if there's
          an issue.
        - Do not invent payment links, discounts, late fees, or legal threats.
        </rules>
        PROMPT;
    }

    public function user(Invoice $invoice, int $reminderNumber): string
    {
        $stage = match ($reminderNumber) {
            1 => 'first (friendly) reminder',
            2 => 'second (firmer) reminder',
            default => 'final reminder',
        };

        $client = e($invoice->user->company_name ?: $invoice->user->name);
        // The real balance owed, not the gross total — matches the PDF's
        // "Balance due" when a partial payment has been recorded.
        $amount = $invoice->total->subtract($invoice->amount_paid)->format();
        $days = $invoice->daysOverdue();

        return <<<PROMPT
        Write the {$stage} for this overdue invoice.

        Client: {$client}
        Invoice: {$invoice->invoice_number}
        Amount outstanding: {$amount}
        Days overdue: {$days}

        Produce the email body now.
        PROMPT;
    }
}
