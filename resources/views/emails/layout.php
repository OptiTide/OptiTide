<?php $accent = config('app.brand.accent', '#FF6A00'); ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f6f8fa;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fa;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:560px;max-width:92%;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <tr><td style="background:<?= e($accent) ?>;padding:18px 28px;">
                <span style="color:#ffffff;font-size:20px;font-weight:800;letter-spacing:-.02em;"><?= e(config('company.brand_name')) ?></span>
            </td></tr>
            <tr><td style="padding:28px;">
                <?= $this->yield('content') ?>
            </td></tr>
            <tr><td style="padding:18px 28px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">
                <?= e(config('company.brand_name')) ?><?= config('company.abn') ? ' · ABN ' . e(config('company.abn')) : '' ?><br>
                <?= e(config('company.email')) ?><br>
                <span style="color:<?= e($accent) ?>;font-weight:700;font-style:italic;">Grow Online. Lead Always.</span>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
