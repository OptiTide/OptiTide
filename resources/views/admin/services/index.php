<?php
$this->extends('layouts.admin');
$currency = config('company.currency', 'AUD');
?>
<?php $this->section('content'); ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Service Catalogue</span>
                <a href="<?= route('admin.services.create') ?>" class="btn btn-sm btn-brand"><i class="bi bi-plus-lg"></i> New Service</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Name</th><th>Line</th><th>Billing</th><th class="text-end">Price</th><th></th><th></th></tr></thead>
                    <tbody>
                        <?php if (! $services): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No services yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($service['name']) ?></td>
                                <td><?= e($cat_names[$service['category_id']] ?? '—') ?></td>
                                <td>
                                    <?php if ($service['billing_type'] === 'recurring'): ?>
                                        <span class="badge badge-soft"><?= e(ucfirst($service['interval'] ?? 'monthly')) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-soft">One-off</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end money"><?= e(money((int) $service['price_cents'], $service['currency'])->format()) ?></td>
                                <td><?php if (! $service['active']): ?><span class="badge text-bg-secondary">Inactive</span><?php endif; ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= route('admin.services.edit', ['id' => $service['id']]) ?>" class="btn btn-sm btn-link"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="<?= route('admin.services.destroy', ['id' => $service['id']]) ?>" class="d-inline" onsubmit="return confirm('Delete this service?')">
                                        <?= csrf_field() ?><?= method_field('DELETE') ?>
                                        <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Service Lines</span>
                <button class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#catModal" onclick="catAdd()"><i class="bi bi-plus-lg"></i></button>
            </div>
            <ul class="list-group list-group-flush">
                <?php if (! $categories): ?>
                    <li class="list-group-item text-muted text-center py-3">No service lines.</li>
                <?php endif; ?>
                <?php foreach ($categories as $c): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= e($c['name']) ?></span>
                        <span class="text-nowrap">
                            <button class="btn btn-sm btn-link" onclick='catEdit(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                            <form method="post" action="<?= route('admin.categories.destroy', ['id' => $c['id']]) ?>" class="d-inline" onsubmit="return confirm('Remove this service line?')">
                                <?= csrf_field() ?><?= method_field('DELETE') ?>
                                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="card-footer text-muted small">Add a new line (e.g. Copywriting, Ads) to expand your offering.</div>
        </div>
    </div>
</div>

<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="catForm" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" id="catMethod" value="">
            <div class="modal-header"><h5 class="modal-title" id="catTitle">Add Service Line</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" id="catName" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Description (optional)</label>
                    <input type="text" name="description" id="catDesc" class="form-control">
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-brand">Save</button></div>
        </form>
    </div>
</div>

<script>
const CAT_STORE = "<?= route('admin.categories.store') ?>";
const CAT_UPDATE = "<?= url('admin/categories') ?>/";
function catAdd() {
    document.getElementById('catTitle').textContent = 'Add Service Line';
    document.getElementById('catForm').action = CAT_STORE;
    document.getElementById('catMethod').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catDesc').value = '';
}
function catEdit(c) {
    document.getElementById('catTitle').textContent = 'Edit Service Line';
    document.getElementById('catForm').action = CAT_UPDATE + c.id;
    document.getElementById('catMethod').value = 'PUT';
    document.getElementById('catName').value = c.name;
    document.getElementById('catDesc').value = c.description || '';
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
</script>
<?php $this->endSection(); ?>
