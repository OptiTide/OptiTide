<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Order;
use App\Models\User;

class ContractService
{
    /**
     * Create the service agreement for a paid order whose products require
     * one (e.g. the Custom Website tier). Idempotent per order.
     */
    public function createForOrder(Order $order): ?Contract
    {
        if ($order->contracts()->exists()) {
            return null;
        }

        $requiresContract = $order->items()
            ->whereHas('product', fn ($query) => $query->where('requires_contract', true))
            ->exists();

        if (! $requiresContract) {
            return null;
        }

        return Contract::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'title' => "Service Agreement — {$order->order_number}",
            'template_key' => 'service_agreement',
        ]);
    }

    /**
     * Create the hosting retainer agreement when a client starts a hosting
     * subscription. One hosting agreement per client.
     */
    public function createHostingContractFor(User $user): ?Contract
    {
        if ($user->contracts()->where('template_key', 'hosting_agreement')->exists()) {
            return null;
        }

        return Contract::create([
            'user_id' => $user->id,
            'title' => 'Hosting Services Agreement',
            'template_key' => 'hosting_agreement',
        ]);
    }
}
