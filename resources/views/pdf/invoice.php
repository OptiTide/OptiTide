<?php
/** @var array $invoice @var array $client @var array $items @var array $company @var array $instructions */
$accent = config('app.brand.accent', '#0d9488');
$balance = \App\Models\Invoice::balance($invoice);
$fmt = fn (int $cents) => money($cents, $invoice['currency'])->format();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: Helvetica, Arial, sans-serif; }
    body { color: #1e293b; font-size: 12px; margin: 0; }
    .accent { color: <?= e($accent) ?>; }
    .head { border-bottom: 3px solid <?= e($accent) ?>; padding-bottom: 12px; margin-bottom: 18px; }
    .brand { font-size: 24px; font-weight: bold; color: #0f172a; }
    .brand span { color: <?= e($accent) ?>; }
    h1 { font-size: 18px; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { vertical-align: top; padding: 0; }
    .muted { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
    .items th { text-align: left; border-bottom: 1px solid #cbd5e1; padding: 8px 6px; color: #64748b; font-size: 10px; text-transform: uppercase; }
    .items td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
    .right { text-align: right; }
    .totals td { padding: 5px 6px; }
    .totals .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #cbd5e1; }
    .pay { margin-top: 22px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; background: #f8fafc; }
    .pay h3 { margin: 0 0 6px; font-size: 12px; }
    .pay td { padding: 2px 0; font-size: 11px; }
</style>
</head>
<body>
<div class="head">
    <table class="meta">
        <tr>
            <td>
                <div class="brand">Opti<span>Tide</span></div>
                <div style="margin-top:6px;"><strong><?= e($company['legal_name']) ?></strong></div>
                <?php if ($company['abn']): ?><div class="muted">ABN <?= e($company['abn']) ?></div><?php endif; ?>
                <div><?= e($company['email']) ?><?= $company['phone'] ? ' · ' . e($company['phone']) : '' ?></div>
                <?php if ($company['address']['line1']): ?>
                    <div><?= e(trim(($company['address']['line1'] ?? '') . ', ' . ($company['address']['locality'] ?? '') . ' ' . ($company['address']['region'] ?? '') . ' ' . ($company['address']['postcode'] ?? ''), ', ')) ?></div>
                <?php endif; ?>
            </td>
            <td class="right">
                <h1 class="accent">TAX INVOICE</h1>
                <div style="margin-top:6px;"><strong><?= e($invoice['number']) ?></strong></div>
                <div class="muted">Issued</div><div><?= e($invoice['issue_date']) ?></div>
                <div class="muted">Due</div><div><?= e($invoice['due_date']) ?></div>
            </td>
        </tr>
    </table>
</div>

<table class="meta" style="margin-bottom:18px;">
    <tr>
        <td>
            <div class="muted">Billed to</div>
            <div><strong><?= e($client['business_name'] ?? '') ?></strong></div>
            <?php if (! empty($client['abn'])): ?><div class="muted">ABN <?= e($client['abn']) ?></div><?php endif; ?>
            <?php if (! empty($client['email'])): ?><div><?= e($client['email']) ?></div><?php endif; ?>
            <?php $addr = \App\Models\Client::fullAddress($client ?? []); ?>
            <?php if ($addr): ?><div><?= e($addr) ?></div><?php endif; ?>
        </td>
        <td class="right">
            <div class="muted">Balance due</div>
            <div style="font-size:20px;font-weight:bold;" class="accent"><?= e($balance->format()) ?></div>
        </td>
    </tr>
</table>

<table class="items">
    <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Unit</th><th class="right">Amount</th></tr></thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['description']) ?></td>
                <td class="right"><?= e($item['quantity']) ?></td>
                <td class="right"><?= e($fmt((int) $item['unit_price_cents'])) ?></td>
                <td class="right"><?= e($fmt((int) $item['line_total_cents'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table style="margin-top:10px;">
    <tr>
        <td style="width:55%;"></td>
        <td>
            <table class="totals">
                <tr><td class="muted">Subtotal (ex GST)</td><td class="right"><?= e($fmt((int) $invoice['subtotal_cents'])) ?></td></tr>
                <?php if ((int) $invoice['gst_cents'] > 0): ?>
                    <tr><td class="muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="right"><?= e($fmt((int) $invoice['gst_cents'])) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td>Total (inc GST)</td><td class="right"><?= e($fmt((int) $invoice['total_cents'])) ?></td></tr>
                <?php if ((int) $invoice['amount_paid_cents'] > 0): ?>
                    <tr><td class="muted">Paid</td><td class="right">− <?= e($fmt((int) $invoice['amount_paid_cents'])) ?></td></tr>
                    <tr class="grand"><td>Balance due</td><td class="right"><?= e($balance->format()) ?></td></tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>

<?php foreach ($instructions as $instruction): ?>
    <?php if (! $instruction->available) { continue; } ?>
    <div class="pay">
        <h3><?= e($instruction->label) ?></h3>
        <?php if ($instruction->type === 'bank_transfer'): ?>
            <table>
                <?php foreach ($instruction->details as $label => $value): ?>
                    <tr><td class="muted" style="width:140px;"><?= e($label) ?></td><td><strong><?= e($value) ?></strong></td></tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div>Pay online: <a href="<?= e($instruction->actionUrl) ?>"><?= e($instruction->actionUrl) ?></a></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (! empty($invoice['notes'])): ?>
    <div style="margin-top:18px;color:#64748b;font-size:11px;"><?= nl2br(e($invoice['notes'])) ?></div>
<?php endif; ?>
</body>
</html>
