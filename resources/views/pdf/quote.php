<?php
/** @var array $quote @var array $client @var array $items @var array $company */
$accent = config('app.brand.accent', '#FF6A00');
$fmt = fn (int $cents) => money($cents, $quote['currency'])->format();
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
    .block { margin-top: 22px; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; background: #f8fafc; }
    .block h3 { margin: 0 0 6px; font-size: 12px; }
</style>
</head>
<body>
<div class="head">
    <table class="meta">
        <tr>
            <td>
                <?php
                // Two-tone wordmark, driven by the brand name in Settings. A
                // CamelCase name ("OptiTide") splits at the hump; anything else
                // renders whole rather than being chopped arbitrarily.
                $brand = (string) ($company['brand_name'] ?? '');
                $twoTone = preg_match('/^(.*[a-z])([A-Z].*)$/', $brand, $m)
                    ? e($m[1]) . '<span>' . e($m[2]) . '</span>'
                    : e($brand);
                ?>
                <div class="brand"><?= $twoTone ?></div>
                <div style="margin-top:6px;"><strong><?= e($company['legal_name']) ?></strong></div>
                <?php if ($company['abn']): ?><div class="muted">ABN <?= e($company['abn']) ?></div><?php endif; ?>
                <div><?= e($company['email']) ?><?= $company['phone'] ? ' · ' . e($company['phone']) : '' ?></div>
                <?php if ($company['address']['line1']): ?>
                    <div><?= e(trim(($company['address']['line1'] ?? '') . ', ' . ($company['address']['locality'] ?? '') . ' ' . ($company['address']['region'] ?? '') . ' ' . ($company['address']['postcode'] ?? ''), ', ')) ?></div>
                <?php endif; ?>
            </td>
            <td class="right">
                <h1 class="accent">QUOTE</h1>
                <div style="margin-top:6px;"><strong><?= e($quote['number']) ?></strong></div>
                <div class="muted">Issued</div><div><?= e($quote['issue_date']) ?></div>
                <?php if (! empty($quote['expires_at'])): ?>
                    <div class="muted">Valid until</div><div><?= e($quote['expires_at']) ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<table class="meta" style="margin-bottom:18px;">
    <tr>
        <td>
            <div class="muted">Prepared for</div>
            <div><strong><?= e($client['business_name'] ?? '') ?></strong></div>
            <?php if (! empty($client['abn'])): ?><div class="muted">ABN <?= e($client['abn']) ?></div><?php endif; ?>
            <?php if (! empty($client['email'])): ?><div><?= e($client['email']) ?></div><?php endif; ?>
            <?php $addr = \App\Models\Client::fullAddress($client ?? []); ?>
            <?php if ($addr): ?><div><?= e($addr) ?></div><?php endif; ?>
        </td>
        <td class="right">
            <div class="muted">Total (inc GST)</div>
            <div style="font-size:20px;font-weight:bold;" class="accent"><?= e(\App\Models\Quote::total($quote)->format()) ?></div>
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
                <?php
                // The discount is shown against the full items total, then GST is
                // struck on the discounted amount — so the quote states the price
                // and the GST actually within it.
                $discountCents = (int) ($quote['discount_cents'] ?? 0);
                if ($discountCents > 0):
                    $grossCents = (int) $quote['subtotal_cents'] + (int) $quote['gst_cents'] + $discountCents;
                ?>
                    <tr><td class="muted">Items total</td><td class="right"><?= e($fmt($grossCents)) ?></td></tr>
                    <tr>
                        <td class="muted"><?= e($quote['discount_label'] ?: 'Discount') ?></td>
                        <td class="right">− <?= e($fmt($discountCents)) ?></td>
                    </tr>
                <?php endif; ?>
                <tr><td class="muted">Subtotal (ex GST)</td><td class="right"><?= e($fmt((int) $quote['subtotal_cents'])) ?></td></tr>
                <?php if ((int) $quote['gst_cents'] > 0): ?>
                    <tr><td class="muted">GST (<?= e(\App\Support\Gst::rateLabel()) ?>)</td><td class="right"><?= e($fmt((int) $quote['gst_cents'])) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td>Total (inc GST)</td><td class="right"><?= e($fmt((int) $quote['total_cents'])) ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<?php if (! empty($quote['terms'])): ?>
    <div class="block">
        <h3>Terms</h3>
        <div style="font-size:11px;color:#475569;"><?= nl2br(e($quote['terms'])) ?></div>
    </div>
<?php endif; ?>

<?php if (! empty($quote['notes'])): ?>
    <div style="margin-top:18px;color:#64748b;font-size:11px;"><?= nl2br(e($quote['notes'])) ?></div>
<?php endif; ?>

<div style="margin-top:22px;font-size:11px;color:#475569;">
    To accept this quote, use the link in your email or sign in to your client portal. Accepting raises the invoice for the amount above.
</div>

<?php $footerNote = \App\Models\Setting::get('invoice_footer'); ?>
<?php if ($footerNote): ?>
    <div style="margin-top:24px;padding-top:10px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:10px;"><?= nl2br(e($footerNote)) ?></div>
<?php endif; ?>
</body>
</html>
