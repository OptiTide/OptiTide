<?php
/**
 * The application form. Shared by /careers (general application) and
 * /careers/{slug} (role-specific), so both stay identical.
 *
 * Expects: $captcha. Optional: $roleSlug — when set, the application is attached
 * to that role; when null it's a general expression of interest.
 *
 * enctype is REQUIRED here — without it the CV silently never arrives.
 *
 * Convention: unmarked fields are required; optional ones say "(optional)". No
 * asterisks — one convention, stated once, below.
 */
$roleSlug = $roleSlug ?? null;

// Wire an input to its error + help text so screen readers announce them
// instead of leaving a red box with no spoken reason.
$describe = function (string $id, bool $hasError, ?string $helpId = null): string {
    $ids = array_filter([$hasError ? $id . '_err' : null, $helpId]);

    return ($hasError ? ' aria-invalid="true"' : '')
        . ($ids ? ' aria-describedby="' . implode(' ', $ids) . '"' : '');
};
?>
<?php if (session('success')): ?>
    <div class="alert alert-success" role="status"><i class="bi bi-check-circle"></i> <?= e(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> <?= e(session('error')) ?></div>
<?php endif; ?>
<?php if (errors() && ! session('error')): ?>
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> Please check the highlighted fields and try again.</div>
<?php endif; ?>

<form method="post" action="<?= route('careers.apply') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php if ($roleSlug): ?>
        <input type="hidden" name="role" value="<?= e($roleSlug) ?>">
    <?php endif; ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <p class="text-muted small">Fields are required unless marked optional.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="a_name">Your name</label>
            <input id="a_name" type="text" name="name" value="<?= e(old('name')) ?>" autocomplete="name" class="form-control <?= has_error('name') ? 'is-invalid' : '' ?>" required<?= $describe('a_name', has_error('name')) ?>>
            <?php if (error('name')): ?><div id="a_name_err" class="invalid-feedback d-block"><?= e(error('name')) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="a_email">Email address</label>
            <input id="a_email" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" required<?= $describe('a_email', has_error('email')) ?>>
            <?php if (error('email')): ?><div id="a_email_err" class="invalid-feedback d-block"><?= e(error('email')) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="a_phone">Phone <span class="text-muted small">(optional)</span></label>
            <input id="a_phone" type="text" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="a_location">Where you're based <span class="text-muted small">(optional)</span></label>
            <input id="a_location" type="text" name="location" value="<?= e(old('location')) ?>" class="form-control" placeholder="e.g. Brisbane, QLD">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="a_linkedin">LinkedIn <span class="text-muted small">(optional)</span></label>
            <input id="a_linkedin" type="url" name="linkedin_url" value="<?= e(old('linkedin_url')) ?>" class="form-control <?= has_error('linkedin_url') ? 'is-invalid' : '' ?>" placeholder="https://linkedin.com/in/…"<?= $describe('a_linkedin', has_error('linkedin_url')) ?>>
            <?php if (error('linkedin_url')): ?><div id="a_linkedin_err" class="invalid-feedback d-block"><?= e(error('linkedin_url')) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="a_portfolio">Portfolio / website <span class="text-muted small">(optional)</span></label>
            <input id="a_portfolio" type="url" name="portfolio_url" value="<?= e(old('portfolio_url')) ?>" class="form-control <?= has_error('portfolio_url') ? 'is-invalid' : '' ?>" placeholder="https://…"<?= $describe('a_portfolio', has_error('portfolio_url')) ?>>
            <?php if (error('portfolio_url')): ?><div id="a_portfolio_err" class="invalid-feedback d-block"><?= e(error('portfolio_url')) ?></div><?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="a_cover">Tell us about yourself</label>
            <textarea id="a_cover" name="cover_letter" rows="6" class="form-control <?= has_error('cover_letter') ? 'is-invalid' : '' ?>" required placeholder="What you do, what you're good at, and why this looks like a fit. A few honest paragraphs beat a template."<?= $describe('a_cover', has_error('cover_letter')) ?>><?= e(old('cover_letter')) ?></textarea>
            <?php if (error('cover_letter')): ?><div id="a_cover_err" class="invalid-feedback d-block"><?= e(error('cover_letter')) ?></div><?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="a_resume">Your CV <span class="text-muted small">(optional)</span></label>
            <input id="a_resume" type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx,.odt,.rtf,.txt" aria-describedby="a_resume_help">
            <div class="form-text" id="a_resume_help">PDF, Word, ODT, RTF or plain text — up to <?= (int) floor(\App\Support\Upload::maxBytes() / 1048576) ?>&nbsp;MB. No CV handy? A portfolio link and a good note work too.</div>
        </div>

        <div class="col-12">
            <label class="form-label" for="a_captcha">Quick check: <?= e($captcha ?? 'What is 3 + 4?') ?></label>
            <input id="a_captcha" type="text" name="captcha" inputmode="numeric" autocomplete="off" class="form-control <?= has_error('captcha') ? 'is-invalid' : '' ?>" style="max-width:180px" required<?= $describe('a_captcha', has_error('captcha'), 'a_captcha_help') ?>>
            <?php if (error('captcha')): ?><div id="a_captcha_err" class="invalid-feedback d-block"><?= e(error('captcha')) ?></div><?php endif; ?>
            <div class="form-text" id="a_captcha_help">A quick sum, to keep bots out. No tracking, no third party.</div>
        </div>
    </div>

    <button class="btn btn-brand btn-lg mt-3">Send Application</button>
    <p class="text-muted small mt-3 mb-0">
        We use what you send here only to consider you for work with us. Your details aren't sold or shared, and you can ask us to delete them any time — see our
        <a href="<?= route('legal.privacy') ?>">Privacy Policy</a>.
    </p>
</form>
