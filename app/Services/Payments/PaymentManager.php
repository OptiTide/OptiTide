<?php

namespace App\Services\Payments;

/**
 * Registry + entry point for payment gateways. The billing core talks only to
 * this class, never to a concrete provider.
 */
final class PaymentManager
{
    /** @var array<string,class-string<PaymentGateway>> */
    protected array $registry = [
        'payid'    => PayIdGateway::class,
        'skrill'   => SkrillGateway::class,
        'paypal'   => PayPalGateway::class,
        'payoneer' => PayoneerGateway::class,
    ];

    /** @return PaymentGateway[] in the configured display order */
    public function enabledGateways(): array
    {
        $gateways = [];

        foreach ((array) config('payments.enabled', []) as $key) {
            $gateway = $this->gateway($key);
            if ($gateway && $gateway->isEnabled()) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    public function gateway(string $key): ?PaymentGateway
    {
        $class = $this->registry[$key] ?? null;

        return $class ? new $class() : null;
    }

    /** @return PaymentInstruction[] */
    public function instructionsFor(array $invoice): array
    {
        return array_map(
            fn (PaymentGateway $gateway) => $gateway->instructionFor($invoice),
            $this->enabledGateways()
        );
    }

    /** Valid manual payment methods for the "record payment" form. */
    public function methods(): array
    {
        $methods = ['payid' => 'PayID / Bank transfer', 'skrill' => 'Skrill', 'paypal' => 'PayPal', 'payoneer' => 'Payoneer', 'manual' => 'Manual / Other'];

        return $methods;
    }
}
