<?php
// Feature-gated at the partial, not at each of the four layouts that insert it,
// so there is exactly one place the widget can be switched off.
if (! \App\Support\Features::enabled('live_chat')) {
    return;
}

// With AI replies off only the team answers, so the "AI-powered" badge and the
// instant-answer promise would both be untrue — say what actually happens.
$otcAi = \App\Support\Features::enabled('ai_chat');
?>
<!-- Live chat widget -->
<button type="button" id="otcBubble" class="otc-bubble" aria-label="Chat with us"><i class="bi bi-chat-dots-fill"></i></button>
<div id="otcPanel" class="otc-panel" role="dialog" aria-label="Live chat">
    <div class="otc-header">
        <div>
            <div class="otc-title"><?= e(config('company.brand_name')) ?> <?= $otcAi ? 'Assistant' : 'Chat' ?><?php if ($otcAi): ?> <span style="font-size:.62rem;background:rgba(255,255,255,.18);padding:1px 6px;border-radius:6px;vertical-align:middle">AI</span><?php endif; ?></div>
            <div class="otc-sub"><span class="otc-dot"></span> <?= $otcAi ? 'AI-powered · a human can join anytime' : 'Our team will reply here' ?></div>
        </div>
        <button type="button" id="otcClose" class="otc-x" aria-label="Close chat"><i class="bi bi-x-lg"></i></button>
    </div>
    <div id="otcMsgs" class="otc-msgs"></div>
    <form id="otcForm" class="otc-input">
        <input id="otcInput" type="text" autocomplete="off" placeholder="Type your message…" maxlength="2000">
        <button type="submit" aria-label="Send"><i class="bi bi-send-fill"></i></button>
    </form>
</div>
<style>
    .otc-bubble { position: fixed; right: 20px; bottom: 20px; z-index: 1080; width: 60px; height: 60px; border-radius: 50%;
        border: 0; background: var(--brand, #FF6A00); color: #fff; font-size: 1.5rem; box-shadow: 0 10px 26px rgba(13,21,48,.28); cursor: pointer; }
    .otc-bubble:hover { filter: brightness(1.05); }
    .otc-panel { position: fixed; right: 20px; bottom: 92px; z-index: 1080; width: 360px; max-width: calc(100vw - 32px); height: 520px; max-height: calc(100vh - 130px);
        background: #fff; border-radius: 16px; box-shadow: 0 24px 60px rgba(13,21,48,.28); display: none; flex-direction: column; overflow: hidden; }
    .otc-panel.otc-open { display: flex; }
    .otc-header { background: linear-gradient(135deg, #1A223F, #0D1530); color: #fff; padding: .9rem 1rem; display: flex; align-items: center; justify-content: space-between; }
    .otc-title { font-weight: 700; }
    .otc-sub { font-size: .76rem; color: #b9c0d4; display: flex; align-items: center; gap: .35rem; }
    .otc-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block; }
    .otc-x { background: none; border: 0; color: #b9c0d4; font-size: 1rem; cursor: pointer; }
    .otc-msgs { flex: 1 1 auto; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .5rem; background: #f7f8fb; }
    .otc-msg { max-width: 82%; padding: .55rem .8rem; border-radius: 14px; font-size: .9rem; line-height: 1.45; white-space: pre-wrap; word-wrap: break-word; }
    .otc-them { align-self: flex-start; background: #fff; border: 1px solid #e7eaf3; color: #1f2637; border-bottom-left-radius: 4px; }
    .otc-me { align-self: flex-end; background: var(--brand, #FF6A00); color: #fff; border-bottom-right-radius: 4px; }
    .otc-input { display: flex; gap: .4rem; padding: .6rem; border-top: 1px solid #eef1f7; background: #fff; }
    .otc-input input { flex: 1 1 auto; border: 1px solid #dfe3ec; border-radius: 20px; padding: .5rem .9rem; font-size: .9rem; outline: none; }
    .otc-input input:focus { border-color: var(--brand, #FF6A00); }
    .otc-input button { border: 0; background: var(--brand, #FF6A00); color: #fff; width: 40px; border-radius: 50%; cursor: pointer; }
    @media (max-width: 480px) { .otc-panel { right: 8px; bottom: 84px; } }
</style>
<script>
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = null, lastId = 0, poller = null, started = false;
    var bubble, panel, msgs, form, input;

    document.addEventListener('DOMContentLoaded', function () {
        bubble = document.getElementById('otcBubble');
        panel = document.getElementById('otcPanel');
        msgs = document.getElementById('otcMsgs');
        form = document.getElementById('otcForm');
        input = document.getElementById('otcInput');
        token = getCookie('ot_chat');
        bubble.addEventListener('click', toggle);
        document.getElementById('otcClose').addEventListener('click', toggle);
        form.addEventListener('submit', function (e) { e.preventDefault(); send(); });
    });

    function toggle() {
        var open = panel.classList.toggle('otc-open');
        if (open) { input.focus(); if (!started) { started = true; init(); } }
    }
    function init() {
        if (token) { poll(true); startPolling(); }
        else { api('POST', '<?= route('chat.start') ?>', {}, function (res) { if (res && res.token) { token = res.token; render(res.messages || []); startPolling(); } }); }
    }
    function send() {
        var body = input.value.trim(); if (!body) return;
        input.value = '';
        var bubble = appendLocal('visitor', body);
        api('POST', '<?= route('chat.message') ?>', { body: body },
            function (res) {
                if (res && res.ok) { if (res.last_id) lastId = Math.max(lastId, res.last_id); setTimeout(function () { poll(false); }, 800); }
                else if (bubble && bubble.parentNode) { bubble.parentNode.removeChild(bubble); }
            },
            function () { if (bubble && bubble.parentNode) { bubble.parentNode.removeChild(bubble); } });
    }
    function poll(initial) {
        if (!token) return;
        fetch('<?= route('chat.poll') ?>?after=' + (initial ? 0 : lastId), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Chat-Token': token } })
            .then(function (r) { return r.json(); })
            .then(function (res) { if (res && res.messages && res.messages.length) { if (initial) render(res.messages); else res.messages.forEach(appendMsg); } })
            .catch(function () {});
    }
    function startPolling() { if (!poller) poller = setInterval(function () { poll(false); }, 4000); }
    function render(list) { msgs.innerHTML = ''; lastId = 0; list.forEach(appendMsg); }
    function appendMsg(m) { lastId = Math.max(lastId, m.id || 0); appendLocal(m.sender, m.body); }
    function appendLocal(sender, body) {
        var d = document.createElement('div');
        d.className = 'otc-msg ' + (sender === 'visitor' ? 'otc-me' : 'otc-them');
        d.textContent = body;
        msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
        return d;
    }
    function api(method, url, data, cb, onErr) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        fetch(url, { method: method, headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': meta ? meta.content : '', 'X-Chat-Token': token || '', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString() })
            .then(function (r) { return r.json(); }).then(cb).catch(function () { if (onErr) onErr(); });
    }
    function getCookie(n) { var v = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'); return v ? v.pop() : null; }
})();
</script>
