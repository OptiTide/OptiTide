<?php
/**
 * The in-house captcha field — a plain arithmetic challenge held in the session.
 * No third-party service, no API key, no tracking pixel: see App\Support\Captcha.
 *
 * Expects: $captcha (the question string from Captcha::question()).
 * Optional: $captchaId (unique id when a page has more than one form),
 *           $captchaCompact (true to drop the wrapper margin).
 *
 * IMPORTANT: Captcha::question() rewrites the session answer each time it's
 * called, so a page with two forms must call it ONCE in the controller and pass
 * the same string to both — otherwise the second render invalidates the first
 * form's answer.
 */
$captchaId = $captchaId ?? 'captcha_' . bin2hex(random_bytes(3));
?>
<div class="<?= empty($captchaCompact) ? 'mb-3' : '' ?>">
    <label class="form-label" for="<?= e($captchaId) ?>">
        Quick check: <?= e($captcha ?? 'What is 3 + 4?') ?> <span class="text-danger">*</span>
    </label>
    <input id="<?= e($captchaId) ?>" type="text" name="captcha" inputmode="numeric"
           autocomplete="off" style="max-width:180px" required
           class="form-control <?= has_error('captcha') ? 'is-invalid' : '' ?>">
    <?php if (error('captcha')): ?>
        <div class="invalid-feedback d-block"><?= e(error('captcha')) ?></div>
    <?php endif; ?>
    <div class="form-text">A quick sum so we know you're human — no tracking, no third parties.</div>
</div>
