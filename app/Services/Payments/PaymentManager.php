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
            if (! self::switchedOn($key)) {
                continue;
            }

            $gateway = $this->gateway($key);
            if ($gateway && $gateway->isEnabled()) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * The admin's on/off switch for a gateway (Settings > Payment Methods),
     * layered ON TOP of env enablement — env decides what is configured at all,
     * this decides what is offered right now. The settings page writes
     * `payments.on.{key}` as '1'/'0'; missing means on, so a gateway can never
     * vanish because the switch was simply never saved. This filter lives HERE,
     * in the one registry every caller uses (pay page, order copy, emails), so
     * switching a method off removes it everywhere at once.
     */
    public static function switchedOn(string $key): bool
    {
        return (string) config('payments.on.' . $key, '1') !== '0';
    }

    public function gateway(string $key): ?PaymentGateway
    {
        $class = $this->registry[$key] ?? null;

        return $class ? new $class() : null;
    }

    /**
     * Labels of the gateways a client can ACTUALLY pay with right now, for copy
     * that promises payment methods. A gateway listed in config but missing its
     * credentials is not enabled, so naming the methods by hand would advertise
     * a way to pay that the invoice page then can't offer.
     *
     * @return string[]
     */
    public function enabledLabels(): array
    {
        return array_map(fn (PaymentGateway $gateway) => $gateway->label(), $this->enabledGateways());
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
