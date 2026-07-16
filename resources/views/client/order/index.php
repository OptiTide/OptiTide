<?php
$this->extends('layouts.portal');
$companyEmail = config('company.email');
?>
<?php $this->section('content'); ?>

<div class="card border-0 mb-4" style="background:var(--brand-soft)">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <div class="h5 fw-bold mb-1">Order a Service</div>
            <div class="text-muted">Pick a package below and we'll set it up right away. You'll get a tax invoice you can pay by PayID or Payoneer, and we'll get started as soon as it's paid.</div>
        </div>
        <a href="<?= route('portal.services') ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-grid"></i> My Services</a>
    </div>
</div>

<?php if (! $packages): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        Our catalogue is being set up. Please <a href="mailto:<?= e($companyEmail) ?>">contact us</a> for a quote.
    </div></div>
<?php endif; ?>

<?php foreach ($packages as $group): ?>
    <h2 class="h5 fw-bold mb-3"><?= e($group['line']['name']) ?></h2>
    <div class="row g-3 mb-4">
        <?php foreach ($group['plans'] as $plan): ?>
            <?php
                $isCustom = (int) $plan['price_cents'] <= 0;
                $recurring = $plan['billing_type'] === 'recurring';
            ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="fw-bold mb-1"><?= e($plan['name']) ?></div>
                        <div class="mb-3">
                            <?php if ($isCustom): ?>
                                <span class="h5 fw-bold">Custom Quote</span>
                            <?php else: ?>
                                <span class="h4 fw-bold money"><?= e(money((int) $plan['price_cents'], $plan['currency'])->format()) ?></span>
                                <?php if ($recurring): ?><span class="text-muted">/<?= e(substr($plan['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?>
                                <div class="text-muted small">incl. GST · <?= $recurring ? 'billed ' . e(strtolower(\App\Models\Service::INTERVALS[$plan['interval']] ?? 'monthly')) : 'one-off' ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($isCustom): ?>
                            <a href="mailto:<?= e($companyEmail) ?>?subject=<?= rawurlencode('Quote request: ' . $plan['name']) ?>" class="btn btn-sm btn-outline-brand w-100 mt-auto">Request a Quote</a>
                        <?php else: ?>
                            <a href="<?= route('portal.order.show', ['service' => $plan['id']]) ?>" class="btn btn-sm btn-brand w-100 mt-auto">Order Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php $this->endSection(); ?>
