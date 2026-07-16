<?php
$this->extends('layouts.portal');
?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Back to All Services</a>

        <div class="card">
            <div class="card-header"><?= $line ? e($line['name']) : 'Order' ?></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="h4 fw-bold mb-0"><?= e($service['name']) ?></div>
                        <div class="text-muted">
                            <?php if ($recurring): ?>
                                Recurring — billed <?= e(strtolower(\App\Models\Service::INTERVALS[$service['interval']] ?? 'monthly')) ?>
                            <?php else: ?>
                                One-off project
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php if ($sale && $saleAmount->minorUnits > 0): ?>
                            <?php $sale_net = new \App\Support\Money($total->minorUnits - $saleAmount->minorUnits, $total->currency); ?>
                            <div class="h3 fw-bold money mb-0 text-success">
                                <span class="text-muted fs-5 fw-normal text-decoration-line-through me-2"><?= e($total->format()) ?></span>
                                <?= e($sale_net->format()) ?><?php if ($recurring): ?><span class="text-muted fs-6">/<?= e(substr($service['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?>
                            </div>
                            <div class="small text-success fw-semibold"><i class="bi bi-tag-fill"></i> <?= e($sale['name']) ?> — save <?= e($saleAmount->format()) ?></div>
                            <?php if ($recurring): ?>
                                <?php // Renewals bill the full engagement price, so say so plainly. ?>
                                <div class="small text-muted">first <?= e(strtolower(\App\Models\Service::INTERVALS[$service['interval']] ?? 'month')) ?> only, then <?= e($total->format()) ?><?= e(\App\Support\Catalog::suffix($service)) ?></div>
                            <?php endif; ?>
                            <div class="text-muted small">includes GST of <?= e(\App\Support\Gst::component($sale_net)->format()) ?></div>
                        <?php else: ?>
                            <div class="h3 fw-bold money mb-0"><?= e($total->format()) ?><?php if ($recurring): ?><span class="text-muted fs-6">/<?= e(substr($service['interval'] ?? 'mo', 0, 2)) ?></span><?php endif; ?></div>
                            <div class="text-muted small">includes GST of <?= e($gst->format()) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3" style="background:var(--brand-soft)">
                    <div class="fw-semibold mb-2"><i class="bi bi-info-circle"></i> What Happens Next</div>
                    <ol class="mb-0 ps-3 small">
                        <li>We create your order and a tax invoice for <strong><?= e($total->format()) ?></strong><?= $recurring ? ' (your first ' . e(strtolower(\App\Models\Service::INTERVALS[$service['interval']] ?? 'monthly')) . ' payment)' : '' ?>.</li>
                        <li>You pay securely by PayID or Payoneer from the invoice page.</li>
                        <li>Once payment lands, our team gets started<?= $recurring ? ' and your subscription renews automatically each period.' : ' on your project.' ?></li>
                    </ol>
                </div>

                <form method="post" action="<?= route('portal.order.place', ['service' => $service['id']]) ?>">
                    <?= csrf_field() ?>
                    <?php if (count($plans ?? []) > 1): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment option</label>
                            <?php foreach ($plans as $i => $plan): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="plan" id="plan_<?= e($plan['key']) ?>" value="<?= e($plan['key']) ?>" <?= $i === 0 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="plan_<?= e($plan['key']) ?>"><?= e($plan['label']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="discount_code">Discount code <span class="text-muted small fw-normal">(optional)</span></label>
                        <input type="text" name="discount_code" id="discount_code" value="<?= e(old('discount_code')) ?>" class="form-control text-uppercase" style="max-width:16rem" placeholder="Have a code?" maxlength="40" autocomplete="off">
                        <?php if ($sale && $saleAmount->minorUnits > 0): ?>
                            <div class="form-text text-success"><?= e($sale['name']) ?> is already applied — you don't need a code. If yours saves you more, we'll use that instead.</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-brand"><i class="bi bi-bag-check"></i> Confirm Order &amp; Continue to Payment</button>
                        <a href="<?= route('portal.order.index') ?>" class="btn btn-light">Cancel</a>
                    </div>
                    <div class="text-muted small mt-2">By confirming you agree to our <a href="<?= route('legal.terms') ?>" target="_blank">Terms of Service</a>.</div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
