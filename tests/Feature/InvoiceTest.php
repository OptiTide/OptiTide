<?php

use App\Enums\InvoiceStatus;
use App\Jobs\SendOverdueReminderJob;
use App\Mail\InvoiceIssued;
use App\Mail\OverdueReminder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

function invoiceWithItems(array $state = [], int $itemCents = 250_000): Invoice
{
    $invoice = Invoice::factory()->create($state);
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Custom Website',
        'quantity' => 1,
        'unit_price' => $itemCents,
        'total' => $itemCents,
    ]);

    return $invoice->fresh();
}

// ---------------------------------------------------------------------------
// PDF download authorisation
// ---------------------------------------------------------------------------

test('an owner can download the PDF of their sent invoice', function () {
    $client = User::factory()->create(['role' => 'client']);
    $invoice = Invoice::factory()->sent()->create(['user_id' => $client->id]);
    InvoiceItem::factory()->for($invoice)->create(['description' => 'Custom Website', 'quantity' => 1, 'unit_price' => 250_000, 'total' => 250_000]);

    $response = $this->actingAs($client)->get(route('invoices.download', $invoice));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('a client cannot download another client\'s invoice', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);
    $invoice = Invoice::factory()->sent()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)->get(route('invoices.download', $invoice))->assertForbidden();
});

test('a client cannot download a draft invoice', function () {
    $client = User::factory()->create(['role' => 'client']);
    $invoice = Invoice::factory()->create(['user_id' => $client->id, 'status' => InvoiceStatus::Draft]);

    $this->actingAs($client)->get(route('invoices.download', $invoice))->assertForbidden();
});

test('staff can download any invoice including drafts', function () {
    $client = User::factory()->create(['role' => 'client']);
    $staff = User::factory()->create(['role' => 'admin']);
    $invoice = invoiceWithItems(['user_id' => $client->id, 'status' => InvoiceStatus::Draft]);

    $this->actingAs($staff)->get(route('invoices.download', $invoice))->assertOk();
});

test('a guest cannot download an invoice', function () {
    $invoice = Invoice::factory()->sent()->create();

    // Unauthenticated: the auth middleware redirects away rather than serving the PDF.
    $this->get(route('invoices.download', $invoice))->assertRedirect();
});

// ---------------------------------------------------------------------------
// Totals + send flow
// ---------------------------------------------------------------------------

test('recomputeTotals sums GST-inclusive line items and backs out the GST component', function () {
    $invoice = Invoice::factory()->create(['subtotal' => 0, 'tax' => 0, 'total' => 0]);
    InvoiceItem::factory()->for($invoice)->create(['quantity' => 2, 'unit_price' => 30_000, 'total' => 60_000]);
    InvoiceItem::factory()->for($invoice)->create(['quantity' => 1, 'unit_price' => 15_000, 'total' => 15_000]);

    app(InvoiceService::class)->recomputeTotals($invoice);

    // Total = line sum (what the client pays); GST = total/11 (component).
    expect($invoice->fresh()->total->amount)->toBe(75_000)
        ->and($invoice->fresh()->tax->amount)->toBe(6_818)      // round(75,000 × 1000/11000)
        ->and($invoice->fresh()->subtotal->amount)->toBe(68_182); // ex-GST
});

test('recomputeTotals charges no GST when the supplier is not GST-registered', function () {
    config()->set('company.gst_registered', false);
    $invoice = Invoice::factory()->create(['subtotal' => 0, 'tax' => 0, 'total' => 0]);
    InvoiceItem::factory()->for($invoice)->create(['quantity' => 1, 'unit_price' => 40_000, 'total' => 40_000]);

    app(InvoiceService::class)->recomputeTotals($invoice);

    expect($invoice->fresh()->tax->amount)->toBe(0)
        ->and($invoice->fresh()->subtotal->amount)->toBe(40_000)
        ->and($invoice->fresh()->total->amount)->toBe(40_000);
});

test('the invoice PDF renders the supplier ABN and the GST-inclusive statement', function () {
    config()->set('company.abn', '12 345 678 901');
    config()->set('company.legal_name', 'OptiTide Pty Ltd');

    $invoice = invoiceWithItems(['status' => InvoiceStatus::Sent]);
    app(InvoiceService::class)->recomputeTotals($invoice);

    $html = view('invoices.pdf', [
        'invoice' => $invoice->fresh(),
        'money' => fn ($m) => $m->format(),
    ])->render();

    expect($html)->toContain('TAX INVOICE')
        ->toContain('ABN 12 345 678 901')
        ->toContain('OptiTide Pty Ltd')
        ->toContain('Includes GST (10%)');
});

test('sending a draft invoice issues it, sets a due date, and mails the client with the PDF', function () {
    Mail::fake();
    $client = User::factory()->create(['role' => 'client', 'email' => 'billing@acme.test']);
    $invoice = invoiceWithItems(['user_id' => $client->id, 'status' => InvoiceStatus::Draft]);

    app(InvoiceService::class)->send($invoice);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->sent_at)->not->toBeNull()
        ->and($invoice->due_date->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($invoice->total->amount)->toBe(250_000); // GST-inclusive line sum

    Mail::assertSent(InvoiceIssued::class, function (InvoiceIssued $mail) use ($client, $invoice) {
        return $mail->hasTo($client->email)
            && $mail->invoice->is($invoice)
            && count($mail->attachments()) === 1;
    });
});

test('sending a non-draft invoice is a no-op', function () {
    Mail::fake();
    $invoice = Invoice::factory()->sent()->create();
    $sentAt = $invoice->sent_at;

    app(InvoiceService::class)->send($invoice);

    expect($invoice->fresh()->sent_at->eq($sentAt))->toBeTrue();
    Mail::assertNothingSent();
});

// ---------------------------------------------------------------------------
// Overdue processing command
// ---------------------------------------------------------------------------

test('a sent invoice past its due date is marked overdue', function () {
    Mail::fake();
    $invoice = Invoice::factory()->overdueBy(1)->create();

    $this->artisan('invoices:process-overdue')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

test('no reminder is dispatched before 5 days overdue', function () {
    Mail::fake();
    Invoice::factory()->overdueBy(4)->create();

    $this->artisan('invoices:process-overdue')->assertSuccessful();

    Mail::assertNothingSent();
});

test('the first reminder is dispatched at 5 days overdue', function () {
    Mail::fake();
    $invoice = Invoice::factory()->overdueBy(5)->create();

    $this->artisan('invoices:process-overdue')->assertSuccessful();

    expect($invoice->fresh()->reminders_sent)->toBe(1);
    Mail::assertSent(OverdueReminder::class, 1);
});

test('reminders escalate at 15 and 30 days and each fires once', function () {
    Mail::fake();
    $second = Invoice::factory()->overdueBy(15)->create();
    $third = Invoice::factory()->overdueBy(30)->create();

    $this->artisan('invoices:process-overdue')->assertSuccessful();

    // Catches up to the highest passed threshold in one run.
    expect($second->fresh()->reminders_sent)->toBe(2)
        ->and($third->fresh()->reminders_sent)->toBe(3);
    Mail::assertSent(OverdueReminder::class, 2);
});

test('running the command twice does not re-send the same reminder', function () {
    Mail::fake();
    $invoice = Invoice::factory()->overdueBy(6)->create();

    $this->artisan('invoices:process-overdue')->assertSuccessful();
    $this->artisan('invoices:process-overdue')->assertSuccessful();

    expect($invoice->fresh()->reminders_sent)->toBe(1);
    Mail::assertSent(OverdueReminder::class, 1);
});

test('a later run escalates to the next reminder once the threshold passes', function () {
    Mail::fake();
    // Already received reminder 1; now 15 days overdue.
    $invoice = Invoice::factory()->overdueBy(15)->create(['reminders_sent' => 1]);

    $this->artisan('invoices:process-overdue')->assertSuccessful();

    expect($invoice->fresh()->reminders_sent)->toBe(2);
    Mail::assertSent(OverdueReminder::class, 1);
});

// ---------------------------------------------------------------------------
// Reminder job — Claude body, fallback, idempotency
// ---------------------------------------------------------------------------

test('the reminder job mails a Claude-drafted body and records the reminder', function () {
    Mail::fake();
    $this->fake->nextResponse = 'Custom Claude reminder body.';
    $client = User::factory()->create(['role' => 'client', 'company_name' => 'Acme Pty Ltd']);
    $invoice = Invoice::factory()->overdueBy(7)->create(['user_id' => $client->id]);

    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    $invoice->refresh();
    expect($invoice->reminders_sent)->toBe(1)
        ->and($invoice->last_reminded_at)->not->toBeNull();

    // The prompt carried real invoice context to Claude.
    expect($this->fake->lastSystem)->toContain('payment reminder email')
        ->and($this->fake->lastPrompt)->toContain('Acme Pty Ltd')
        ->and($this->fake->lastPrompt)->toContain($invoice->invoice_number);

    Mail::assertSent(OverdueReminder::class, fn (OverdueReminder $m) => $m->body === 'Custom Claude reminder body.');
});

test('a Claude failure still sends a reminder using the fallback template', function () {
    Mail::fake();
    $this->fake->shouldThrow = true;
    $client = User::factory()->create(['role' => 'client', 'name' => 'Dana Client']);
    $invoice = Invoice::factory()->overdueBy(6)->create(['user_id' => $client->id]);

    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    expect($invoice->fresh()->reminders_sent)->toBe(1);
    Mail::assertSent(OverdueReminder::class, function (OverdueReminder $m) use ($invoice) {
        return str_contains($m->body, $invoice->invoice_number) && str_contains($m->body, 'overdue');
    });
});

test('the reminder job is idempotent when the reminder was already sent', function () {
    Mail::fake();
    $invoice = Invoice::factory()->overdueBy(20)->create(['reminders_sent' => 2, 'last_reminded_at' => now()->subDay()]);

    // A re-queued job for reminder 2 must not re-send.
    (new SendOverdueReminderJob($invoice->id, 2))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    Mail::assertNothingSent();
    expect($invoice->fresh()->reminders_sent)->toBe(2);
});

test('the reminder job no-ops when the invoice is no longer overdue', function () {
    Mail::fake();
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid, 'due_date' => now()->subDays(10)->toDateString()]);

    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    Mail::assertNothingSent();
    expect($invoice->fresh()->reminders_sent)->toBe(0);
});

test('the reminder claims atomically before sending, so a re-dispatch cannot double-send', function () {
    Mail::fake();
    $invoice = Invoice::factory()->overdueBy(6)->create(['reminders_sent' => 0]);
    $builder = app(\App\Services\AI\ReminderPromptBuilder::class);

    // Simulate the daily command dispatching the same reminder twice (e.g. a
    // crash last run left reminders_sent un-recorded, or two workers race).
    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, $builder);
    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, $builder);

    expect($invoice->fresh()->reminders_sent)->toBe(1);
    Mail::assertSent(OverdueReminder::class, 1); // exactly one, not two
});

test('a failed send releases the reminder claim so a retry re-sends', function () {
    // A mailer whose send() throws (transport down).
    $mailer = Mockery::mock(Illuminate\Contracts\Mail\Mailer::class);
    $mailer->shouldReceive('to')->andReturnSelf();
    $mailer->shouldReceive('send')->andThrow(new RuntimeException('mail transport down'));
    Mail::swap($mailer);

    $invoice = Invoice::factory()->overdueBy(6)->create(['reminders_sent' => 0]);

    expect(fn () => (new SendOverdueReminderJob($invoice->id, 1))
        ->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class)))
        ->toThrow(RuntimeException::class);

    // Claim released: reminders_sent is back to 0 so the queue retry re-sends.
    expect($invoice->fresh()->reminders_sent)->toBe(0);
});

test('send() delivers a real message with the generated PDF attached', function () {
    // No Mail::fake() — exercise the full production path: markdown render +
    // the attachment closure actually generating the PDF bytes.
    config(['mail.default' => 'array']);
    $client = User::factory()->create(['role' => 'client', 'email' => 'billing@acme.test']);
    $invoice = invoiceWithItems(['user_id' => $client->id, 'status' => InvoiceStatus::Draft]);

    app(InvoiceService::class)->send($invoice);

    $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    $email = $messages[0]->getOriginalMessage();
    expect($email->getSubject())->toContain($invoice->fresh()->invoice_number);
    $attachments = $email->getAttachments();
    expect($attachments)->toHaveCount(1);
    expect($attachments[0]->getMediaType().'/'.$attachments[0]->getMediaSubtype())->toBe('application/pdf');
});

test('both invoice emails render through the real markdown pipeline', function () {
    // Guards against Content(view:) vs Content(markdown:) regressions — the
    // mail:: components only resolve via the markdown pipeline, and Mail::fake()
    // in other tests skips rendering entirely.
    $invoice = Invoice::factory()->overdueBy(3)->create(['total' => 250_000, 'amount_paid' => 0]);
    InvoiceItem::factory()->for($invoice)->create(['description' => 'Website', 'quantity' => 1, 'unit_price' => 250_000, 'total' => 250_000]);
    $invoice->refresh();

    $reminder = (new OverdueReminder($invoice, 'Please arrange payment.'))->render();
    expect($reminder)->toContain('Please arrange payment.')
        ->toContain($invoice->invoice_number);

    $issued = (new \App\Mail\InvoiceIssued($invoice))->render();
    expect($issued)->toContain($invoice->invoice_number);
});

test('the reminder amount is the outstanding balance, not the gross total', function () {
    Mail::fake();
    // $2,500 invoice, $1,000 already paid, still open and overdue.
    $invoice = Invoice::factory()->overdueBy(7)->create(['total' => 250_000, 'amount_paid' => 100_000]);

    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    // Claude's prompt quotes the $1,500 balance due, never the $2,500 gross total.
    expect($this->fake->lastPrompt)->toContain('1,500.00')
        ->and($this->fake->lastPrompt)->not->toContain('2,500.00');
});

test('the fallback reminder body also uses the outstanding balance', function () {
    Mail::fake();
    $this->fake->shouldThrow = true; // force the template fallback
    $invoice = Invoice::factory()->overdueBy(7)->create(['total' => 250_000, 'amount_paid' => 100_000]);

    (new SendOverdueReminderJob($invoice->id, 1))->handle($this->fake, app(\App\Services\AI\ReminderPromptBuilder::class));

    Mail::assertSent(OverdueReminder::class, function (OverdueReminder $m) {
        return str_contains($m->body, '1,500.00') && ! str_contains($m->body, '2,500.00');
    });
});

test('recomputeTotals restamps line items to the invoice currency', function () {
    $invoice = Invoice::factory()->create(['currency' => 'USD', 'tax' => 0]);
    // A line item mistakenly stored in AUD (the repeater has no currency field).
    InvoiceItem::factory()->for($invoice)->create(['currency' => 'AUD', 'quantity' => 1, 'unit_price' => 10_000, 'total' => 10_000]);

    app(InvoiceService::class)->recomputeTotals($invoice);

    expect($invoice->items()->first()->currency)->toBe('USD');
});

// ---------------------------------------------------------------------------
// Filament UI wiring — the resource pages mount without runtime errors
// (exercises the line-items Repeater, money hydration, table columns/actions)
// ---------------------------------------------------------------------------

test('an admin can render the invoices list, create, and edit pages', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $invoice = invoiceWithItems(['status' => InvoiceStatus::Draft]);

    $this->actingAs($admin)->get(route('filament.admin.resources.invoices.index'))
        ->assertOk()->assertSee($invoice->invoice_number);

    $this->actingAs($admin)->get(route('filament.admin.resources.invoices.create'))->assertOk();

    // Edit hydrates the money fields and the line-items repeater from the DB.
    $this->actingAs($admin)->get(route('filament.admin.resources.invoices.edit', ['record' => $invoice]))
        ->assertOk()->assertSee('Custom Website');
});

test('a client can render their invoices list', function () {
    $client = User::factory()->create(['role' => 'client']);
    Invoice::factory()->sent()->create(['user_id' => $client->id]);

    $this->actingAs($client)->get(route('filament.client.resources.invoices.index'))->assertOk();
});

test('creating an invoice through the admin form stores line totals in cents', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);
    $this->actingAs($admin);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Invoices\Pages\CreateInvoice::class)
        ->fillForm([
            'user_id' => $client->id,
            'status' => 'draft',
            'currency' => 'AUD',
            'amount_paid' => '0',
            'items' => [
                ['description' => 'Design', 'quantity' => 2, 'unit_price' => '100.00'],
                ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => '50.00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::latest('id')->first();
    expect($invoice->items()->count())->toBe(2)
        ->and($invoice->total->amount)->toBe(25_000)      // GST-inclusive line sum (2×10,000 + 1×5,000)
        ->and($invoice->tax->amount)->toBe(2_273)         // round(25,000 × 1000/11000)
        ->and($invoice->subtotal->amount)->toBe(22_727);  // ex-GST

    // Each line's unit price was dehydrated from dollars to cents.
    expect($invoice->items->map(fn ($i) => $i->unit_price->amount)->all())->toContain(10_000, 5_000);
});
