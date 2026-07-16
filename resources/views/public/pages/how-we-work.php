<?php $this->extends('layouts.marketing'); ?>
<?php $this->section('content'); ?>
<?php
use App\Support\Company;

$company = config('company');
$brand = $company['brand_name'];
$tz = Company::timezone();
$abbr = Company::timezoneAbbr();
$offset = Company::utcOffsetLabel();
$now = Company::officeNow();
?>

<header class="mk-page-hero">
    <div class="mk-container">
        <nav class="mk-crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <i class="bi bi-chevron-right"></i> <span>How We Work</span></nav>
        <span class="mk-eyebrow" style="color:var(--brand-bright)">How We Work</span>
        <h1>You Sleep. Your Project Doesn't.</h1>
        <p class="mk-lead">We're based in Western Australia and we work <?= e($abbr) ?> hours — often well into the evening. For most of our clients that means the same thing every time: send it through, go to bed, and it's waiting for you in the morning.</p>
        <div class="mk-hero-cta">
            <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Start a Project</a>
            <a href="#clock" class="btn btn-ghost btn-lg">What Time Is It There?</a>
        </div>
    </div>
</header>

<!-- Live clock + visitor comparison -->
<section class="mk-section" id="clock">
    <div class="mk-container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-9">
                <div class="mk-clock-card"
                     data-office-tz="<?= e($tz->getName()) ?>"
                     data-office-abbr="<?= e($abbr) ?>"
                     data-brand="<?= e($brand) ?>">
                    <div class="mk-clock-row">
                        <div class="mk-clock">
                            <div class="mk-clock-label"><i class="bi bi-buildings"></i> Our office · <?= e($abbr) ?> (<?= e($offset) ?>)</div>
                            <div class="mk-clock-time" id="officeClock"><?= e($now->format('g:i A')) ?></div>
                            <div class="mk-clock-day" id="officeDay"><?= e($now->format('l, j F')) ?></div>
                        </div>
                        <div class="mk-clock-vs" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></div>
                        <div class="mk-clock">
                            <div class="mk-clock-label"><i class="bi bi-person"></i> <span id="yourTzLabel">Your time</span></div>
                            <div class="mk-clock-time" id="yourClock">—</div>
                            <div class="mk-clock-day" id="yourDay">&nbsp;</div>
                        </div>
                    </div>
                    <!-- Filled in by JS from the visitor's own clock. Honest by
                         construction: someone in our timezone is told so. -->
                    <p class="mk-clock-verdict" id="tzVerdict">
                        <noscript>We work <?= e($abbr) ?> hours (<?= e($offset) ?>) from Western Australia.</noscript>
                    </p>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">
                    Our working hours: <strong><?= e($company['hours']) ?></strong>. We're not a 24/7 call centre — we're a small team that tends to work late.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- The loop -->
<section class="mk-section mk-section--alt">
    <div class="mk-container">
        <div class="text-center mb-5">
            <span class="mk-eyebrow">The Overnight Loop</span>
            <h2 class="mk-h2">How a day actually goes</h2>
            <p class="text-muted">This is the bit clients tell us they like most — and it's just a happy accident of where we sit on the map.</p>
        </div>
        <div class="row g-4">
            <?php
            $steps = [
                ['1', 'bi-send', 'You send feedback', 'Some time during your day you fire off a note: change this, add that, here\'s the copy you asked for.'],
                ['2', 'bi-moon-stars', 'We work while you\'re offline', 'Your evening is our working evening. We pick it up, do the work, and push it to your staging site.'],
                ['3', 'bi-cup-hot', 'You review it with your coffee', 'You open your laptop the next morning and it\'s already done, with a note explaining what changed.'],
                ['4', 'bi-arrow-repeat', 'Repeat — and it adds up', 'One round-trip a day instead of one every few days. Over a project that\'s the difference between weeks and months.'],
            ];
            foreach ($steps as [$n, $icon, $title, $copy]): ?>
                <div class="col-md-6 col-lg-3">
                    <article class="mk-feature h-100">
                        <div class="mk-feature-icon"><i class="bi <?= e($icon) ?>"></i></div>
                        <div class="mk-step-num mb-2">Step <?= e($n) ?></div>
                        <h3><?= e($title) ?></h3>
                        <p><?= e($copy) ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Honesty section -->
<section class="mk-section">
    <div class="mk-container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <span class="mk-eyebrow">Straight Up</span>
                <h2 class="mk-h2">When this <em>doesn't</em> help you</h2>
                <p class="text-muted">We'd rather tell you than have you find out.</p>
                <ul class="mk-checklist mt-3">
                    <li><i class="bi bi-dash-circle" style="color:var(--muted)"></i> <strong>If you're in WA too</strong>, there's no overnight magic — we're awake at the same time as you. You get something better instead: we're in your timezone, so you can just call us.</li>
                    <li><i class="bi bi-dash-circle" style="color:var(--muted)"></i> <strong>Working late isn't the same as 24/7.</strong> We're a small team. If you send something at 3am our time, it waits until we're up.</li>
                    <li><i class="bi bi-dash-circle" style="color:var(--muted)"></i> <strong>Big decisions still need a conversation.</strong> Async is brilliant for iterating; it's worse for "should we rebuild the whole thing". For those we book a call.</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <span class="mk-eyebrow">Either Way</span>
                <h2 class="mk-h2">What you always get</h2>
                <ul class="mk-checklist mt-3">
                    <li><i class="bi bi-check2-circle"></i> <strong>One place for everything.</strong> Your portal has your project, invoices, files and support history — no digging through email.</li>
                    <li><i class="bi bi-check2-circle"></i> <strong>An answer any hour.</strong> Our live chat assistant answers instantly, day or night, and hands you to a human when it should.</li>
                    <li><i class="bi bi-check2-circle"></i> <strong>Australian hours, Australian team.</strong> Not a call centre in another hemisphere reading a script.</li>
                    <li><i class="bi bi-check2-circle"></i> <strong>A written trail.</strong> Async work means decisions get written down, so nobody's relying on what someone remembers from a call.</li>
                </ul>
                <a href="<?= route('pages.contact') ?>" class="btn btn-brand mt-3">Talk to Us <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="mk-cta-band">
    <div class="mk-container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2>Send it tonight. Review it tomorrow.</h2>
            <p>Tell us what you need and we'll come back with honest advice and a clear quote — usually by the time you're next at your desk.</p>
        </div>
        <a href="<?= route('pages.contact') ?>" class="btn btn-brand btn-lg">Get a Free Quote <i class="bi bi-arrow-right"></i></a>
    </div>
</section>

<script>
(function () {
    var card = document.querySelector('.mk-clock-card');
    if (!card) return;
    var officeTz = card.dataset.officeTz;
    var brand = card.dataset.brand;

    var yourTz;
    try { yourTz = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) { yourTz = null; }

    function partsIn(tz, date) {
        // Read the wall-clock in a timezone without any date maths of our own.
        var f = new Intl.DateTimeFormat('en-AU', {
            timeZone: tz, hour: 'numeric', minute: '2-digit', hour12: true,
            weekday: 'long', day: 'numeric', month: 'long'
        });
        var out = {};
        f.formatToParts(date).forEach(function (p) { out[p.type] = p.value; });
        return {
            time: (out.hour || '') + ':' + (out.minute || '') + ' ' + (out.dayPeriod || '').toUpperCase(),
            day: (out.weekday || '') + ', ' + (out.day || '') + ' ' + (out.month || '')
        };
    }

    // Offset difference in MINUTES: compare the same instant read in both zones.
    // Minutes, not hours — Adelaide, Darwin, India and Nepal are half- and
    // quarter-hour offsets, and rounding them to whole hours tells the visitor
    // the wrong number.
    function offsetMinutes(tzA, tzB, date) {
        function asUtc(tz) {
            return new Date(date.toLocaleString('en-US', { timeZone: tz })).getTime();
        }
        return Math.round((asUtc(tzB) - asUtc(tzA)) / 60000);
    }

    /** 90 -> "1.5 hours", 60 -> "1 hour", 45 -> "45 minutes" */
    function gapLabel(mins) {
        if (mins < 60) return mins + ' minute' + (mins === 1 ? '' : 's');
        var hours = mins / 60;
        var text = Number.isInteger(hours) ? String(hours) : hours.toFixed(1).replace(/\.0$/, '');
        return text + ' hour' + (text === '1' ? '' : 's');
    }

    function render() {
        var now = new Date();
        var office = partsIn(officeTz, now);
        document.getElementById('officeClock').textContent = office.time;
        document.getElementById('officeDay').textContent = office.day;

        if (!yourTz) return;
        var mine = partsIn(yourTz, now);
        document.getElementById('yourClock').textContent = mine.time;
        document.getElementById('yourDay').textContent = mine.day;
        document.getElementById('yourTzLabel').textContent = 'You · ' + yourTz.split('/').pop().replace(/_/g, ' ');

        var diff = offsetMinutes(officeTz, yourTz, now);
        var verdict = document.getElementById('tzVerdict');
        if (diff === 0) {
            // The caveat, stated plainly to the people it actually applies to.
            verdict.innerHTML = '<i class="bi bi-people-fill"></i> <strong>You\'re on the same clock as us.</strong> ' +
                'So no overnight turnaround for you — but you do get the better half of the deal: we\'re in your timezone, ' +
                'we work the same hours you do, and you can just pick up the phone.';
            verdict.className = 'mk-clock-verdict is-same';
        } else {
            var ahead = diff > 0;
            verdict.innerHTML = '<i class="bi bi-moon-stars-fill"></i> You\'re <strong>' + gapLabel(Math.abs(diff)) + ' ' +
                (ahead ? 'ahead of' : 'behind') + '</strong> us. ' +
                'While you\'re asleep tonight, we\'re still working — send your feedback before you log off and it\'ll be waiting for you in the morning.';
            verdict.className = 'mk-clock-verdict is-diff';
        }
    }

    render();
    setInterval(render, 30000);
})();
</script>
<?php $this->endSection(); ?>
