<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>
<?php
use App\Models\Discount;
use App\Support\Money;

$ccy = config('company.currency') ?: 'AUD';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted"><?= count($discounts) ?> discount<?= count($discounts) === 1 ? '' : 's' ?></div>
    <a href="<?= route('admin.discounts.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Discount</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Off</th><th>Applies To</th><th>Window</th><th>Used</th><th class="text-end">Given Away</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($discounts as $d): ?>
                    <?php [$statusText, $statusClass] = Discount::statusLabel($d); ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= e($d['name']) ?>
                            <?php if ($d['is_sale']): ?>
                                <div class="text-muted small"><i class="bi bi-megaphone"></i> Automatic sale — shown publicly</div>
                            <?php elseif (! empty($d['client_id'])): ?>
                                <div class="text-muted small"><i class="bi bi-person"></i> One client only</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($d['code']): ?>
                                <code><?= e($d['code']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">no code needed</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold text-nowrap"><?= e(Discount::label($d)) ?></td>
                        <td class="small"><?= e(Discount::SCOPES[$d['scope']] ?? $d['scope']) ?></td>
                        <td class="small text-nowrap">
                            <?php if ($d['starts_at'] || $d['ends_at']): ?>
                                <?= e($d['starts_at'] ? date('j M y', strtotime((string) $d['starts_at'])) : 'now') ?>
                                –
                                <?= e($d['ends_at'] ? date('j M y', strtotime((string) $d['ends_at'])) : 'open') ?>
                            <?php else: ?>
                                <span class="text-muted">always</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= (int) $d['uses'] ?><?= $d['max_uses'] ? ' / ' . (int) $d['max_uses'] : '' ?></td>
                        <td class="text-end small"><?= e((new Money((int) ($given[$d['id']] ?? 0), $ccy))->format()) ?></td>
                        <td><span class="badge <?= e($statusClass) ?>"><?= e($statusText) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= route('admin.discounts.edit', ['id' => $d['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= route('admin.discounts.destroy', ['id' => $d['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this discount? Invoices that already used it keep their discount.')">
                                <?= csrf_field() ?><?= method_field('DELETE') ?>
                                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($discounts === []): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        No discounts yet. <a href="<?= route('admin.discounts.create') ?>">Create one.</a>
                        <div class="small mt-2">Use a <strong>code</strong> for a client to type at checkout, or a <strong>sale</strong> that applies automatically and shows struck-through pricing on your site.</div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">
    <i class="bi bi-info-circle"></i> GST is inclusive, so a discount comes off the total and the GST is recalculated on what's left — you never pay GST on money you didn't take.
</p>
<?php $this->endSection(); ?>
