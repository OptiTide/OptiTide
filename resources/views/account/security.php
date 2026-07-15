<?php $this->extends($layout); ?>
<?php $this->section('content'); ?>

<div class="row justify-content-center" id="twofactor">
    <div class="col-lg-8">

        <?php if ($recovery): ?>
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning-subtle">Save your recovery codes</div>
                <div class="card-body">
                    <p class="small text-muted">Store these somewhere safe. Each code works once if you lose access to your device. They won't be shown again.</p>
                    <div class="row row-cols-2 g-2">
                        <?php foreach ($recovery as $code): ?>
                            <div class="col"><code class="d-block bg-light border rounded px-2 py-1 text-center"><?= e($code) ?></code></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Two-Factor Authentication</span>
                <?php if ($enabled): ?>
                    <span class="badge text-bg-success">On · <?= $method === 'email' ? 'E-Mail' : 'Authenticator App' ?></span>
                <?php else: ?>
                    <span class="badge text-bg-secondary">Off</span>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <?php if ($enabled): ?>
                    <p class="text-muted">Your account is protected with two-factor authentication. You'll be asked for a code each time you sign in.</p>
                    <form method="post" action="<?= route('security.disable') ?>" class="row g-2 align-items-end" onsubmit="return confirm('Turn off two-factor authentication?')">
                        <?= csrf_field() ?>
                        <div class="col-sm-7">
                            <label class="form-label">Confirm Your Password to Turn Off</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-outline-danger">Disable 2FA</button>
                        </div>
                    </form>

                <?php elseif ($setup && $setup_method === 'totp'): ?>
                    <p class="text-muted">Scan this QR code with Google Authenticator, Authy, 1Password or any authenticator app — then enter the 6-digit code it shows.</p>
                    <div class="d-flex flex-wrap gap-4 align-items-center">
                        <div style="width:200px"><?= $setup['qr'] ?></div>
                        <div>
                            <div class="small text-muted mb-1">Can't scan? Enter this key manually:</div>
                            <code class="d-inline-block bg-light border rounded px-2 py-1 mb-3"><?= e(chunk_split($setup['secret'], 4, ' ')) ?></code>
                            <form method="post" action="<?= route('security.confirm') ?>" class="row g-2 align-items-end">
                                <?= csrf_field() ?>
                                <div class="col-auto">
                                    <label class="form-label">6-Digit Code</label>
                                    <input type="text" name="code" inputmode="numeric" maxlength="6" class="form-control" placeholder="000000" required autofocus>
                                </div>
                                <div class="col-auto"><button class="btn btn-brand">Enable</button></div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($setup_method === 'email'): ?>
                    <p class="text-muted">We sent a 6-digit code to <strong><?= e($user['email']) ?></strong>. Enter it below to turn on e-mail two-factor authentication.</p>
                    <form method="post" action="<?= route('security.confirm') ?>" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-auto">
                            <label class="form-label">6-Digit Code</label>
                            <input type="text" name="code" inputmode="numeric" maxlength="6" class="form-control" placeholder="000000" required autofocus>
                        </div>
                        <div class="col-auto"><button class="btn btn-brand">Enable</button></div>
                    </form>
                    <form method="post" action="<?= route('security.setup') ?>" class="mt-2">
                        <?= csrf_field() ?><input type="hidden" name="method" value="email">
                        <button class="btn btn-link btn-sm p-0">Resend code</button>
                    </form>

                <?php else: ?>
                    <p class="text-muted">Add a second step at sign-in for stronger security. Choose a method:</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-1"><i class="bi bi-qr-code text-brand"></i> Authenticator App</div>
                                <p class="small text-muted">Scan a QR code with an app like Google Authenticator or Authy.</p>
                                <form method="post" action="<?= route('security.setup') ?>">
                                    <?= csrf_field() ?><input type="hidden" name="method" value="totp">
                                    <button class="btn btn-sm btn-brand">Set Up App</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold mb-1"><i class="bi bi-envelope text-brand"></i> E-Mail Code</div>
                                <p class="small text-muted">We e-mail you a 6-digit code each time you sign in.</p>
                                <form method="post" action="<?= route('security.setup') ?>">
                                    <?= csrf_field() ?><input type="hidden" name="method" value="email">
                                    <button class="btn btn-sm btn-outline-brand">Set Up E-Mail</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
