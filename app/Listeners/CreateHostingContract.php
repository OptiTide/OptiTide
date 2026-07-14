<?php

namespace App\Listeners;

use App\Services\ContractService;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * When Stripe reports an active subscription (hosting plans are our only
 * subscriptions), issue the hosting retainer agreement for signature.
 * createHostingContractFor is idempotent, so the created→updated sequence
 * (e.g. SCA that completes later) issues exactly one agreement.
 */
class CreateHostingContract
{
    public function __construct(protected ContractService $contracts) {}

    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! in_array($type, ['customer.subscription.created', 'customer.subscription.updated'], true)) {
            return;
        }

        $subscription = $event->payload['data']['object'] ?? [];

        // Only issue once the subscription is genuinely live — 'incomplete'
        // (first payment pending SCA) or 'canceled' should not trigger it.
        if (! in_array($subscription['status'] ?? null, ['active', 'trialing'], true)) {
            return;
        }

        $stripeCustomerId = $subscription['customer'] ?? null;

        if ($stripeCustomerId === null) {
            return;
        }

        $user = Cashier::findBillable($stripeCustomerId);

        if ($user !== null) {
            $this->contracts->createHostingContractFor($user);
        }
    }
}
