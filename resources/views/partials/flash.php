<?php
$flashes = ['success' => 'success', 'status' => 'info', 'error' => 'danger', 'warning' => 'warning'];
foreach ($flashes as $key => $variant):
    $message = session($key);
    if (! $message) {
        continue;
    }
    ?>
    <div class="alert alert-<?= $variant ?> alert-dismissible fade show" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>

<?php if (errors()): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach (errors() as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
