<?php
$path = request()?->path() ?? '';
$active = fn (string $prefix, bool $exact = false): string => ($exact ? $path === $prefix : str_starts_with($path, $prefix)) ? 'active' : '';
$me = auth();
?>
<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => ($title ?? 'My Account') . ' — OptiTide']); ?></head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="<?= route('portal.dashboard') ?>" class="d-block mb-2"><img class="brand-logo brand-logo--chip" src="/assets/img/logo.png" alt="OptiTide"></a>
        <div class="text-secondary small mb-2" style="font-size:.72rem">Client Portal</div>

        <nav class="nav flex-column">
            <a class="nav-link <?= $active('/portal', true) ?>" href="<?= route('portal.dashboard') ?>"><i class="bi bi-house"></i> Dashboard</a>
            <a class="nav-link <?= $active('/portal/order') ?>" href="<?= route('portal.order.index') ?>"><i class="bi bi-bag-plus"></i> Order a Service</a>
            <a class="nav-link <?= $active('/portal/services') ?>" href="<?= route('portal.services') ?>"><i class="bi bi-grid"></i> My Services</a>
            <a class="nav-link <?= $active('/portal/invoices') ?>" href="<?= route('portal.invoices.index') ?>"><i class="bi bi-receipt"></i> Invoices</a>
            <a class="nav-link <?= $active('/portal/support') ?>" href="<?= route('portal.support.index') ?>"><i class="bi bi-life-preserver"></i> Support</a>
            <a class="nav-link <?= $active('/portal/refer') ?>" href="<?= route('portal.refer') ?>"><i class="bi bi-gift"></i> Refer &amp; Earn</a>
            <a class="nav-link <?= $active('/portal/profile') ?>" href="<?= route('portal.profile.edit') ?>"><i class="bi bi-person"></i> Profile</a>
        </nav>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>
                <h1 class="page-title"><?= e($title ?? 'Dashboard') ?></h1>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?= e($me['name'] ?? 'Account') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= route('portal.profile.edit') ?>">Profile</a></li>
                    <li><a class="dropdown-item" href="<?= route('security.show') ?>">Security &amp; 2FA</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= route('logout') ?>" class="px-1">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger" type="submit">Sign Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="main">
            <?php if (\App\Core\Session::has('_impersonator')): ?>
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-eye"></i> You are previewing this portal as a client (admin view).</span>
                    <form method="post" action="<?= route('impersonate.leave') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-dark">Return to Admin</button></form>
                </div>
            <?php endif; ?>
            <?php $this->insert('partials.flash'); ?>
            <?= $this->yield('content') ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
