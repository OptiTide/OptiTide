<?php
// Strong, business-favourable cancellation/refund template. Solicitor review advised.
$this->extends('layouts.public');
$co = config('company.legal_name');
$abn = config('company.abn');
$email = config('company.email');
?>
<?php $this->section('content'); ?>
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h2 fw-bold mb-1">Refund &amp; Cancellation Policy</h1>
    <p class="text-muted small mb-4">Last updated <?= date('F Y') ?><?= $abn ? ' · ' . e($co) . ' · ABN ' . e($abn) : '' ?></p>

    <p>This policy explains how cancellations and refunds work for services provided by <?= e($co) ?>. It should be read together with our <a href="<?= route('legal.terms') ?>" target="_blank" rel="noopener">Terms of Service</a>. Nothing in this policy excludes any right or remedy you have under the Australian Consumer Law (ACL) that cannot lawfully be excluded.</p>

    <h2 class="h5 fw-bold mt-4">1. Deposits are non-refundable</h2>
    <p>Deposits secure your place in our schedule and cover work that begins immediately (discovery, planning, research and resourcing). Deposits are non-refundable once paid.</p>

    <h2 class="h5 fw-bold mt-4">2. Work already performed</h2>
    <p>Because our services are custom and labour-based, once work on a project or cycle has commenced, fees for that work are earned and non-refundable. If you cancel a project part-way, you remain liable for all work performed and expenses incurred up to cancellation, and any unpaid balance for that work becomes immediately due.</p>

    <h2 class="h5 fw-bold mt-4">3. Recurring services (SEO, social media, hosting)</h2>
    <p>Recurring services are billed in advance and may be cancelled with at least 30 days' written notice. The current billing period and the notice period are payable in full and are not refundable or pro-rated. Cancelling stops future billing only; it does not entitle you to a refund of amounts already paid.</p>

    <h2 class="h5 fw-bold mt-4">4. Change of mind</h2>
    <p>We are not required to provide a refund or credit where you simply change your mind, find the service cheaper elsewhere, no longer want the service, or fail to provide what we need to proceed.</p>

    <h2 class="h5 fw-bold mt-4">5. Your rights under the Australian Consumer Law</h2>
    <p>Our services come with guarantees that cannot be excluded under the ACL. If there is a major failure with a service, you may be entitled to a remedy. For failures that do not amount to a major failure, we may choose to re-supply the service or refund the reasonable cost of doing so. Any refund we agree to is limited to the portion of the fee reasonably attributable to the affected service.</p>

    <h2 class="h5 fw-bold mt-4">6. Third-party costs</h2>
    <p>Amounts we pay to third parties on your behalf (domain registration, licences, stock assets, paid advertising, third-party hosting or software) are non-refundable once incurred.</p>

    <h2 class="h5 fw-bold mt-4">7. Chargebacks</h2>
    <p>If you believe an amount has been charged in error, contact us first at <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> so we can resolve it. Initiating a chargeback without first contacting us is a breach of these terms, and we may suspend services and recover our costs while the matter is resolved.</p>

    <h2 class="h5 fw-bold mt-4">8. How to request</h2>
    <p>All cancellations and refund requests must be made in writing to <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> and are assessed in line with this policy and the ACL.</p>
</div></div>
<?php $this->endSection(); ?>
