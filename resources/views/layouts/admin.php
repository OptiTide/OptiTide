<?php
$path = request()?->path() ?? '';
$active = fn (string $prefix, bool $exact = false): string => ($exact ? $path === $prefix : str_starts_with($path, $prefix)) ? 'active' : '';
$me = auth();
?>
<!doctype html>
<html lang="en">
<head><?php $this->insert('partials.head', ['title' => ($title ?? 'Dashboard') . ' — OptiTide']); ?></head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a href="<?= route('admin.dashboard') ?>" class="d-block mb-2"><img class="brand-logo brand-logo--chip" src="/assets/img/logo.png" alt="OptiTide"></a>
        <div class="text-secondary small mb-2" style="font-size:.72rem">Billing &amp; CRM</div>

        <nav class="nav flex-column">
            <a class="nav-link <?= $active('/admin', true) ?>" href="<?= route('admin.dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="nav-link <?= $active('/admin/clients') ?>" href="<?= route('admin.clients.index') ?>"><i class="bi bi-people"></i> Clients</a>
            <a class="nav-link <?= $active('/admin/invoices') ?>" href="<?= route('admin.invoices.index') ?>"><i class="bi bi-receipt"></i> Invoices</a>
            <a class="nav-link <?= $active('/admin/services') ?>" href="<?= route('admin.services.index') ?>"><i class="bi bi-grid"></i> Services</a>
            <a class="nav-link <?= $active('/admin/boards') ?>" href="<?= route('admin.boards.index') ?>"><i class="bi bi-kanban"></i> Project Boards</a>
            <a class="nav-link <?= $active('/admin/blogs') ?>" href="<?= route('admin.blogs.index') ?>"><i class="bi bi-newspaper"></i> Blog</a>

            <?php if (\App\Core\Auth::isAdmin()): ?>
                <div class="nav-section">Admin</div>
                <a class="nav-link <?= $active('/admin/users') ?>" href="<?= route('admin.users.index') ?>"><i class="bi bi-person-badge"></i> Users</a>
                <a class="nav-link <?= $active('/admin/settings') ?>" href="<?= route('admin.settings.edit') ?>"><i class="bi bi-gear"></i> Settings</a>
            <?php endif; ?>
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

        <main class="main">
            <?php $this->insert('partials.flash'); ?>
            <?= $this->yield('content') ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
