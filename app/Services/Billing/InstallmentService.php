<?php

namespace App\Services\Billing;

/**
 * Resolves payment-plan options and turns a chosen plan into a concrete invoice
 * schedule. Amounts are computed so the installments always sum exactly to the
 * base (the last one absorbs any rounding remainder).
 */
final class InstallmentService
{
    /** @return array<int,array{key:string,label:string,installments:array}> */
    public function plansFor(?string $category): array
    {
        $plans = $category ? (config('installments.' . $category) ?: []) : [];

        if ($plans === []) {
            // Everything supports pay-in-full even without a config entry.
            return [['key' => 'full', 'label' => 'Pay in full', 'installments' => [['pct' => 100, 'due_days' => 0, 'label' => '']]]];
        }

        return $plans;
    }

    /**
     * Whether a chosen plan needs admin approval. Only the first (default) plan
     * for a line is auto — instalment / hardship options (50/50, fortnightly,
     * monthly hosting) are approval-gated.
     */
    public function requiresApproval(?string $category, array $resolvedPlan): bool
    {
        $plans = $this->plansFor($category);

        return count($plans) > 1 && ($plans[0]['key'] ?? null) !== ($resolvedPlan['key'] ?? null);
    }

    /** The chosen plan, or the first (default) if the key is unknown. */
    public function resolvePlan(?string $category, ?string $key): array
    {
        $plans = $this->plansFor($category);
        foreach ($plans as $plan) {
            if ($plan['key'] === $key) {
                return $plan;
            }
        }

        return $plans[0];
    }

    /**
     * Concrete schedule for a service + plan.
     *
     * @return array{months:int,rows:array<int,array{amount_cents:int,due_days:int,label:string}>}
     */
    public function schedule(int $priceCents, array $plan): array
    {
        $months = 1;
        foreach ($plan['installments'] as $ins) {
            if (isset($ins['months'])) {
                $months = max($months, (int) $ins['months']);
            }
        }

        $base = $priceCents * $months;
        $rows = [];
        $allocated = 0;
        $n = count($plan['installments']);

        foreach ($plan['installments'] as $i => $ins) {
            $isLast = $i === $n - 1;
            $amount = $isLast
                ? ($base - $allocated)
                : (int) round($base * ((float) ($ins['pct'] ?? 100) / 100));
            $allocated += $amount;

            $rows[] = [
                'amount_cents' => $amount,
                'due_days'     => (int) ($ins['due_days'] ?? 0),
                'label'        => (string) ($ins['label'] ?? ''),
            ];
        }

        return ['months' => $months, 'rows' => $rows];
    }
}
