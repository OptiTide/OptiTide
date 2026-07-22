<?php
$this->extends('layouts.admin');
$isEdit = $page !== null;
$action = $isEdit ? route('admin.landing.update', ['id' => $page['id']]) : route('admin.landing.store');
$v = fn (string $k, $d = '') => e(old($k, $page[$k] ?? $d));
$faqs = $isEdit ? \App\Models\LandingPage::faqs($page) : [];
?>
<?php $this->section('content'); ?>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?><?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Content</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Page Heading (H1)</label>
                        <input type="text" name="title" value="<?= $v('title') ?>" class="form-control <?= has_error('title') ? 'is-invalid' : '' ?>" required placeholder="Web Design in Perth">
                        <?php if (error('title')): ?><div class="invalid-feedback"><?= e(error('title')) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= e(rtrim(config('app.url'), '/')) ?>/</span>
                            <input type="text" name="slug" value="<?= $v('slug') ?>" class="form-control" required placeholder="web-design-perth" autocapitalize="none">
                        </div>
                        <div class="form-text">Lowercase words separated by hyphens. Put the keyword in it. Changing this on a live page loses its ranking — treat it as permanent.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Intro <span class="text-muted small fw-normal">(the paragraph under the heading)</span></label>
                        <textarea name="intro" rows="3" class="form-control" maxlength="600"><?= $v('intro') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Body</label>
                        <textarea name="body" rows="18" class="form-control font-monospace" style="font-size:.85rem"><?= $v('body') ?></textarea>
                        <div class="form-text">
                            HTML. Allowed: headings (h2/h3), paragraphs, lists, links, images, tables, bold/italic.
                            Scripts and anything risky are stripped before the page renders, so paste freely.
                            <strong>Aim for 700+ words of genuinely useful content</strong> — thin pages don't rank.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>FAQs <span class="text-muted small fw-normal">— these become FAQ rich results in Google</span></span>
                    <button type="button" class="btn btn-sm btn-outline-brand" onclick="addFaq()"><i class="bi bi-plus-lg"></i> Add</button>
                </div>
                <div class="card-body" id="faqRows">
                    <?php foreach ($faqs as $f): ?>
                        <div class="row g-2 mb-2 faq-row">
                            <div class="col-md-5"><input type="text" name="faq_q[]" value="<?= e($f['q']) ?>" class="form-control form-control-sm" placeholder="Question"></div>
                            <div class="col-md-6"><textarea name="faq_a[]" rows="2" class="form-control form-control-sm" placeholder="Answer"><?= e($f['a']) ?></textarea></div>
                            <div class="col-md-1 d-grid"><button type="button" class="btn btn-sm btn-link text-danger" onclick="this.closest('.faq-row').remove()"><i class="bi bi-x-lg"></i></button></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($faqs === []): ?><p class="text-muted small mb-0" id="faqEmpty">No FAQs yet. Three or four real questions your customers ask is the cheapest way onto page one.</p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Search Engine</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Target keyword</label>
                        <input type="text" name="keyword" value="<?= $v('keyword') ?>" class="form-control" placeholder="web design perth">
                        <div class="form-text">The phrase this page is trying to win. One per page.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta title <span class="text-muted small fw-normal">(optional)</span></label>
                        <input type="text" name="meta_title" value="<?= $v('meta_title') ?>" class="form-control" maxlength="180" placeholder="Defaults to the heading">
                        <div class="form-text">Aim for under 60 characters so Google doesn't cut it off.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" rows="3" class="form-control" maxlength="320" placeholder="What someone sees under the link in Google."><?= $v('meta_description') ?></textarea>
                        <div class="form-text">150–160 characters is the sweet spot.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Location <span class="text-muted small fw-normal">(optional)</span></label>
                        <input type="text" name="location" value="<?= $v('location') ?>" class="form-control" placeholder="Perth">
                        <div class="form-text">Only set this where you genuinely serve — a false location claim is worse than no page.</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Publishing</div>
                <div class="card-body">
                    <label class="form-label">Service line <span class="text-muted small fw-normal">(optional)</span></label>
                    <select name="service_slug" class="form-select mb-1">
                        <option value="">— none —</option>
                        <?php foreach ($lines as $slug => $name): ?>
                            <option value="<?= e($slug) ?>" <?= ($page['service_slug'] ?? '') === $slug ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text mb-3">Shows this line's real prices in the sidebar, straight from your catalogue — so a page can't advertise a price checkout won't honour.</div>

                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-3">
                        <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft — not public</option>
                        <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published — live &amp; in sitemap</option>
                    </select>

                    <button class="btn btn-brand w-100"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save' : 'Create' ?></button>
                    <a href="<?= route('admin.landing.index') ?>" class="btn btn-link w-100">Back to all pages</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Built with DOM methods rather than innerHTML — nothing here is user content, but
// a template that assembles markup from strings is one edit away from being an
// injection point, and createElement simply removes the possibility.
function addFaq() {
    var empty = document.getElementById('faqEmpty');
    if (empty) { empty.remove(); }

    var row = document.createElement('div');
    row.className = 'row g-2 mb-2 faq-row';

    var qCol = document.createElement('div');
    qCol.className = 'col-md-5';
    var q = document.createElement('input');
    q.type = 'text'; q.name = 'faq_q[]'; q.className = 'form-control form-control-sm';
    q.placeholder = 'Question';
    qCol.appendChild(q);

    var aCol = document.createElement('div');
    aCol.className = 'col-md-6';
    var a = document.createElement('textarea');
    a.name = 'faq_a[]'; a.rows = 2; a.className = 'form-control form-control-sm';
    a.placeholder = 'Answer';
    aCol.appendChild(a);

    var xCol = document.createElement('div');
    xCol.className = 'col-md-1 d-grid';
    var x = document.createElement('button');
    x.type = 'button'; x.className = 'btn btn-sm btn-link text-danger'; x.textContent = '×';
    x.addEventListener('click', function () { row.remove(); });
    xCol.appendChild(x);

    row.appendChild(qCol); row.appendChild(aCol); row.appendChild(xCol);
    document.getElementById('faqRows').appendChild(row);
}
</script>
<?php $this->endSection(); ?>
