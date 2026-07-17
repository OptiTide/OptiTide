<?php

namespace App\Services\Notifications;

use App\Services\Mail\Mail;

/**
 * Tell the owner something happened.
 *
 * The convention in this app was inverted: a contact-form enquiry emailed him
 * (ContactController) and a job application emailed him (CareerController), but an
 * actual SALE did not, and neither did a cancellation. The events that move money
 * were the only silent ones — he'd learn about them by opening the dashboard.
 *
 * Never throws. An alert failing to send must not roll back a sale or block a client
 * from cancelling; the record is already written and the dashboard still shows it.
 */
class OwnerAlert
{
    /**
     * @param array<string,string> $rows label => value, rendered as a simple table
     */
    public static function send(string $subject, string $headline, array $rows = [], ?string $url = null, ?string $cta = null): void
    {
        $to = (string) config('company.email');

        if ($to === '') {
            return;
        }

        try {
            Mail::to($to, config('company.legal_name'))
                ->subject($subject)
                ->view('emails.owner-alert', [
                    'headline' => $headline,
                    'rows'     => $rows,
                    'url'      => $url,
                    'cta'      => $cta,
                ])
                ->send();
        } catch (\Throwable $e) {
            error_log('Owner alert failed (' . $subject . '): ' . $e->getMessage());
        }
    }
}
