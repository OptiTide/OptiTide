<?php
$this->extends('layouts.public');
$co = config('company.legal_name');
$abn = config('company.abn');
$email = config('company.email');
?>
<?php $this->section('content'); ?>
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h2 fw-bold mb-1">Privacy Policy</h1>
    <p class="text-muted small mb-4">Last updated <?= date('F Y') ?><?= $abn ? ' · ' . e($co) . ' · ABN ' . e($abn) : '' ?></p>

    <p><?= e($co) ?> ("we", "us") is committed to protecting your privacy and handling your personal information in accordance with the Privacy Act 1988 (Cth) and the Australian Privacy Principles (APPs).</p>

    <h2 class="h5 fw-bold mt-4">1. Information We Collect</h2>
    <p>We may collect your name, business name, email address, phone number, business address, ABN, and information you provide through our website forms, enquiries and client portal. When you use our services we may also collect billing details and records of the work we do for you.</p>

    <h2 class="h5 fw-bold mt-4">2. How We Use It</h2>
    <p>We use your information to provide and manage our services, prepare quotes and invoices, communicate with you, provide support, and meet our legal and tax obligations. We do not sell your personal information.</p>

    <h2 class="h5 fw-bold mt-4">3. Disclosure</h2>
    <p>We may share information with trusted service providers who help us operate (for example payment, email and hosting providers), only as needed to deliver our services, and where required by law. Some providers may store data overseas; we take reasonable steps to ensure appropriate protections.</p>

    <h2 class="h5 fw-bold mt-4">4. Storage &amp; Security</h2>
    <p>We take reasonable steps to protect your information from misuse, loss and unauthorised access, including access controls and encryption where appropriate. No method of transmission or storage is completely secure.</p>

    <h2 class="h5 fw-bold mt-4">5. Cookies, Analytics &amp; Live Chat</h2>
    <p>Our website may use cookies and analytics tools (including our own visitor analytics and third-party services such as Google Analytics and Meta Pixel where configured) to understand how the site is used, measure marketing, and improve our services. We also record live-chat conversations and basic visit information (such as pages viewed, referrer and approximate location) to provide support and improve our service. You can control cookies through your browser settings.</p>

    <h2 class="h5 fw-bold mt-4">6. Job Applicants</h2>
    <p>If you apply for a role through our <a href="<?= route('careers.index') ?>">careers page</a>, we collect what you choose to give us — your name and email address, and optionally your phone number, location, LinkedIn or portfolio links, the note you write, and your CV if you attach one. We also record the IP address the application came from, to help us prevent spam and abuse.</p>
    <p>We use this only to consider you for the role you applied for, and for other roles we think may suit you. We don't sell it and we don't share it outside <?= e($co) ?>, except where the law requires. Your CV is stored on our servers where it is not publicly accessible, and is only opened by the people involved in hiring.</p>
    <p>If you're unsuccessful we may keep your application on file so we can contact you when a suitable role opens up. You can ask us to delete your application and CV at any time and we will — just email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>. The Privacy Act's employee-records exemption does not apply to job applicants, so the rights below apply to your application in full.</p>

    <h2 class="h5 fw-bold mt-4">7. Your Rights</h2>
    <p>You may request access to, or correction of, the personal information we hold about you. To make a request or raise a privacy concern, contact us using the details below. If you are not satisfied with our response, you may contact the Office of the Australian Information Commissioner (OAIC).</p>

    <h2 class="h5 fw-bold mt-4">8. Contact</h2>
    <p>For privacy enquiries, email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
</div></div>
<?php $this->endSection(); ?>
