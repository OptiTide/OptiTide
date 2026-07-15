<?php $this->extends('layouts.portal'); ?>
<?php $this->section('content'); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 mb-3" style="background:var(--brand-soft)">
            <div class="card-body">
                <div class="fw-bold h6 mb-1"><i class="bi bi-clipboard-check"></i> <?= e($set['label']) ?></div>
                <div class="text-muted small">A few quick questions so we can hit the ground running on <strong><?= e($engagement['label'] ?? 'your project') ?></strong><?= ! empty($engagement['reference']) ? ' (' . e($engagement['reference']) . ')' : '' ?>. This takes a couple of minutes.</div>
            </div>
        </div>

        <form method="post" action="<?= route('portal.intake.store', ['engagement' => $engagement['id']]) ?>" novalidate>
            <?= csrf_field() ?>
            <?php if (! empty($invoiceId)): ?><input type="hidden" name="invoice" value="<?= (int) $invoiceId ?>"><?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <?php foreach ($set['questions'] as $q): ?>
                        <?php $val = $answers[$q['key']] ?? ''; ?>
                        <div class="mb-3">
                            <label class="form-label"><?= e($q['label']) ?></label>
                            <?php if (($q['type'] ?? 'text') === 'textarea'): ?>
                                <textarea name="q_<?= e($q['key']) ?>" rows="3" class="form-control"><?= e($val) ?></textarea>
                            <?php elseif (($q['type'] ?? '') === 'select'): ?>
                                <select name="q_<?= e($q['key']) ?>" class="form-select">
                                    <option value="">Choose…</option>
                                    <?php foreach (($q['options'] ?? []) as $opt): ?>
                                        <option value="<?= e($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="<?= ($q['type'] ?? 'text') === 'url' ? 'url' : 'text' ?>" name="q_<?= e($q['key']) ?>" value="<?= e($val) ?>" class="form-control">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <?php if (! empty($invoiceId)): ?>
                        <a href="<?= route('portal.invoices.show', ['id' => $invoiceId]) ?>" class="btn btn-link text-muted">Skip for now</a>
                    <?php else: ?><span></span><?php endif; ?>
                    <button class="btn btn-brand"><i class="bi bi-send"></i> Submit Brief<?= ! empty($invoiceId) ? ' &amp; Continue to Payment' : '' ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
