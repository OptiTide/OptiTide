<?php

namespace App\Services\Billing;

/**
 * Thrown when a discount's last use is claimed by someone else between the
 * eligibility check and the redemption claim. Rolls the order transaction back
 * so a refused claim can never leave a discounted invoice behind.
 */
final class DiscountExhaustedException extends \RuntimeException
{
}
