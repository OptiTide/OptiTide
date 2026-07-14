<?php

use App\Jobs\GenerateSeoAuditJob;
use App\Mail\SeoAuditReport;
use App\Models\Lead;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\AI\SeoAuditPromptBuilder;
use App\Services\SeoAuditPdf;
use App\Support\SafeUrlFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** A fetcher that resolves any host to a fixed public IP (hermetic tests). */
class PublicResolvingFetcher extends SafeUrlFetcher
{
    protected function resolve(string $host): array
    {
        return ['93.184.216.34'];
    }
}

beforeEach(function () {
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

function runAuditJob(Lead $lead): void
{
    (new GenerateSeoAuditJob($lead->id))->handle(
        app(SafeUrlFetcher::class),
        test()->fake,
        app(SeoAuditPromptBuilder::class),
        app(SeoAuditPdf::class),
    );
}

// ---------------------------------------------------------------------------
// Public form + lead capture
// ---------------------------------------------------------------------------

test('the public audit form renders', function () {
    $this->get(route('seo-audit.show'))
        ->assertOk()
        ->assertSee('Instant SEO Audit');
});

test('submitting the form captures a seo_audit lead and queues the audit', function () {
    Bus::fake([GenerateSeoAuditJob::class]);

    $this->post(route('seo-audit.store'), [
        'website_url' => 'https://acme.example',
        'email' => 'owner@acme.example',
    ])->assertRedirect()->assertSessionHas('success');

    $lead = Lead::first();
    expect($lead)->not->toBeNull()
        ->and($lead->source)->toBe('seo_audit')
        ->and($lead->email)->toBe('owner@acme.example')
        ->and($lead->website_url)->toBe('https://acme.example');

    Bus::assertDispatched(GenerateSeoAuditJob::class, fn ($job) => $job->leadId === $lead->id);
});

test('the honeypot silently drops bot submissions', function () {
    Bus::fake([GenerateSeoAuditJob::class]);

    $this->post(route('seo-audit.store'), [
        'website_url' => 'https://acme.example',
        'email' => 'bot@acme.example',
        'company_website' => 'http://spam.test',
    ])->assertRedirect();

    expect(Lead::count())->toBe(0);
    Bus::assertNotDispatched(GenerateSeoAuditJob::class);
});

test('invalid submissions are rejected', function () {
    $this->post(route('seo-audit.store'), ['website_url' => 'not-a-url', 'email' => 'nope'])
        ->assertSessionHasErrors(['website_url', 'email']);

    $this->post(route('seo-audit.store'), ['website_url' => 'ftp://acme.example', 'email' => 'a@b.co'])
        ->assertSessionHasErrors(['website_url']); // non-http scheme rejected

    expect(Lead::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Generation job
// ---------------------------------------------------------------------------

test('the audit job fetches, audits, stores a PDF, stamps the path and emails the prospect', function () {
    Mail::fake();
    Storage::fake('local');
    $this->app->instance(SafeUrlFetcher::class, new PublicResolvingFetcher);
    Http::fake(['*' => Http::response('<html><head><title>Acme</title></head><body><h1>Hi</h1></body></html>', 200)]);

    $lead = Lead::factory()->seoAudit()->create(['website_url' => 'https://acme.example', 'email' => 'owner@acme.example']);

    runAuditJob($lead);

    $lead->refresh();
    expect($lead->meta['audit']['overall_score'] ?? null)->not->toBeNull()
        ->and($lead->meta['signals']['title'] ?? null)->toBe('Acme')
        ->and($lead->seo_report_path)->not->toBeNull();

    Storage::disk('local')->assertExists($lead->seo_report_path);
    Mail::assertSent(SeoAuditReport::class, fn (SeoAuditReport $m) => $m->hasTo('owner@acme.example') && count($m->attachments()) === 1);
});

test('an SSRF-blocked URL keeps the lead but records the error and never emails', function () {
    Mail::fake();
    // Real fetcher; a loopback literal is rejected before any HTTP call.
    $lead = Lead::factory()->seoAudit()->create(['website_url' => 'http://127.0.0.1/admin']);

    runAuditJob($lead);

    $lead->refresh();
    expect($lead->meta['audit_error']['stage'] ?? null)->toBe('fetch')
        ->and($lead->seo_report_path)->toBeNull();
    Mail::assertNothingSent();
});

test('malformed AI JSON records an error and does not email a broken report', function () {
    Mail::fake();
    $this->app->instance(SafeUrlFetcher::class, new PublicResolvingFetcher);
    Http::fake(['*' => Http::response('<html><title>X</title></html>', 200)]);
    $this->fake->nextResponse = 'not json';

    $lead = Lead::factory()->seoAudit()->create(['website_url' => 'https://acme.example']);

    runAuditJob($lead);

    expect($lead->fresh()->meta['audit_error']['stage'] ?? null)->toBe('audit');
    Mail::assertNothingSent();
});

test('re-running the audit job does not re-send a duplicate report (idempotent)', function () {
    Mail::fake();
    Storage::fake('local');
    $this->app->instance(SafeUrlFetcher::class, new PublicResolvingFetcher);
    Http::fake(['*' => Http::response('<html><title>X</title></html>', 200)]);
    $lead = Lead::factory()->seoAudit()->create(['website_url' => 'https://acme.example']);

    runAuditJob($lead);
    runAuditJob($lead->fresh()); // queue retry

    Mail::assertSent(SeoAuditReport::class, 1);
});

test('a repeat submission for the same email within a day does not re-audit (anti-mailbomb)', function () {
    Bus::fake([GenerateSeoAuditJob::class]);
    $payload = ['website_url' => 'https://acme.example', 'email' => 'victim@acme.example'];

    $this->post(route('seo-audit.store'), $payload)->assertRedirect();
    $this->post(route('seo-audit.store'), $payload)->assertRedirect(); // same address again

    expect(Lead::where('email', 'victim@acme.example')->count())->toBe(1);
    Bus::assertDispatchedTimes(GenerateSeoAuditJob::class, 1);
});

test('staff can download the stored audit report from the admin panel', function () {
    Storage::fake('local');
    $staff = App\Models\User::factory()->create(['role' => 'va']);
    $lead = Lead::factory()->seoAudit()->create();
    $path = "seo_audits/{$lead->id}/report.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 fake');
    $lead->forceFill(['seo_report_path' => $path])->save();

    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Leads\Pages\ListLeads::class)
        ->callTableAction('downloadReport', $lead)
        ->assertFileDownloaded('report.pdf');
});

// ---------------------------------------------------------------------------
// PDF + mailable
// ---------------------------------------------------------------------------

test('the SEO audit PDF renders valid PDF bytes', function () {
    $lead = Lead::factory()->seoAudit()->create([
        'website_url' => 'https://acme.example',
        'meta' => ['audit' => ['overall_score' => 71, 'summary' => 'Good.', 'findings' => [['area' => 'Title', 'severity' => 'good', 'detail' => 'ok', 'recommendation' => 'keep']], 'quick_wins' => ['do x']]],
    ]);

    expect(substr(app(SeoAuditPdf::class)->bytes($lead), 0, 4))->toBe('%PDF');
});

test('the report email renders through the real markdown pipeline with the PDF attached', function () {
    config(['mail.default' => 'array']);
    $lead = Lead::factory()->seoAudit()->create([
        'email' => 'owner@acme.example',
        'website_url' => 'https://acme.example',
        'meta' => ['audit' => ['overall_score' => 71, 'summary' => 'Good.', 'findings' => [], 'quick_wins' => []]],
    ]);

    Mail::to($lead->email)->send(new SeoAuditReport($lead));

    $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $email = $messages[0]->getOriginalMessage();
    expect($email->getSubject())->toContain('acme.example');
    $attachments = $email->getAttachments();
    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->getMediaType().'/'.$attachments[0]->getMediaSubtype())->toBe('application/pdf');
});
