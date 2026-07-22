<?php

use App\Core\Blueprint;
use App\Core\Schema;

/**
 * email_logs — a record of every email the system attempts to send.
 *
 * Written by LoggingMailer, a decorator around the real Mailer, so it captures
 * every send without a single call site having to opt in. Anything that goes
 * through Mail::send() is logged; there is no other way out of the app.
 *
 * A row is inserted BEFORE the send and updated with the outcome after. That
 * ordering matters: if the process dies mid-send (a hung SMTP/API call, a
 * worker killed on deploy) the attempt still leaves a 'sending' row. Logging
 * only after a successful return would make exactly the failures you most want
 * to investigate the ones that vanish silently.
 *
 * body_html is stored REDACTED. Emails carry single-use credentials — the
 * password-reset and email-verification links are account-takeover URLs — and a
 * browsable admin log is a much softer target than the mail provider. See
 * EmailLog::redact(). Invoice/quote pay links are left intact: they are
 * capability URLs for data staff can already see, and they are the ones support
 * actually needs to re-send.
 *
 * provider_message_id is the join key for delivery events. Resend returns an id
 * per accepted message; storing it now means a future bounce/delivery webhook
 * can match events back to a row without a schema change. "Accepted by the
 * provider" is NOT "delivered to the human" — this table records the former,
 * and only the webhook can tell you the latter.
 */
return new class {
    public function up(): void
    {
        if (Schema::hasTable('email_logs')) {
            return;
        }

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email', 190);
            $table->string('to_name', 190, true);
            $table->string('from_email', 190, true);
            $table->string('reply_to', 190, true);
            $table->string('subject', 300, true);
            $table->string('mailer', 30, true);              // resend | log
            // sending -> sent | failed. 'sending' left behind means the process
            // died before the driver returned; that is a signal, not corruption.
            $table->string('status', 20, false, 'sending');
            $table->string('provider_message_id', 190, true);
            $table->text('error', true);
            $table->text('body_html', true);                 // redacted
            $table->text('attachments', true);               // JSON: filenames + sizes, never content
            $table->timestamps();
            $table->index('to_email');
            $table->index('status');
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
