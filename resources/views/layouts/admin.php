<?php
$path = request()?->path() ?? '';
$active = fn (string $prefix, bool $exact = false): string => ($exact ? $path === $prefix : str_starts_with($path, $prefix)) ? 'active' : '';
$me = auth();
?>
<!doctype html>
<html lang="en-AU">
<head><?php $this->insert('partials.head', ['title' => ($title ?? 'Dashboard') . ' — ' . config('company.brand_name')]); ?></head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <button type="button" class="sidebar-close btn btn-sm btn-outline-light float-end" onclick="otToggleSidebar(false)" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
        <a href="<?= route('admin.dashboard') ?>" class="sidebar-brand" aria-label="<?= e(config('company.brand_name')) ?> admin">
            <span class="sidebar-brand-mark"><img src="/assets/img/mark-wave.png" alt="<?= e(config('company.brand_name')) ?>"></span>
            <span class="sidebar-brand-name">Opti<span style="color:var(--brand)">Tide</span></span>
        </a>
        <div class="text-secondary small mb-2" style="font-size:.72rem">Billing &amp; CRM</div>

        <nav class="nav flex-column">
            <div class="nav-section">General</div>
            <a class="nav-link <?= $active('/admin', true) ?>" href="<?= route('admin.dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="nav-link <?= $active('/admin/clients') ?>" href="<?= route('admin.clients.index') ?>"><i class="bi bi-people"></i> Clients</a>

            <div class="nav-section">Billing</div>
            <a class="nav-link <?= $active('/admin/invoices') ?>" href="<?= route('admin.invoices.index') ?>"><i class="bi bi-receipt"></i> Invoices</a>
            <a class="nav-link <?= $active('/admin/installments') ?>" href="<?= route('admin.installments.index') ?>"><i class="bi bi-hourglass-split"></i> Payment Plans</a>
            <a class="nav-link <?= $active('/admin/services') ?>" href="<?= route('admin.services.index') ?>"><i class="bi bi-grid"></i> Services</a>

            <div class="nav-section">Support &amp; Sales</div>
            <a class="nav-link <?= $active('/admin/tickets') ?>" href="<?= route('admin.tickets.index') ?>"><i class="bi bi-life-preserver"></i> Helpdesk</a>
            <a class="nav-link <?= $active('/admin/chat') ?>" href="<?= route('admin.chat.index') ?>"><i class="bi bi-chat-dots"></i> Live Chat</a>
            <a class="nav-link <?= $active('/admin/meetings') ?>" href="<?= route('admin.meetings.index') ?>"><i class="bi bi-calendar-event"></i> Meetings</a>
            <a class="nav-link <?= $active('/admin/blogs') ?>" href="<?= route('admin.blogs.index') ?>"><i class="bi bi-newspaper"></i> Blog</a>
            <a class="nav-link <?= $active('/admin/backlinks') ?>" href="<?= route('admin.backlinks.index') ?>"><i class="bi bi-link-45deg"></i> Backlinks</a>

            <div class="nav-section">Delivery Boards</div>
            <a class="nav-link <?= $active('/admin/boards/web-design') ?>" href="<?= route('admin.boards.show', ['key' => 'web-design']) ?>"><i class="bi bi-palette"></i> Web Design</a>
            <a class="nav-link <?= $active('/admin/boards/seo') ?>" href="<?= route('admin.boards.show', ['key' => 'seo']) ?>"><i class="bi bi-graph-up-arrow"></i> SEO</a>
            <a class="nav-link <?= $active('/admin/boards/smm') ?>" href="<?= route('admin.boards.show', ['key' => 'smm']) ?>"><i class="bi bi-instagram"></i> Social Media</a>
            <a class="nav-link <?= $active('/admin/hosting') ?>" href="<?= route('admin.hosting.index') ?>"><i class="bi bi-hdd-network"></i> Hosting Accounts</a>

            <?php if (\App\Core\Auth::isAdmin()): ?>
                <div class="nav-section">Admin</div>
                <a class="nav-link <?= $active('/admin/assistant') ?>" href="<?= route('admin.assistant.index') ?>"><i class="bi bi-stars"></i> AI Assistant</a>
                <a class="nav-link <?= $active('/admin/broadcast') ?>" href="<?= route('admin.broadcast.index') ?>"><i class="bi bi-envelope-paper"></i> Mass Email</a>
                <a class="nav-link <?= $active('/admin/visitors') ?>" href="<?= route('admin.visitors.index') ?>"><i class="bi bi-people-fill"></i> Visitors</a>
                <a class="nav-link <?= $active('/admin/commissions') ?>" href="<?= route('admin.commissions.index') ?>"><i class="bi bi-cash-stack"></i> Commissions</a>
                <a class="nav-link <?= $active('/admin/users') ?>" href="<?= route('admin.users.index') ?>"><i class="bi bi-person-badge"></i> Users</a>
                <a class="nav-link <?= $active('/admin/audit-log') ?>" href="<?= route('admin.audit.index') ?>"><i class="bi bi-journal-text"></i> Audit Log</a>
                <a class="nav-link <?= $active('/admin/settings') ?>" href="<?= route('admin.settings.edit') ?>"><i class="bi bi-gear"></i> Settings</a>
            <?php endif; ?>
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
                    <li><span class="dropdown-item-text small text-muted"><?= e(ucfirst($me['role'] ?? '')) ?></span></li>
                    <li><a class="dropdown-item" href="<?= route('security.show') ?>"><i class="bi bi-shield-lock"></i> Security &amp; 2FA</a></li>
                    <?php if (\App\Core\Auth::isAdmin()): ?>
                        <li><a class="dropdown-item" href="<?= route('admin.settings.edit') ?>">Settings</a></li>
                    <?php endif; ?>
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
            <?php $this->insert('partials.flash'); ?>
            <?= $this->yield('content') ?>
        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(config('company.legal_name')) ?><?= config('company.abn') ? ' · ABN ' . e(config('company.abn')) : '' ?></span>
            <span class="tag">Grow Online. Lead Always.</span>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $this->insert('partials.sidebar-js'); ?>
</body>
</html>
