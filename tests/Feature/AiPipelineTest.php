<?php

use App\Enums\ArtifactStatus;
use App\Enums\ArtifactType;
use App\Enums\OrderState;
use App\Jobs\GenerateMockupJob;
use App\Jobs\PushToGitHubJob;
use App\Services\AI\MockupPromptBuilder;
use App\Models\ClientSubmission;
use App\Models\FormSchema;
use App\Models\GeneratedArtifact;
use App\Models\MockupAnnotation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\AI\PipelineService;
use Database\Seeders\FormSchemaSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductSeeder::class);
    $this->seed(FormSchemaSeeder::class);
    // Deterministic, inspectable Claude client.
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

/** A paid custom-website order sitting at admin_review with a submitted brief. */
function orderAtAdminReview(): Order
{
    $client = User::factory()->create(['role' => 'client', 'company_name' => 'Blue Octopus Cafe']);
    $product = Product::where('slug', 'custom-website')->first();

    $order = Order::create([
        'user_id' => $client->id,
        'payment_status' => 'paid',
        'subtotal' => 250_000,
        'total' => 250_000,
        'placed_at' => now(),
    ]);
    OrderItem::create([
        'order_id' => $order->id, 'product_id' => $product->id, 'description' => $product->name,
        'quantity' => 1, 'unit_price' => 250_000, 'total' => 250_000, 'currency' => 'AUD',
    ]);
    ClientSubmission::create([
        'order_id' => $order->id,
        'form_schema_id' => FormSchema::where('key', 'exhaustive_onboarding')->first()->id,
        'user_id' => $client->id,
        'data' => ['business_name' => 'Blue Octopus Cafe', 'industry' => 'Hospitality'],
        'brand_assets' => ['primary_color' => '#0284c7', 'secondary_color' => '#f59e0b'],
        'submitted_at' => now(),
    ]);
    $order->transitionTo(OrderState::AdminReview);

    return $order->fresh();
}

function pipeline(): PipelineService
{
    return app(PipelineService::class);
}

test('generating a mockup produces an artifact and advances to internal QA', function () {
    $order = orderAtAdminReview();

    pipeline()->generateMockup($order);

    $order->refresh();
    expect($order->state)->toBe(OrderState::MockupQa);

    $artifact = $order->latestArtifact(ArtifactType::MockupHtml);
    expect($artifact)->not->toBeNull()
        ->and($artifact->status)->toBe(ArtifactStatus::Ready)
        ->and($artifact->version)->toBe(1)
        ->and($artifact->content)->toContain('<!DOCTYPE html>');
});

test('the mockup prompt injects brand context and enforces Tailwind v4', function () {
    pipeline()->generateMockup(orderAtAdminReview());

    // Brand data reaches the user prompt as XML constraints.
    expect($this->fake->lastPrompt)->toContain('Blue Octopus Cafe')
        ->and($this->fake->lastPrompt)->toContain('#0284c7')
        ->and($this->fake->lastPrompt)->toContain('<client_context>');

    // Anti-repetition + Tailwind rules are in the system prompt.
    expect($this->fake->lastSystem)->toContain('Tailwind v4')
        ->and($this->fake->lastSystem)->toContain('bespoke')
        ->and($this->fake->lastSystem)->toContain('NEVER use a standard centered hero');
});

test('a generation failure marks the artifact rejected and leaves the order at generating', function () {
    $this->fake->shouldThrow = true;
    $order = orderAtAdminReview();

    pipeline()->generateMockup($order);

    $order->refresh();
    expect($order->state)->toBe(OrderState::GeneratingMockup);

    $artifact = $order->latestArtifact(ArtifactType::MockupHtml);
    expect($artifact->status)->toBe(ArtifactStatus::Rejected)
        ->and($artifact->prompt_context['error'] ?? null)->toContain('Simulated');
});

test('a stuck order can be retried without an illegal transition', function () {
    $this->fake->shouldThrow = true;
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order); // fails, now at generating_mockup

    $this->fake->shouldThrow = false;
    pipeline()->generateMockup($order->fresh()); // retry succeeds

    $order->refresh();
    expect($order->state)->toBe(OrderState::MockupQa)
        ->and($order->artifacts()->where('type', ArtifactType::MockupHtml)->count())->toBe(2);
});

test('approving the mockup marks it approved and opens client review', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);

    pipeline()->approveMockupForClient($order->fresh());

    $order->refresh();
    expect($order->state)->toBe(OrderState::ClientReview)
        ->and($order->latestArtifact(ArtifactType::MockupHtml)->status)->toBe(ArtifactStatus::Approved);
});

test('the client proofing page renders the mockup in a locked-down sandboxed iframe', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $response = $this->actingAs($order->user)->get(route('proofing.show', $order))->assertOk();

    // Sandboxed without allow-same-origin — the untrusted HTML can't touch the app origin.
    $response->assertSee('sandbox="allow-scripts"', false);
    expect($response->getContent())->not->toContain('allow-same-origin');
});

test('a non-owner cannot proof someone else\'s order', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $intruder = User::factory()->create(['role' => 'client']);
    $this->actingAs($intruder)->get(route('proofing.show', $order))->assertForbidden();
    $this->actingAs($intruder)->post(route('proofing.approve', $order))->assertForbidden();
});

test('client approval generates logic and advances to final QA', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $this->actingAs($order->user)->post(route('proofing.approve', $order))
        ->assertRedirect(route('filament.client.resources.orders.index'));

    $order->refresh();
    expect($order->state)->toBe(OrderState::FinalQa)
        ->and($order->latestArtifact(ArtifactType::LogicCode))->not->toBeNull()
        ->and($order->latestArtifact(ArtifactType::LogicCode)->status)->toBe(ArtifactStatus::Ready);
});

test('client requesting changes regenerates the mockup', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $this->actingAs($order->user)->post(route('proofing.changes', $order))->assertRedirect();

    $order->refresh();
    expect($order->state)->toBe(OrderState::MockupQa) // regenerated → back through generating → QA
        ->and($order->artifacts()->where('type', ArtifactType::MockupHtml)->count())->toBe(2);
});

test('a client can drop annotation pins during proofing', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $this->actingAs($order->user)->post(route('proofing.annotate', $order), [
        'x' => 42.5, 'y' => 60.0, 'comment' => 'Make this heading bigger.',
    ])->assertRedirect(route('proofing.show', $order));

    $pin = MockupAnnotation::first();
    expect($pin)->not->toBeNull()
        ->and((float) $pin->x)->toBe(42.5)
        ->and($pin->comment)->toBe('Make this heading bigger.');
});

test('annotation coordinates are validated to the viewport range', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());

    $this->actingAs($order->user)->post(route('proofing.annotate', $order), [
        'x' => 250, 'y' => -5, 'comment' => '',
    ])->assertSessionHasErrors(['x', 'y', 'comment']);
});

test('final approval delivers the order and dispatches the GitHub push', function () {
    // Fake only the GitHub push; the generation jobs must still run (sync).
    Bus::fake([PushToGitHubJob::class]);
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());
    // Bring it to final_qa by generating logic directly.
    pipeline()->generateLogic($order->fresh());

    pipeline()->approveAndDeliver($order->fresh());

    $order->refresh();
    expect($order->state)->toBe(OrderState::Delivered)
        ->and($order->latestArtifact(ArtifactType::LogicCode)->status)->toBe(ArtifactStatus::Approved);

    Bus::assertDispatched(PushToGitHubJob::class);
});

test('the github push job no-ops when unconfigured', function () {
    config(['services.github.token' => null]);
    $order = orderAtAdminReview();
    $order->artifacts()->create(['type' => ArtifactType::MockupHtml, 'status' => ArtifactStatus::Approved, 'content' => '<html></html>', 'version' => 1]);
    $logic = $order->artifacts()->create(['type' => ArtifactType::LogicCode, 'status' => ArtifactStatus::Approved, 'content' => 'console.log(1)', 'version' => 1]);

    (new PushToGitHubJob($order->id))->handle(app(\App\Services\GitHubService::class));

    // No repo URL recorded, no exception thrown.
    expect($logic->fresh()->github_repo_url)->toBeNull();
});

test('non-HTML generation output is rejected instead of stored as a mockup', function () {
    $this->fake->nextResponse = 'I could not determine the brand colours, so here is a summary instead.';
    $order = orderAtAdminReview();

    pipeline()->generateMockup($order);

    $order->refresh();
    expect($order->state)->toBe(OrderState::GeneratingMockup) // did not advance
        ->and($order->latestArtifact(ArtifactType::MockupHtml)->status)->toBe(ArtifactStatus::Rejected);
});

test('a stale generation job does not advance the order when a newer artifact exists', function () {
    $order = orderAtAdminReview();
    $order->transitionTo(OrderState::GeneratingMockup);

    $v1 = $order->artifacts()->create(['type' => ArtifactType::MockupHtml, 'status' => ArtifactStatus::Generating, 'version' => 1]);
    $order->artifacts()->create(['type' => ArtifactType::MockupHtml, 'status' => ArtifactStatus::Generating, 'version' => 2]);

    // The OLD job (v1) finishes late; v2 is now the current artifact.
    (new GenerateMockupJob($v1->id))->handle($this->fake, app(MockupPromptBuilder::class));

    expect($order->fresh()->state)->toBe(OrderState::GeneratingMockup) // NOT advanced
        ->and($v1->fresh()->status)->toBe(ArtifactStatus::Ready);      // content still filled
});

test('the failed hook marks a stuck generating artifact rejected', function () {
    $order = orderAtAdminReview();
    $order->transitionTo(OrderState::GeneratingMockup);
    $art = $order->artifacts()->create(['type' => ArtifactType::MockupHtml, 'status' => ArtifactStatus::Generating, 'version' => 1]);

    (new GenerateMockupJob($art->id))->failed(new RuntimeException('worker ran out of memory'));

    expect($art->fresh()->status)->toBe(ArtifactStatus::Rejected)
        ->and($art->fresh()->prompt_context['error'])->toContain('worker ran out of memory');
});

test('a stuck order exposes a retry generation path', function () {
    $this->fake->shouldThrow = true;
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order); // fails → rejected artifact, stuck at generating_mockup

    // The retry action re-runs generateMockup from the stuck state.
    $this->fake->shouldThrow = false;
    pipeline()->generateMockup($order->fresh());

    expect($order->fresh()->state)->toBe(OrderState::MockupQa);
});

test('the preview route serves the mockup with a strict CSP and is owner/staff only', function () {
    $order = orderAtAdminReview();
    pipeline()->generateMockup($order);
    pipeline()->approveMockupForClient($order->fresh());
    $order->refresh();

    $staff = User::factory()->create(['role' => 'va']);
    $intruder = User::factory()->create(['role' => 'client']);

    $resp = $this->actingAs($order->user)->get(route('proofing.preview', $order))->assertOk();
    expect($resp->headers->get('Content-Security-Policy'))->toContain("connect-src 'none'")
        ->and($resp->headers->get('Content-Security-Policy'))->toContain("default-src 'none'");

    $this->actingAs($staff)->get(route('proofing.preview', $order))->assertOk();
    $this->actingAs($intruder)->get(route('proofing.preview', $order))->assertForbidden();
});

test('the full pipeline walks admin_review to delivered', function () {
    Bus::fake([PushToGitHubJob::class]);
    $order = orderAtAdminReview();

    pipeline()->generateMockup($order);                 // → mockup_qa
    pipeline()->approveMockupForClient($order->fresh()); // → client_review
    $this->actingAs($order->user)->post(route('proofing.approve', $order)); // → final_qa
    pipeline()->approveAndDeliver($order->fresh());      // → delivered

    expect($order->fresh()->state)->toBe(OrderState::Delivered);

    // Every stage was audited.
    $states = $order->stateTransitions()->pluck('to_state')->map(fn ($s) => $s->value)->all();
    expect($states)->toContain('generating_mockup', 'mockup_qa', 'client_review', 'generating_logic', 'final_qa', 'delivered');
});
