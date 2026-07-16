<?php $this->extends('layouts.admin'); ?>
<?php $this->section('content'); ?>

<div class="card" style="max-width:900px">
    <div class="card-body">
        <?php if (! $available): ?>
            <div class="alert alert-warning mb-3">
                <i class="bi bi-exclamation-triangle"></i> The AI assistant isn't switched on yet. Add <code>ANTHROPIC_API_KEY</code> to your <code>.env</code> to enable it.
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge text-bg-dark"><i class="bi bi-stars"></i> AI Assistant</span>
            <span class="text-muted small">Ask about clients, invoices, money or activity — I read live data. I can also propose changes for you to confirm.</span>
        </div>

        <div id="asstLog" style="height:460px;overflow-y:auto;background:#f7f8fb;border:1px solid #e7eaf3;border-radius:12px;padding:1rem;display:flex;flex-direction:column;gap:.6rem"></div>

        <form id="asstForm" class="d-flex gap-2 mt-3">
            <input id="asstInput" type="text" class="form-control" placeholder="e.g. Which invoices are overdue, and by how much?" autocomplete="off" <?= $available ? '' : 'disabled' ?>>
            <button class="btn btn-brand" type="submit" <?= $available ? '' : 'disabled' ?>><i class="bi bi-send"></i></button>
        </form>
        <div class="small text-muted mt-2">Try: “Show me a snapshot of the business”, “What does Demo Client owe?”, “Add $25 API credit to client 1 as a goodwill gesture”.</div>
    </div>
</div>

<style>
    .asst-msg { max-width: 88%; padding: .55rem .85rem; border-radius: 14px; font-size: .92rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; }
    .asst-me { align-self: flex-end; background: var(--brand, #FF6A00); color: #fff; border-bottom-right-radius: 4px; }
    .asst-ai { align-self: flex-start; background: #fff; border: 1px solid #e7eaf3; color: #1f2637; border-bottom-left-radius: 4px; }
    .asst-typing { align-self: flex-start; color: #8a92a6; font-size: .85rem; }
    .asst-action { align-self: flex-start; max-width: 88%; background: #fff8f0; border: 1px solid #ffd8b0; border-radius: 12px; padding: .7rem .85rem; }
</style>

<script>
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var log = document.getElementById('asstLog');
    var form = document.getElementById('asstForm');
    var input = document.getElementById('asstInput');
    var history = [];
    var busy = false;

    greet();
    // JSON-encoded, not e(): HTML entities aren't decoded inside <script>.
    const BRAND = <?= json_encode((string) config('company.brand_name'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    function greet() { bubble('ai', "G'day! I'm your " + BRAND + " admin assistant. Ask me about clients, invoices, outstanding balances or recent activity — I'll pull live data. I can also propose changes (like adjusting a client's credit) for you to confirm."); }

    form.addEventListener('submit', function (e) { e.preventDefault(); send(); });

    function send() {
        if (busy) return;
        var text = input.value.trim();
        if (!text) return;
        input.value = '';
        bubble('me', text);
        history.push({ role: 'user', content: text });
        busy = true;
        var t = typing();
        fetch('<?= route('admin.assistant.message') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ history: history })
        }).then(function (r) { return r.json(); }).then(function (res) {
            t.remove(); busy = false;
            if (!res.ok) { bubble('ai', '⚠️ ' + (res.error || 'Something went wrong.')); return; }
            bubble('ai', res.reply || '(no answer)');
            history.push({ role: 'assistant', content: res.reply || '' });
            if (res.action) { actionCard(res.action); }
        }).catch(function () { t.remove(); busy = false; bubble('ai', '⚠️ Network error — please try again.'); });
    }

    function actionCard(action) {
        var card = document.createElement('div');
        card.className = 'asst-action';
        var p = document.createElement('div');
        p.className = 'mb-2 fw-semibold'; p.textContent = '⚡ ' + action.summary;
        var btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-warning'; btn.textContent = 'Confirm & apply';
        var cancel = document.createElement('button');
        cancel.className = 'btn btn-sm btn-link text-muted'; cancel.textContent = 'Dismiss';
        cancel.onclick = function () { card.remove(); };
        btn.onclick = function () {
            btn.disabled = true; btn.textContent = 'Applying…';
            var body = new URLSearchParams(); body.append('token', action.token);
            fetch('<?= route('admin.assistant.execute') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': meta ? meta.content : '', 'X-Requested-With': 'XMLHttpRequest' },
                body: body.toString()
            }).then(function (r) { return r.json(); }).then(function (res) {
                card.remove();
                bubble('ai', res.ok ? ('✅ ' + res.message) : ('⚠️ ' + (res.error || 'Could not apply.')));
            }).catch(function () { btn.disabled = false; btn.textContent = 'Confirm & apply'; });
        };
        card.appendChild(p); card.appendChild(btn); card.appendChild(cancel);
        log.appendChild(card); log.scrollTop = log.scrollHeight;
    }

    function bubble(who, text) {
        var d = document.createElement('div');
        d.className = 'asst-msg ' + (who === 'me' ? 'asst-me' : 'asst-ai');
        d.textContent = text; log.appendChild(d); log.scrollTop = log.scrollHeight;
        return d;
    }
    function typing() { var d = document.createElement('div'); d.className = 'asst-typing'; d.textContent = 'Thinking…'; log.appendChild(d); log.scrollTop = log.scrollHeight; return d; }
})();
</script>
<?php $this->endSection(); ?>
