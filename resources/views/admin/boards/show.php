<?php
$this->extends('layouts.admin');
$clientNames = array_column($clients, 'business_name', 'id');
?>
<?php $this->section('content'); ?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($boards as $b): ?>
        <a href="<?= route('admin.boards.show', ['key' => $b['key']]) ?>" class="btn btn-sm <?= $b['id'] === $board['id'] ? 'btn-brand' : 'btn-outline-brand' ?>"><?= e($b['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="kb-board">
    <?php foreach ($columns as $col): ?>
        <?php $cards = $cardsByColumn[$col['id']] ?? []; ?>
        <div class="kb-col" data-column-id="<?= $col['id'] ?>">
            <div class="kb-col-head">
                <span><?= e($col['name']) ?></span>
                <span class="kb-count"><?= count($cards) ?></span>
            </div>
            <div class="kb-list" data-column-id="<?= $col['id'] ?>">
                <?php foreach ($cards as $card): ?>
                    <div class="kb-card" draggable="true" data-id="<?= $card['id'] ?>"
                         data-title="<?= e($card['title']) ?>"
                         data-notes="<?= e($card['notes'] ?? '') ?>"
                         data-client="<?= e($card['client_id'] ?? '') ?>"
                         data-due="<?= e($card['due_date'] ? date('Y-m-d', strtotime($card['due_date'])) : '') ?>">
                        <button type="button" class="kb-card-edit" title="Edit"><i class="bi bi-pencil"></i></button>
                        <div class="kb-card-title"><?= e($card['title']) ?></div>
                        <div class="kb-card-meta">
                            <?php if (! empty($card['client_id']) && isset($clientNames[$card['client_id']])): ?>
                                <span class="kb-tag"><i class="bi bi-building"></i> <?= e($clientNames[$card['client_id']]) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($card['due_date'])): ?>
                                <span class="kb-tag kb-tag--due"><i class="bi bi-calendar-event"></i> <?= e(date('d M', strtotime($card['due_date']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="kb-add" method="post" action="<?= route('admin.boards.cards.store', ['key' => $board['key']]) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="column_id" value="<?= $col['id'] ?>">
                <input type="text" name="title" placeholder="+ Add a card" maxlength="200" required autocomplete="off">
                <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Add</button>
            </form>
        </div>
    <?php endforeach; ?>

    <div class="kb-col kb-col--add">
        <form method="post" action="<?= route('admin.boards.columns.store', ['key' => $board['key']]) ?>" class="kb-add-col">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="+ Add a column" maxlength="80" required autocomplete="off">
            <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Add</button>
        </form>
    </div>
</div>

<!-- Edit card modal -->
<div class="modal fade" id="cardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cardForm" method="post">
                <?= csrf_field() ?><?= method_field('PUT') ?>
                <div class="modal-header">
                    <h5 class="modal-title">Card details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="cf_title" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="cf_notes" rows="4" class="form-control" maxlength="2000"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Client</label>
                            <select name="client_id" id="cf_client" class="form-select">
                                <option value="">— None —</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['business_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Due date</label>
                            <input type="date" name="due_date" id="cf_due" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="submit" class="btn btn-outline-danger btn-sm" form="cardDeleteForm"><i class="bi bi-trash"></i> Delete</button>
                    <button type="submit" class="btn btn-brand">Save Card</button>
                </div>
            </form>
        </div>
    </div>
</div>
<form id="cardDeleteForm" method="post" class="d-none"><?= csrf_field() ?><?= method_field('DELETE') ?></form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var dragged = null;

    document.querySelectorAll('.kb-card').forEach(function (card) {
        card.addEventListener('dragstart', function (e) { dragged = card; card.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
        card.addEventListener('dragend', function () { card.classList.remove('dragging'); });
    });

    document.querySelectorAll('.kb-list').forEach(function (list) {
        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragged) return;
            var after = getAfter(list, e.clientY);
            if (after == null) list.appendChild(dragged); else list.insertBefore(dragged, after);
        });
        list.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragged) return;
            persist(list, dragged);
            updateCounts();
        });
    });

    function getAfter(list, y) {
        var els = Array.prototype.slice.call(list.querySelectorAll('.kb-card:not(.dragging)'));
        var closest = { offset: -Infinity, element: null };
        els.forEach(function (child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) closest = { offset: offset, element: child };
        });
        return closest.element;
    }

    function persist(list, card) {
        var order = Array.prototype.slice.call(list.querySelectorAll('.kb-card')).map(function (c) { return c.dataset.id; });
        var body = new URLSearchParams();
        body.append('column_id', list.dataset.columnId);
        order.forEach(function (id) { body.append('order[]', id); });
        fetch('<?= url('admin/cards') ?>/' + card.dataset.id + '/move', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).catch(function () {});
    }

    function updateCounts() {
        document.querySelectorAll('.kb-col').forEach(function (col) {
            var badge = col.querySelector('.kb-count');
            if (badge) badge.textContent = col.querySelectorAll('.kb-card').length;
        });
    }

    var modalEl = document.getElementById('cardModal');
    var modal = (modalEl && window.bootstrap) ? new bootstrap.Modal(modalEl) : null;
    document.querySelectorAll('.kb-card-edit').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var card = btn.closest('.kb-card');
            document.getElementById('cardForm').action = '<?= url('admin/cards') ?>/' + card.dataset.id;
            document.getElementById('cardDeleteForm').action = '<?= url('admin/cards') ?>/' + card.dataset.id;
            document.getElementById('cf_title').value = card.dataset.title || '';
            document.getElementById('cf_notes').value = card.dataset.notes || '';
            document.getElementById('cf_due').value = card.dataset.due || '';
            var sel = document.getElementById('cf_client');
            if (sel) sel.value = card.dataset.client || '';
            if (modal) modal.show();
        });
    });
});
</script>
<?php $this->endSection(); ?>
