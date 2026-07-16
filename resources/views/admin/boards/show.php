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
                    <?php
                    $priority = $card['priority'] ?? \App\Models\BoardCard::PRIORITY_NORMAL;
                    $bar = $progress[$card['id']] ?? ['done' => 0, 'total' => 0, 'pct' => 0];
                    $comments = $commentCounts[$card['id']] ?? 0;
                    $hidden = ! ($card['client_visible'] ?? 1);
                    ?>
                    <div class="kb-card kb-card--p-<?= e($priority) ?>" draggable="true" data-id="<?= $card['id'] ?>">
                        <a href="<?= route('admin.cards.show', ['id' => $card['id']]) ?>" class="kb-card-edit" title="Open card" draggable="false"><i class="bi bi-pencil"></i></a>
                        <div class="kb-card-title"><?= e($card['title']) ?></div>
                        <div class="kb-card-meta">
                            <?php if ($priority !== \App\Models\BoardCard::PRIORITY_NORMAL): ?>
                                <span class="badge <?= e(\App\Models\BoardCard::priorityBadge($priority)) ?>"><?= e(\App\Models\BoardCard::priorityLabel($priority)) ?></span>
                            <?php endif; ?>
                            <?php if ($hidden): ?>
                                <span class="kb-tag kb-tag--hidden" title="Hidden from the client"><i class="bi bi-eye-slash"></i> Internal</span>
                            <?php endif; ?>
                            <?php if (! empty($card['completed_at'])): ?>
                                <span class="kb-tag kb-tag--done"><i class="bi bi-check-circle-fill"></i> Complete</span>
                            <?php endif; ?>
                            <?php if (! empty($card['client_id']) && isset($clientNames[$card['client_id']])): ?>
                                <span class="kb-tag"><i class="bi bi-building"></i> <?= e($clientNames[$card['client_id']]) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($card['assigned_to']) && isset($staffNames[$card['assigned_to']])): ?>
                                <span class="kb-tag"><i class="bi bi-person"></i> <?= e($staffNames[$card['assigned_to']]) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($card['due_date'])): ?>
                                <span class="kb-tag kb-tag--due"><i class="bi bi-calendar-event"></i> <?= e(date('d M', strtotime($card['due_date']))) ?></span>
                            <?php endif; ?>
                            <?php if ($bar['total'] > 0): ?>
                                <span class="kb-tag <?= $bar['pct'] === 100 ? 'kb-tag--done' : '' ?>"><i class="bi bi-check2-square"></i> <?= $bar['done'] ?>/<?= $bar['total'] ?></span>
                            <?php endif; ?>
                            <?php if ($comments > 0): ?>
                                <span class="kb-tag"><i class="bi bi-chat-left-text"></i> <?= (int) $comments ?></span>
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
});
</script>
<?php $this->endSection(); ?>
