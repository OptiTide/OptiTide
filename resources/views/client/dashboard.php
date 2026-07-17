<?php
$this->extends('layouts.portal');

$quotesOn = \App\Support\Features::enabled('quotes');
$meetingsOn = \App\Support\Features::enabled('meetings');

// "Needs your attention" is the whole point of the page — build it first so the
// rest of the dashboard can stay quiet when nothing is waiting on the client.
$todo = [];

if ($next_due) {
    $isOverdue = $next_due['status'] === \App\Models\Invoice::STATUS_OVERDUE;
    $todo[] = [
        'icon'  => $isOverdue ? 'bi-exclamation-octagon-fill' : 'bi-receipt',
        'tone'  => $isOverdue ? 'danger' : 'brand',
        'title' => $isOverdue ? 'Payment overdue' : 'Invoice to pay',
        'text'  => $next_due['number'] . ' · ' . \App\Models\Invoice::balance($next_due)->format()
            . ' · due ' . date('j M Y', strtotime((string) $next_due['due_date'])),
        'cta'   => 'Pay Now',
        'href'  => route('portal.invoices.show', ['id' => $next_due['id']]),
    ];
}

foreach ($open_quotes as $quote) {
    $todo[] = [
        'icon'  => 'bi-file-earmark-text',
        'tone'  => 'brand',
        'title' => 'Quote awaiting your decision',
        'text'  => $quote['number'] . ' · ' . \App\Models\Quote::total($quote)->format()
            . ($quote['expires_at'] ? ' · valid until ' . date('j M Y', strtotime((string) $quote['expires_at'])) : ''),
        'cta'   => 'Review Quote',
        'href'  => route('portal.quotes.show', ['id' => $quote['id']]),
    ];
}

foreach ($reply_tickets as $ticket) {
    $todo[] = [
        'icon'  => 'bi-chat-left-dots',
        'tone'  => 'brand',
        'title' => 'We\'re waiting on your reply',
        'text'  => $ticket['number'] . ' · ' . $ticket['subject'],
        'cta'   => 'Reply',
        'href'  => route('portal.support.show', ['id' => $ticket['id']]),
    ];
}
?>
<?php $this->section('content'); ?>

<?php if ($is_new): ?>
    <?php // A brand-new client has nothing to summarise, so the page explains the
          // portal and hands them the first action instead of a wall of zeroes. ?>
    <div class="card border-0 mb-4" style="background:linear-gradient(120deg,var(--navy-2),var(--navy));color:#fff">
        <div class="card-body">
            <div class="h5 fw-bold mb-1">👋 Welcome to Your Client Portal</div>
            <p class="mb-3" style="color:#cbd2e0">Everything for your project lives here — your invoices, your progress, your meetings and your requests. Not sure where to start? Pick one:</p>
            <div class="row g-2">
                <?php
                $guide = [
                    ['bi-bag-plus', 'Order a Service', 'Web, SEO, social or hosting', route('portal.order.index')],
                    ['bi-kanban', 'Track Your Project', 'See progress live', route('portal.project')],
                    ['bi-receipt', 'View & Pay Invoices', 'Every invoice, and how to pay it', route('portal.invoices.index')],
                ];
                if ($meetingsOn) {
                    $guide[] = ['bi-calendar-event', 'Book a Meeting', 'Pick a time that suits you', route('portal.meetings')];
                }
                $guide[] = ['bi-life-preserver', 'Get Help', 'Open a request any time', route('portal.support.index')];
                if (\App\Support\Features::enabled('affiliate')) {
                    // CommissionService pays ONE acquisition commission per referred
                    // client, on their first paid invoice — not on every referral.
                    $guide[] = ['bi-gift', 'Refer & Earn', 'Earn a % when they first pay', route('portal.refer')];
                }
                foreach ($guide as [$icon, $t, $d, $href]):
                ?>
                    <div class="col-sm-6 col-lg-4">
                        <a href="<?= $href ?>" class="d-flex align-items-start gap-2 p-3 rounded text-decoration-none h-100" style="background:rgba(255,255,255,.07);color:#fff;min-height:44px">
                            <i class="bi <?= $icon ?>" style="color:var(--brand);font-size:1.1rem"></i>
                            <span><strong><?= e($t) ?></strong><span class="d-block small" style="color:#b9c0d4"><?= e($d) ?></span></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center py-5">
            <div class="stat-icon mx-auto mb-3"><i class="bi bi-rocket-takeoff"></i></div>
            <h2 class="h5 fw-bold mb-1">Let's Get You Started</h2>
            <p class="text-muted mb-4">You don't have any services with us yet. Pick a package and we'll get moving — or tell us what you need and we'll put a quote together.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= route('portal.order.index') ?>" class="btn btn-brand"><i class="bi bi-bag-plus"></i> Order a Service</a>
                <a href="<?= route('portal.support.create') ?>" class="btn btn-outline-brand"><i class="bi bi-chat-left-dots"></i> Ask Us a Question</a>
            </div>
        </div>
    </div>
<?php else: ?>

    <?php if ($todo !== []): ?>
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-bell text-brand"></i> Needs Your Attention
                <span class="badge badge-soft ms-auto"><?= count($todo) ?></span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($todo as $item): ?>
                    <div class="list-group-item d-flex flex-wrap align-items-center gap-2 py-3">
                        <i class="bi <?= $item['icon'] ?> fs-5 <?= $item['tone'] === 'danger' ? 'text-danger' : 'text-brand' ?>"></i>
                        <div class="flex-grow-1" style="min-width:12rem">
                            <div class="fw-semibold <?= $item['tone'] === 'danger' ? 'text-danger' : '' ?>"><?= e($item['title']) ?></div>
                            <div class="text-muted small"><?= e($item['text']) ?></div>
                        </div>
                        <a href="<?= $item['href'] ?>" class="btn btn-sm <?= $item['tone'] === 'danger' ? 'btn-danger' : 'btn-brand' ?> ms-auto"><?= e($item['cta']) ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>You're all up to date — nothing needs your attention right now.</span>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            [
                'label' => 'Balance Owing',
                'value' => $outstanding->format(),
                'icon'  => 'bi-hourglass-split',
                'href'  => route('portal.invoices.index') . '?status=sent',
                'note'  => $overdue > 0
                    ? $overdue . ' overdue'
                    : ($outstanding->isZero() ? 'Nothing to pay' : null),
                'tone'  => $overdue > 0 ? 'danger' : null,
            ],
            [
                'label' => 'Active Services',
                'value' => (string) $services,
                'icon'  => 'bi-grid',
                'href'  => route('portal.services'),
                'note'  => $project_open > 0 ? $project_open . ' in progress' : null,
            ],
            [
                'label' => 'Open Requests',
                'value' => (string) $open_tickets,
                'icon'  => 'bi-life-preserver',
                'href'  => route('portal.support.index'),
                'note'  => $open_tickets === 0 ? 'Nothing open' : null,
            ],
            [
                'label' => 'Paid to Date',
                'value' => $paid->format(),
                'icon'  => 'bi-check2-circle',
                'href'  => route('portal.invoices.index') . '?status=paid',
                'note'  => null,
            ],
        ];
        foreach ($cards as $card):
            ?>
            <div class="col-6 col-xl-3">
                <a href="<?= $card['href'] ?>" class="text-decoration-none text-reset">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon"><i class="bi <?= $card['icon'] ?>"></i></div>
                            <div class="min-w-0">
                                <div class="stat-value money"><?= e($card['value']) ?></div>
                                <div class="stat-label"><?= e($card['label']) ?></div>
                                <?php if ($card['note']): ?>
                                    <div class="small <?= ($card['tone'] ?? '') === 'danger' ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= e($card['note']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Your Project</span>
                    <a href="<?= route('portal.project') ?>" class="btn btn-sm btn-light">View Project</a>
                </div>
                <div class="card-body">
                    <?php if ($project_cards === []): ?>
                        <div class="text-center text-muted py-4">
                            <?php if ($project_done > 0): ?>
                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                Everything we're working on for you is finished. Ready for the next thing?
                                <div class="mt-3"><a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand">Order a Service</a></div>
                            <?php else: ?>
                                <i class="bi bi-kanban fs-3 d-block mb-2"></i>
                                No work is under way yet. Once you order a service, your progress shows up here — you'll see each step as we move through it.
                                <div class="mt-3"><a href="<?= route('portal.order.index') ?>" class="btn btn-sm btn-brand">Order a Service</a></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($project_cards as $card): ?>
                            <?php $bar = $card['_progress']; ?>
                            <div class="pj-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div class="fw-semibold"><?= e($card['title']) ?></div>
                                    <span class="badge badge-soft"><?= e($card['_status']) ?></span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-calendar-event"></i>
                                    Due <?= $card['due_date'] ? e(date('j M Y', strtotime((string) $card['due_date']))) : 'not scheduled yet' ?>
                                </div>
                                <?php if ($bar['total'] > 0): ?>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <div class="progress flex-grow-1" style="height:6px" role="progressbar" aria-valuenow="<?= $bar['pct'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progress on <?= e($card['title']) ?>">
                                            <div class="progress-bar bg-success" style="width:<?= $bar['pct'] ?>%"></div>
                                        </div>
                                        <span class="text-muted small text-nowrap"><?= $bar['done'] ?>/<?= $bar['total'] ?> done</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($project_open > count($project_cards)): ?>
                            <a href="<?= route('portal.project') ?>" class="small">+ <?= $project_open - count($project_cards) ?> more under way</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent Invoices</span>
                    <a href="<?= route('portal.invoices.index') ?>" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Invoice</th><th class="d-none d-sm-table-cell">Issued</th><th>Status</th><th class="text-end">Balance</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php if (! $recent): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet. When you order a service, your invoice appears here.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recent as $invoice): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($invoice['number']) ?></td>
                                    <td class="d-none d-sm-table-cell"><?= e($invoice['issue_date']) ?></td>
                                    <td><span class="badge text-bg-<?= \App\Models\Invoice::STATUS_COLORS[$invoice['status']] ?>"><?= e(\App\Models\Invoice::STATUSES[$invoice['status']]) ?></span></td>
                                    <td class="text-end money"><?= e(\App\Models\Invoice::balance($invoice)->format()) ?></td>
                                    <td class="text-end"><a href="<?= route('portal.invoices.show', ['id' => $invoice['id']]) ?>" class="btn btn-sm <?= \App\Models\Invoice::isPayable($invoice) ? 'btn-brand' : 'btn-light' ?>"><?= \App\Models\Invoice::isPayable($invoice) ? 'Pay' : 'View' ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <?php if ($meetingsOn): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Next Meeting</span>
                        <a href="<?= route('portal.meetings') ?>" class="btn btn-sm btn-light">All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($next_meeting): ?>
                            <div class="fw-semibold"><?= e($next_meeting['title']) ?></div>
                            <div class="text-muted small mb-3"><i class="bi bi-calendar-event"></i> <?= e(date('l j F, g:ia', strtotime((string) $next_meeting['meeting_at']))) ?></div>
                            <?php if (! empty($next_meeting['location']) && str_starts_with((string) $next_meeting['location'], 'http')): ?>
                                <a href="<?= e($next_meeting['location']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-brand w-100"><i class="bi bi-camera-video"></i> Join Meeting</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted small mb-3">Nothing booked. Want to talk something through? Pick a time that suits you and we'll confirm it.</p>
                            <a href="<?= route('portal.meetings') ?>" class="btn btn-sm btn-brand w-100"><i class="bi bi-calendar-plus"></i> Book a Meeting</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-header">Quick Actions</div>
                <div class="list-group list-group-flush">
                    <a href="<?= route('portal.order.index') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3"><i class="bi bi-bag-plus text-brand"></i> Order a Service</a>
                    <a href="<?= route('portal.invoices.index') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3"><i class="bi bi-receipt text-brand"></i> View &amp; Pay Invoices</a>
                    <a href="<?= route('portal.support.create') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3"><i class="bi bi-life-preserver text-brand"></i> Open a Request</a>
                    <a href="<?= route('portal.profile.edit') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3"><i class="bi bi-person text-brand"></i> Update Your Details</a>
                </div>
            </div>

            <?php if ($next_renewal): ?>
                <div class="card">
                    <div class="card-header">Next Renewal</div>
                    <div class="card-body">
                        <div class="fw-semibold"><?= e($next_renewal['label']) ?></div>
                        <div class="text-muted small">Renews <?= e(date('j M Y', strtotime((string) $next_renewal['next_invoice_date']))) ?> · <?= e(money((int) $next_renewal['price_cents'], $next_renewal['currency'])->format()) ?> / <?= e(substr($next_renewal['interval'] ?? 'mo', 0, 2)) ?></div>
                        <a href="<?= route('portal.services') ?>" class="small">Manage your services</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>
