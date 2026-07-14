<?php

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ContractService;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Events\WebhookHandled;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductSeeder::class);
    Storage::fake('local');
    config(['sign-pad.disk_name' => 'local']);
});

/** A valid signature image as a data URI, drawn with GD. */
function signatureDataUri(): string
{
    $img = imagecreatetruecolor(600, 200);
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
    imageline($img, 20, 150, 580, 60, imagecolorallocate($img, 0, 0, 0));
    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    imagedestroy($img);

    return 'data:image/png;base64,'.base64_encode($png);
}

function orderWith(string $slug, User $client): Order
{
    $product = Product::where('slug', $slug)->first();

    $order = Order::create([
        'user_id' => $client->id,
        'subtotal' => $product->price->amount,
        'total' => $product->price->amount,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'description' => $product->name,
        'quantity' => 1,
        'unit_price' => $product->price->amount,
        'total' => $product->price->amount,
        'currency' => 'AUD',
    ]);

    return $order;
}

test('a service agreement is issued for a paid order that requires one', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = orderWith('custom-website', $client);

    app(ContractService::class)->createForOrder($order);

    $contract = $order->contracts()->first();
    expect($contract)->not->toBeNull()
        ->and($contract->template_key)->toBe('service_agreement')
        ->and($contract->status)->toBe(ContractStatus::Pending)
        ->and($contract->user_id)->toBe($client->id);
});

test('no agreement is issued for an order that does not require one', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = orderWith('standard-website', $client);

    app(ContractService::class)->createForOrder($order);

    expect($order->contracts()->count())->toBe(0);
});

test('order contract creation is idempotent', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = orderWith('custom-website', $client);

    app(ContractService::class)->createForOrder($order);
    app(ContractService::class)->createForOrder($order);

    expect($order->contracts()->count())->toBe(1);
});

test('a hosting agreement is issued when a subscription is created', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->forceFill(['stripe_id' => 'cus_test_123'])->save();

    event(new WebhookHandled([
        'type' => 'customer.subscription.created',
        'data' => ['object' => ['customer' => 'cus_test_123', 'status' => 'active']],
    ]));

    $contract = $client->contracts()->where('template_key', 'hosting_agreement')->first();
    expect($contract)->not->toBeNull()
        ->and($contract->status)->toBe(ContractStatus::Pending);
});

test('the hosting agreement is only issued once per client', function () {
    $client = User::factory()->create(['role' => 'client']);

    app(ContractService::class)->createHostingContractFor($client);
    app(ContractService::class)->createHostingContractFor($client);

    expect($client->contracts()->where('template_key', 'hosting_agreement')->count())->toBe(1);
});

test('a client can sign their own agreement, producing a signed PDF', function () {
    $client = User::factory()->create(['role' => 'client', 'company_name' => 'Acme Pty Ltd']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    $this->actingAs($client)
        ->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()])
        ->assertRedirect();

    $contract->refresh();
    expect($contract->status)->toBe(ContractStatus::Signed)
        ->and($contract->signed_at)->not->toBeNull()
        ->and($contract->hasBeenSigned())->toBeTrue();

    // A signed PDF was generated and stored.
    $path = $contract->signature->getSignedDocumentPath();
    expect($path)->not->toBeNull();
    Storage::disk('local')->assertExists($path);
    expect(substr(Storage::disk('local')->get($path), 0, 4))->toBe('%PDF');
});

test('a client cannot view another client\'s signing page', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $owner->id, 'title' => 'Service Agreement']);

    $this->actingAs($intruder)->get(route('contracts.sign.show', $contract))->assertForbidden();
});

test('a client cannot sign another client\'s agreement', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $owner->id, 'title' => 'Service Agreement']);

    $this->actingAs($intruder)
        ->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()])
        ->assertForbidden();

    expect($contract->refresh()->status)->toBe(ContractStatus::Pending);
});

test('an already-signed agreement cannot be signed again', function () {
    $client = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    $this->actingAs($client)->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()]);

    $this->actingAs($client)
        ->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()])
        ->assertStatus(409);

    expect($contract->signature()->count())->toBe(1);
});

test('an invalid signature payload is rejected', function () {
    $client = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    $this->actingAs($client)
        ->post(route('contracts.sign', $contract), ['sign' => 'not-a-data-uri'])
        ->assertStatus(422);

    expect($contract->refresh()->status)->toBe(ContractStatus::Pending);
});

test('only the owner or an admin can download a signed agreement', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $intruder = User::factory()->create(['role' => 'client']);
    $va = User::factory()->create(['role' => 'va']);
    $admin = User::factory()->create(['role' => 'admin']);
    $contract = Contract::create(['user_id' => $owner->id, 'title' => 'Service Agreement']);

    $this->actingAs($owner)->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()]);

    $this->actingAs($intruder)->get(route('contracts.download', $contract))->assertForbidden();
    // A VA is staff but must not enumerate every client's legal PDF.
    $this->actingAs($va)->get(route('contracts.download', $contract))->assertForbidden();
    $this->actingAs($owner)->get(route('contracts.download', $contract))->assertOk();
    $this->actingAs($admin)->get(route('contracts.download', $contract))->assertOk();
});

test('the package sign-pad route cannot be used to sign a contract', function () {
    $owner = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $owner->id, 'title' => 'Service Agreement']);

    // The package's shared per-class token, if the route were still live.
    $token = md5(config('app.key').Contract::class);

    $this->actingAs($owner)->post('/creagia/sign-pad', [
        'model' => Contract::class,
        'id' => $contract->id,
        'token' => $token,
        'sign' => signatureDataUri(),
    ])->assertNotFound();

    expect($contract->refresh()->status)->toBe(ContractStatus::Pending)
        ->and($contract->signature()->count())->toBe(0);
});

test('the database rejects a second signature for the same contract', function () {
    $client = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    $contract->signature()->create(['uuid' => 'a', 'from_ips' => [], 'filename' => 'a.png', 'certified' => false]);

    // The unique (model_type, model_id) index closes the double-submit race.
    expect(fn () => $contract->signature()->create(['uuid' => 'b', 'from_ips' => [], 'filename' => 'b.png', 'certified' => false]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

test('the hosting listener ignores unrelated webhook events', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->forceFill(['stripe_id' => 'cus_ignore'])->save();

    event(new WebhookHandled([
        'type' => 'invoice.paid',
        'data' => ['object' => ['customer' => 'cus_ignore', 'status' => 'active']],
    ]));

    expect($client->contracts()->count())->toBe(0);
});

test('the hosting listener ignores incomplete subscriptions', function () {
    $client = User::factory()->create(['role' => 'client']);
    $client->forceFill(['stripe_id' => 'cus_incomplete'])->save();

    event(new WebhookHandled([
        'type' => 'customer.subscription.created',
        'data' => ['object' => ['customer' => 'cus_incomplete', 'status' => 'incomplete']],
    ]));

    expect($client->contracts()->count())->toBe(0);
});

test('downloading before signing returns 404', function () {
    $client = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    $this->actingAs($client)->get(route('contracts.download', $contract))->assertNotFound();
});

test('a PDF-generation failure does not brick the contract and a retry succeeds', function () {
    $client = User::factory()->create(['role' => 'client']);
    $contract = Contract::create(['user_id' => $client->id, 'title' => 'Service Agreement']);

    // Force the document generator to throw partway through.
    $this->app->bind(\Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction::class, fn () => new class extends \Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction
    {
        public function __construct() {}

        public function __invoke($signature, $template, $decodedImage): void
        {
            throw new RuntimeException('PDF backend down');
        }
    });

    try {
        $this->actingAs($client)->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()]);
    } catch (RuntimeException $e) {
        // Expected — the request errors out.
    }

    // The transaction rolled the signature row back, so nothing is dangling.
    $contract->refresh();
    expect($contract->status)->toBe(ContractStatus::Pending)
        ->and($contract->signature()->count())->toBe(0)
        ->and($contract->hasBeenSigned())->toBeFalse();

    // With the generator restored, the client can sign for real.
    $this->app->forgetInstance(\Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction::class);
    unset($this->app[\Creagia\LaravelSignPad\Actions\GenerateSignatureDocumentAction::class]);

    $this->actingAs($client)
        ->post(route('contracts.sign', $contract), ['sign' => signatureDataUri()])
        ->assertRedirect();

    expect($contract->refresh()->status)->toBe(ContractStatus::Signed);
});

test('the order blocks onboarding while an agreement is unsigned', function () {
    $client = User::factory()->create(['role' => 'client']);
    $order = orderWith('custom-website', $client);
    app(ContractService::class)->createForOrder($order);

    expect($order->hasUnsignedContract())->toBeTrue();

    $order->contracts()->first()->markSigned();

    expect($order->fresh()->hasUnsignedContract())->toBeFalse();
});
