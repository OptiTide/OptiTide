<?php $this->extends('layouts.public'); ?>
<?php $this->section('content'); ?>
<div class="text-center py-5">
    <div class="display-3 fw-bold text-brand"><?= e($status ?? 500) ?></div>
    <p class="lead mt-2"><?= e($message ?: 'Something went wrong.') ?></p>
    <a href="/" class="btn btn-brand mt-2">Back to home</a>
</div>
<?php $this->endSection(); ?>
