<?php
// NOTE: These are strong, business-favourable template terms. Have a solicitor
// review before relying on them — they cannot override the Australian Consumer Law.
$this->extends('layouts.public');
$co = config('company.legal_name');
$abn = config('company.abn');
$email = config('company.email');
?>
<?php $this->section('content'); ?>
<div class="row justify-content-center"><div class="col-lg-9">
    <h1 class="h2 fw-bold mb-1">Terms of Service</h1>
    <p class="text-muted small mb-4">Last updated <?= date('F Y') ?><?= $abn ? ' · ' . e($co) . ' · ABN ' . e($abn) : '' ?></p>

    <p>These Terms of Service ("Terms") govern all services provided by <?= e($co) ?><?= $abn ? ' (ABN ' . e($abn) . ')' : '' ?> ("we", "us", "our") to you ("you", "the client"). By engaging us, accepting a quote or proposal, paying a deposit or invoice, or using our client portal, you agree to be bound by these Terms, which prevail over any of your own terms to the extent of any inconsistency.</p>

    <h2 class="h5 fw-bold mt-4">1. Services &amp; scope</h2>
    <p>We provide web design and development, search engine optimisation (SEO), social media marketing and web hosting. The specific services, deliverables and fees are set out in your quote, proposal or invoice, which forms part of these Terms. Anything not expressly listed is out of scope. Additional work, changes to an agreed scope, or requests beyond the agreed number of revisions may be quoted separately and are chargeable.</p>

    <h2 class="h5 fw-bold mt-4">2. Quotes, pricing &amp; GST</h2>
    <p>Quotes are valid for 30 days and are estimates based on the information you provide; they may be revised if that information is incomplete or changes. All prices are in Australian dollars and, where we are registered for GST, include GST. We may review and adjust recurring fees on 30 days' notice. Recurring services (SEO, social media, hosting) are billed in advance each cycle and continue until cancelled in accordance with clause 8.</p>

    <h2 class="h5 fw-bold mt-4">3. Deposits &amp; payment</h2>
    <p>Unless stated otherwise, projects require a non-refundable deposit before work begins, with the balance payable as invoiced (including on completion or before final delivery/launch). Invoices are payable by the due date shown. Where we agree to instalments or a hardship arrangement, that arrangement is at our discretion, subject to approval, and does not defer your underlying liability. We may charge interest on overdue amounts at 2% per month (or part month) and recover reasonable costs of collection, including legal and agency fees. We may withhold delivery, pause, suspend or remove services (including taking a website offline) while any amount is overdue.</p>

    <h2 class="h5 fw-bold mt-4">4. Your responsibilities</h2>
    <p>You must provide accurate information, content, materials, approvals and access we reasonably need, and respond to requests promptly. Delays caused by you may extend timelines and incur additional fees. You are solely responsible for the content, materials and instructions you supply, including that you own or are licensed to use them and that they are lawful, accurate and not misleading. You must not use our services for any unlawful, infringing or harmful purpose.</p>

    <h2 class="h5 fw-bold mt-4">5. Intellectual property</h2>
    <p>We retain ownership of all intellectual property, know-how, tools, frameworks, code libraries, templates and materials we create or use, including preliminary concepts and anything not selected. On full payment of all amounts owing for a project, we grant you a licence (or, for final deliverables created specifically for you, assign ownership) to use those final deliverables for their intended purpose. Until full payment is received, all deliverables remain our property and any licence is suspended. Third-party materials, open-source components, fonts, stock assets and our pre-existing IP remain owned by their respective owners and are licensed, not sold. Editable source files, working files and design files are not included unless expressly agreed and paid for. We may showcase the work and your business name/logo in our portfolio, case studies and marketing.</p>

    <h2 class="h5 fw-bold mt-4">6. No guarantee of results</h2>
    <p>Search rankings, traffic, leads, sales, engagement and revenue depend on many factors outside our control, including third-party platforms and algorithms, your market and your own actions. We do not warrant or guarantee any particular result, ranking or outcome, and any timeframes or projections are estimates only.</p>

    <h2 class="h5 fw-bold mt-4">7. Warranties &amp; limitation of liability</h2>
    <p>We provide our services with due care and skill. Nothing in these Terms excludes, restricts or modifies any guarantee, right or remedy you have under the Australian Consumer Law that cannot lawfully be excluded. Where we are permitted to limit our liability, our liability for a failure to comply with a consumer guarantee is limited, at our option, to re-supplying the service or paying the cost of having it re-supplied. To the maximum extent permitted by law: (a) our total aggregate liability arising out of or in connection with the services is capped at the total fees you paid to us for the specific service in the three months before the event giving rise to the claim; and (b) we are not liable for any indirect, special or consequential loss, or for loss of profit, revenue, data, goodwill or business opportunity, however arising.</p>

    <h2 class="h5 fw-bold mt-4">8. Cancellation &amp; termination</h2>
    <p>You may cancel recurring services with at least 30 days' written notice; fees for the current and notice period remain payable and are non-refundable. We may suspend or terminate any service immediately if you breach these Terms, fail to pay, or act unlawfully or abusively. On termination for any reason, all amounts for work performed and the current billing cycle become immediately due, and any licence to use unpaid deliverables ends. Cancellation and refunds are also governed by our <a href="<?= route('legal.refund') ?>" target="_blank" rel="noopener">Refund &amp; Cancellation Policy</a>.</p>

    <h2 class="h5 fw-bold mt-4">9. Indemnity</h2>
    <p>You indemnify us and our personnel against all claims, losses, liabilities, costs and expenses arising from your content, materials or instructions, your breach of these Terms, or your use of the services in breach of any law or third-party right.</p>

    <h2 class="h5 fw-bold mt-4">10. Subcontracting, third parties &amp; non-solicitation</h2>
    <p>We may use subcontractors and third-party services (hosting, domains, analytics, social and payment platforms) to deliver the services. We are not responsible for the acts, outages or changes of those third parties, though we will use reasonable efforts to assist. During our engagement and for 12 months afterwards, you must not solicit or engage our staff or contractors except through us.</p>

    <h2 class="h5 fw-bold mt-4">11. Confidentiality &amp; force majeure</h2>
    <p>Each party will keep the other's confidential information confidential. We are not liable for any delay or failure caused by events beyond our reasonable control.</p>

    <h2 class="h5 fw-bold mt-4">12. Privacy</h2>
    <p>We handle personal information in accordance with our <a href="<?= route('legal.privacy') ?>" target="_blank" rel="noopener">Privacy Policy</a> and the Privacy Act 1988 (Cth).</p>

    <h2 class="h5 fw-bold mt-4">13. Variation, governing law &amp; general</h2>
    <p>We may amend these Terms from time to time by publishing the updated version; continued use of our services constitutes acceptance. These Terms are governed by the laws of the State in which we principally carry on business, and the parties submit to the non-exclusive jurisdiction of its courts. If any provision is unenforceable it is severed without affecting the rest. A failure to enforce a right is not a waiver of it. These Terms are the entire agreement between us regarding the services.</p>

    <h2 class="h5 fw-bold mt-4">14. Contact</h2>
    <p>Questions about these Terms? Email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
</div></div>
<?php $this->endSection(); ?>
