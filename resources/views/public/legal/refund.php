<?php
$this->extends('layouts.public');
$co = config('company.legal_name');
$email = config('company.email');
?>
<?php $this->section('content'); ?>
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h2 fw-bold mb-1">Refund &amp; Cancellation Policy</h1>
    <p class="text-muted small mb-4">Last updated <?= date('F Y') ?></p>

    <p>This policy explains how deposits, cancellations and refunds work for services provided by <?= e($co) ?>. It operates together with our <a href="<?= route('legal.terms') ?>">Terms of Service</a> and does not limit your rights under the Australian Consumer Law.</p>

    <h2 class="h5 fw-bold mt-4">1. Project Work (Web Design)</h2>
    <p>Project deposits reserve your booking and cover initial work; they are generally non-refundable once work has commenced. If you cancel a project partway through, fees for work already performed remain payable, and any balance for uncommenced work may be refunded at our discretion.</p>

    <h2 class="h5 fw-bold mt-4">2. Recurring Services (SEO &amp; Social Media)</h2>
    <p>Recurring plans are billed in advance each cycle. You may cancel at any time with reasonable notice; cancellation stops future billing. The current cycle is generally not refundable, as work and resourcing are allocated for that period.</p>

    <h2 class="h5 fw-bold mt-4">3. Hosting</h2>
    <p>Hosting is billed in advance (monthly or annually). You may cancel to stop future renewals. We do not typically refund the unused portion of a current hosting period, but will help you migrate your site where reasonable.</p>

    <h2 class="h5 fw-bold mt-4">4. Faulty or Not-as-Described Services</h2>
    <p>If a service is faulty or not as described, you are entitled to a remedy under the Australian Consumer Law, which may include a repair, re-performance or refund. Please contact us and we will work with you to make it right.</p>

    <h2 class="h5 fw-bold mt-4">5. How to Request</h2>
    <p>To cancel a service or request a refund, email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> with your business name and invoice number.</p>
</div></div>
<?php $this->endSection(); ?>
