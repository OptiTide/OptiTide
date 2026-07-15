<?php
$this->extends('layouts.public');
$co = config('company.legal_name');
$email = config('company.email');
?>
<?php $this->section('content'); ?>
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h2 fw-bold mb-1">Terms of Service</h1>
    <p class="text-muted small mb-4">Last updated <?= date('F Y') ?></p>

    <p>These Terms of Service ("Terms") govern the services provided by <?= e($co) ?> ("we", "us", "our") to you ("the client"). By engaging our services, accepting a quote, or using our client portal, you agree to these Terms.</p>

    <h2 class="h5 fw-bold mt-4">1. Services</h2>
    <p>We provide web design and development, search engine optimisation (SEO), social media marketing and web hosting. The specific services, deliverables and fees for your engagement are set out in your quote, proposal or invoice, which forms part of these Terms.</p>

    <h2 class="h5 fw-bold mt-4">2. Quotes, Pricing &amp; GST</h2>
    <p>Quotes are valid for 30 days unless stated otherwise. All prices are in Australian dollars (AUD) and, where we are registered for GST, are inclusive of GST. Recurring services (such as SEO, social media and hosting) are billed each cycle in advance until cancelled in accordance with these Terms.</p>

    <h2 class="h5 fw-bold mt-4">3. Payment</h2>
    <p>Invoices are payable by the due date shown on the invoice. We accept payment by PayID/bank transfer and Payoneer. We may pause or suspend services, including hosting, where an account is significantly overdue. Recurring plans continue to accrue until cancelled.</p>

    <h2 class="h5 fw-bold mt-4">4. Client Responsibilities</h2>
    <p>You agree to provide accurate information, content and access we reasonably need to perform the services, and to review and approve work in a timely manner. You are responsible for the legality and ownership of any content you supply.</p>

    <h2 class="h5 fw-bold mt-4">5. Intellectual Property</h2>
    <p>On full payment for a project, ownership of the final deliverables created specifically for you transfers to you, except for third-party materials, open-source components and our own pre-existing tools, which remain owned by their respective owners and are licensed to you for use in the deliverables.</p>

    <h2 class="h5 fw-bold mt-4">6. Warranties &amp; Liability</h2>
    <p>We perform our services with due care and skill. Nothing in these Terms excludes rights you have under the Australian Consumer Law. To the extent permitted by law, our total liability arising from the services is limited to the fees paid by you for the relevant service, and we are not liable for indirect or consequential loss.</p>

    <h2 class="h5 fw-bold mt-4">7. Third-Party Services</h2>
    <p>Some services rely on third parties (for example hosting infrastructure, domain registrars, analytics and social platforms). We are not responsible for outages, changes or actions of those third parties, though we will use reasonable efforts to assist.</p>

    <h2 class="h5 fw-bold mt-4">8. Termination</h2>
    <p>Either party may end an ongoing engagement with reasonable written notice. Fees for work performed and the current billing cycle remain payable. Cancellation of recurring services is covered by our <a href="<?= route('legal.refund') ?>">Refund &amp; Cancellation Policy</a>.</p>

    <h2 class="h5 fw-bold mt-4">9. Privacy</h2>
    <p>We handle personal information in accordance with our <a href="<?= route('legal.privacy') ?>">Privacy Policy</a> and the Privacy Act 1988 (Cth).</p>

    <h2 class="h5 fw-bold mt-4">10. Governing Law</h2>
    <p>These Terms are governed by the laws of Australia and the state in which we operate. The parties submit to the non-exclusive jurisdiction of the courts of that state.</p>

    <h2 class="h5 fw-bold mt-4">11. Contact</h2>
    <p>Questions about these Terms? Email us at <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
</div></div>
<?php $this->endSection(); ?>
