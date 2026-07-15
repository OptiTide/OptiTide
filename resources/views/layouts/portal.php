<?php
$path = request()?->path() ?? '';
$active = fn (string $prefix, bool $exact = false): string => ($exact ? $path === $prefix : str_starts_with($path, $prefix)) ? 'active' : '';
$me = auth();
?>
<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => ($title ?? 'My Account') . ' — OptiTide']); ?></head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <button type="button" class="sidebar-close btn btn-sm btn-outline-light float-end" onclick="otToggleSidebar(false)" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
        <a href="<?= route('portal.dashboard') ?>" class="sidebar-brand" aria-label="OptiTide home">
            <span class="sidebar-brand-mark"><img src="/assets/img/mark-wave.png" alt="OptiTide"></span>
            <span class="sidebar-brand-name">Opti<span style="color:var(--brand)">Tide</span></span>
        </a>
        <div class="text-secondary small mb-2" style="font-size:.72rem">Client Portal</div>

        <nav class="nav flex-column">
            <a class="nav-link <?= $active('/portal', true) ?>" href="<?= route('portal.dashboard') ?>"><i class="bi bi-house"></i> Dashboard</a>

            <div class="nav-section">Services</div>
            <a class="nav-link <?= $active('/portal/order') ?>" href="<?= route('portal.order.index') ?>"><i class="bi bi-bag-plus"></i> Order a Service</a>
            <a class="nav-link <?= $active('/portal/services') ?>" href="<?= route('portal.services') ?>"><i class="bi bi-grid"></i> My Services</a>
            <a class="nav-link <?= $active('/portal/project') ?>" href="<?= route('portal.project') ?>"><i class="bi bi-kanban"></i> My Project</a>
            <a class="nav-link <?= $active('/portal/hosting') ?>" href="<?= route('portal.hosting') ?>"><i class="bi bi-hdd-network"></i> Hosting</a>

            <div class="nav-section">Billing</div>
            <a class="nav-link <?= $active('/portal/invoices') ?>" href="<?= route('portal.invoices.index') ?>"><i class="bi bi-receipt"></i> Invoices</a>
            <a class="nav-link <?= $active('/portal/api-credits') ?>" href="<?= route('portal.api.index') ?>"><i class="bi bi-cpu"></i> API Credits</a>

            <div class="nav-section">Support</div>
            <a class="nav-link <?= $active('/portal/meetings') ?>" href="<?= route('portal.meetings') ?>"><i class="bi bi-calendar-event"></i> Meetings</a>
            <a class="nav-link <?= $active('/portal/support') ?>" href="<?= route('portal.support.index') ?>"><i class="bi bi-life-preserver"></i> Support</a>

            <div class="nav-section">Account</div>
            <a class="nav-link <?= $active('/portal/refer') ?>" href="<?= route('portal.refer') ?>"><i class="bi bi-gift"></i> Refer &amp; Earn</a>
            <a class="nav-link <?= $active('/portal/profile') ?>" href="<?= route('portal.profile.edit') ?>"><i class="bi bi-person"></i> Profile</a>
        </nav>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="otToggleSidebar(false)"></div>

    <div class="content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false" onclick="otToggleSidebar()"><i class="bi bi-list"></i></button>
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

        <main class="main" id="main">
            <?php if (\App\Core\Session::has('_impersonator')): ?>
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-eye"></i> You are previewing this portal as a client (admin view).</span>
                    <form method="post" action="<?= route('impersonate.leave') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-dark">Return to Admin</button></form>
                </div>
            <?php endif; ?>
            <?php if (! \App\Models\User::hasVerifiedEmail(\App\Core\Auth::user())): ?>
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-envelope-exclamation"></i> Please confirm your email address to secure your account. Check your inbox for the link we sent.</span>
                    <form method="post" action="<?= route('email.verify.resend') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-brand">Resend email</button></form>
                </div>
            <?php endif; ?>
            <?php $this->insert('partials.flash'); ?>
            <?= $this->yield('content') ?>
        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?><?= config('company.abn') ? ' · ABN ' . e(config('company.abn')) : '' ?></span>
            <span><a href="<?= route('legal.terms') ?>" target="_blank" rel="noopener">Terms</a> &middot; <a href="<?= route('legal.privacy') ?>" target="_blank" rel="noopener">Privacy</a> &middot; <a href="<?= route('portal.support.index') ?>">Support</a> &middot; <span class="tag">Grow Online. Lead Always.</span></span>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->insert('partials.sidebar-js'); ?>
<?php $this->insert('partials.chat-widget'); ?>
</body>
</html>
