# OptiTide Implementation Roadmap

Tracks the build against the "System Architecture and Implementation Blueprint"
document, including where and why the blueprint was corrected. All package
choices below were verified against Packagist in July 2026.

## Blueprint corrections (verified July 2026)

| Blueprint said | Reality | Decision |
|---|---|---|
| Payoneer Checkout API | Requires a Hong Kong legal entity + US$20k/mo volume; **no recurring billing**; AUD settles via double FX | **Stripe Checkout + Billing** (laravel/cashier). Self-serve for AU ABN holders, native AUD settlement, BECS Direct Debit (1% capped at A$3.50) ideal for hosting subscriptions. Add Stripe Tax for GST |
| `ralphjsmit/laravel-filament-onboard` | Does not exist on Packagist (the real Ralph J. Smit plugin is commercial) | `spatie/laravel-onboard` (installed) + a panel middleware redirecting to a wizard page until complete |
| `axelvds/laravel-uptime-monitor-extended` | Auto-generated one-off, dead since Nov 2025, Laravel ≤11 only | `spatie/laravel-uptime-monitor` + small custom Filament widget |
| `gufy/cpanel-whm` | Dead since 2017 (Laravel 5) | Thin custom service on Laravel's `Http` client against WHM API 1 / cPanel UAPI with token auth |
| `mokhosh/filament-kanban` | Filament v3 only | `relaticle/flowforge` v4 (installed, Filament ^5) |
| Laravel "11/12/13" | Laravel 13 stable (Mar 2026), needs PHP ≥8.3 | Laravel 13 + Filament ^5.6 (5.0–5.3.5 conflict with L13) |

Verified-good blueprint picks: `creagia/laravel-sign-pad` (installed),
`dutchcodingcompany/filament-socialite` (installed), `spatie/laravel-backup`
(installed), `spatie/laravel-translatable` (installed),
`streats22/laragrape` v1.5 (real but ~4 stars / ~141 downloads — high risk,
pin minors, budget to fork), `notebrainslab/filament-email-templates` v2
(young, Unlayer-based, Filament 4/5), `hamzahassanm/laravel-social-auto-post`
v2.5 (active, 8 platforms), `brimham/backup-monitor` +
`brimham/filament-backup-monitor` (pre-1.0). Helpdesk: built custom (done) —
`daacreators/creators-ticketing` is the fallback if scope grows. Affiliate:
custom ledger (schema done); `pdazcom/laravel-referrals` only does attribution.

## Done (foundation)

- [x] Laravel 13 + Filament v5.6 scaffold, Vite 8 + Tailwind v4, git repo
- [x] Toolchain: php.ini with required extensions, Composer 2.10 at `~/.local/bin/composer.phar`
- [x] Full domain schema (21 migrations): products, orders (+items, +state
      transitions), invoices (+items), leads, form_schemas,
      client_submissions, helpdesk tickets (+messages), referrals
      (+commissions), blogs, social_posts, generated_artifacts
      (+mockup_annotations), chat, board_tasks; Cashier subscription tables;
      sign-pad signatures
- [x] Money value object + cast (integer cents, multi-currency, AUD default)
- [x] 8-stage CRM state machine with enforced transitions + audit trail
- [x] Catalog seeded (10 products per blueprint pricing) + 3 onboarding form schemas
- [x] Admin panel `/admin`: 10 resources incl. Orders with "Move stage"
      action (offers only legal transitions), dashboard stats widget
- [x] Client portal `/client`: registration enabled; own-records-only Orders
      (read-only, masked stages), Invoices (read-only, drafts hidden),
      Support tickets (create with first message)
- [x] RBAC: role enum + `canAccessPanel` + UserPolicy (VA cannot manage
      users); clients 403 on `/admin`
- [x] Pest suite (state machine incl. concurrency, money, panel access,
      scoping, masking, storefront, webhooks)
- [x] **Epic 1 — Public storefront**: home/services/detail pages (Tailwind
      v4, ACL footer), session cart (`App\Services\Cart`, one-time products
      only), Stripe Checkout via Cashier (`CheckoutController` creates a
      pending order + session; `StripeWebhookController` extends Cashier's,
      marks the order paid and issues a paid invoice in one transaction,
      idempotent), hosting subscription checkout
      (`SubscriptionCheckoutController`, needs `stripe_price_id` on the
      product), contact form → leads (honeypot + throttle). Guests
      authenticate through the client portal. Hardened by adversarial review
      (24 findings fixed): webhook **fails closed** without
      STRIPE_WEBHOOK_SECRET and verifies signatures; paid-order fulfilment is
      a compare-and-swap so redelivery can't duplicate invoices; async
      (BECS) payments wait for settlement; success page confirms with Stripe
      before clearing the cart; duplicate-subscription guard. **To go live**:
      set STRIPE_KEY/SECRET/WEBHOOK_SECRET in .env (the webhook returns 503
      until the secret is set), create Stripe Prices for the two hosting
      plans and fill `products.stripe_price_id`. GST via Stripe Tax and PDF
      invoices are Epic 5.

- [x] **Epic 2 — Digital contracts & e-signature**: `contracts` table +
      `products.requires_contract`. `ContractService` issues a service
      agreement when a paid order contains a contract-requiring product
      (hooked into the webhook fulfilment transaction) and a hosting
      agreement when a subscription is created (`CreateHostingContract`
      listener on Cashier's `WebhookHandled`). Signing uses
      `creagia/laravel-sign-pad` but through **our own** auth + per-record
      ownership route (`ContractSignatureController`), not the package's
      shared-token route; the flow is wrapped in a transaction so a PDF
      failure can't brick the contract (regression test included). Signed
      TCPDF PDFs (with IP + timestamp) stored on the local disk, downloadable
      by owner or staff. Filament: admin list+download, client
      own-records-only with sign/download actions and a pending-count nav
      badge. Custom Website + both hosting plans are flagged
      `requires_contract`. **Note**: `certify_documents` is off (no signing
      cert yet) — PDFs are generated but not cryptographically certified;
      add a cert file + set the config to enable. Verified end-to-end in a
      real browser (canvas → 10KB PDF). Hardened by adversarial review
      (7 findings fixed): PDF download restricted to owner-or-Admin (VAs can
      no longer enumerate every client's legal PDF); signature stamp moved to
      the correct signature column (visually verified); the package's
      shared-token `creagia/sign-pad` route neutralized; a unique
      `(model_type, model_id)` index on `signatures` closes the
      double-submit race; the hosting-agreement listener now gates on an
      active/trialing subscription (not `incomplete`).

- [x] **Epic 3 — Dynamic intake forms**: schema-driven per-order project
      brief. `Order::onboardingFormSchema()` resolves the `FormSchema` from the
      purchased product's `onboarding_form_key`; `needsIntake()` gates on
      paid + `pending_intake` + no submission + no unsigned agreement.
      `IntakeController` renders a dynamic Blade form (`SchemaFormBuilder`
      turns the fields JSON into validation rules), splits answers into
      `client_submissions.data` vs `.brand_assets` (files + colours), stores
      uploads to the private `local` disk under `intake_assets/{order}`, and
      advances the order `pending_intake → admin_review` in a transaction.
      Client Orders get a "Complete project brief" action; admin Orders get a
      "View brief" modal with colour swatches + auth-scoped asset downloads.
      Verified end-to-end in a real browser. Hardened by adversarial review
      (8 findings fixed): `needsIntake()` now requires `isPaid()` (an
      abandoned unpaid checkout could previously be briefed into the staff
      pipeline); the select validation uses `Rule::in()` so
      comma-containing options validate (the Custom Website brief was
      literally unsubmittable); intake gates on order state not past
      submissions (a VA can bounce an order back for a revised brief); the
      concurrent double-submit race redirects gracefully instead of 500ing;
      multi-product orders resolve to the most comprehensive schema; plus
      regression tests for multi-file uploads and the SVG attachment guard.
      **Deferred (documented choice)**:
      the blueprint's hard-lock first-login onboarding wizard
      (spatie/laravel-onboard) was NOT built — the per-order brief already
      collects logo/brand-colours/requirements, and a hard portal lock would
      block clients from their own orders/agreements; revisit as a soft
      dashboard checklist if desired.

- [x] **Epic 4 — AI mockup/logic pipeline** (the stealth AI CRM core):
      integrates the official `anthropic-ai/sdk` behind a `ClaudeClient`
      interface — real (`AnthropicClaudeClient`, opus-4-8, adaptive thinking +
      effort, refusal-aware) when `ANTHROPIC_API_KEY` is set, else a
      `FakeClaudeClient` producing labelled placeholder output so the flow runs
      keyless. `MockupPromptBuilder` injects the client's brief as XML brand
      constraints and enforces Tailwind v4 + anti-repetition rules;
      `LogicPromptBuilder` builds logic from the approved mockup. Queued
      `GenerateMockupJob` / `GenerateLogicJob` store `GeneratedArtifact`
      versions and drive the state machine. `PipelineService` orchestrates:
      generate → internal QA → client proof → logic gen → final QA → deliver,
      each a `transitionTo()` audit step. Untrusted HTML renders ONLY in
      `srcdoc + sandbox="allow-scripts"` iframes (no allow-same-origin) — admin
      QA modal + client proofing page. Client proofing supports click-to-drop
      annotation pins (`mockup_annotations`). `PushToGitHubJob` +
      `GitHubService` create a private repo and push on delivery (config-gated,
      no-ops without `GITHUB_TOKEN`). Verified end-to-end in a real browser
      through all 8 stages (async queue worker included). Hardened by
      adversarial review (12 findings fixed): queue `retry_after` (660s) now
      exceeds the 600s job timeout (was 90s — long generations ran twice
      concurrently); the Claude client **streams** and rejects
      `max_tokens`-truncated output, strips stray code fences, and jobs reject
      non-HTML/non-JS output rather than storing prose as a "mockup"; a stale
      late-finishing job can't overwrite a newer regeneration (current-artifact
      guard); `failed()` hooks + a "Retry generation" admin action recover a
      stuck order; GitHub push is idempotent (repo reuse + sha upsert); both
      order tables poll so async results surface; and the untrusted preview is
      served from a **CSP-locked route** (`connect-src 'none'`) instead of raw
      srcdoc. **To go live**: set `ANTHROPIC_API_KEY` (and
      `GITHUB_TOKEN`/`GITHUB_OWNER` for repo push), and run a queue worker
      (`php artisan queue:work`) so the jobs process.

- [x] **Epic 5 — Invoicing automation**: PDF invoices via
      `barryvdh/laravel-dompdf` (`InvoicePdf` service + styled
      `invoices.pdf` blade), downloadable through `InvoiceController@download`
      (owner-or-staff; clients blocked from drafts). Admin invoice builder uses
      a Filament `Repeater` of line items — money entered in dollars, stored as
      integer cents — with `subtotal`/`total` recomputed from the items + tax on
      every save (`InvoiceService::recomputeTotals`). A "Send invoice" action
      (`InvoiceService::send`) flips Draft→Sent, defaults a 14-day due date, and
      emails `InvoiceIssued` with the PDF attached. The overdue engine is a daily
      `invoices:process-overdue` command (`->dailyAt('09:00')->withoutOverlapping()`)
      that marks past-due invoices Overdue and dispatches escalating
      **Claude-drafted** reminders at 5/15/30 days; the queued
      `SendOverdueReminderJob` builds the body via `ReminderPromptBuilder`,
      falls back to a plain template if Claude throws, and records
      `reminders_sent`/`last_reminded_at`. Verified through 27 feature tests
      (PDF auth, send + real markdown render + PDF attachment via the array
      transport, overdue cadence, idempotency) plus Filament page-render/create
      tests. Hardened by adversarial review (6 issues fixed, 1 refuted): the
      reminder job now **claims atomically** (compare-and-swap on
      `reminders_sent`, mirroring the Stripe webhook) *before* sending and
      releases the claim if the send throws, so a crash / queue retry / double
      daily-run can't double-send; reminders quote the **outstanding balance**
      (`total − amount_paid`), matching the PDF's "Balance due", instead of the
      gross total; line items are re-stamped to the invoice currency so non-AUD
      invoices don't mix denominations; and both mailables now render via
      `Content(markdown:)` (they used `view:`, so every email would have thrown
      "No hint path defined for [mail]" on a real send — invisible because
      `Mail::fake()` skips rendering). Two edit-page 500s found pre-review by
      Filament render tests (Money `->numeric()` state-cast crash + a
      Money `total` left in Livewire's serialized repeater state) were fixed in
      `InvoiceForm`. The refuted finding (a claimed client "View invoice" modal
      crash) was disproved by an empirical mounted-action test. **To go live**:
      configure a real `MAIL_MAILER` (SMTP/Postmark/etc.) and run both a queue
      worker (`php artisan queue:work`) and the scheduler
      (`php artisan schedule:work` / cron) so reminders fire.

- [x] **Epic 6 — Real-time support chat** (Reverb + stealth AI fallback):
      `chat_conversations` (client `user_id` + optional `assigned_to` staff +
      status) and `chat_messages` (sender `client|staff|ai`) drive a live chat.
      Broadcasting runs on **Laravel Reverb** (pusher protocol) — private
      `chat.conversation.{id}` (client-or-staff auth via
      `ChatConversation::canBeAccessedBy`) carries a content-free
      `ChatMessageSent` nudge (masking stays server-side) plus `AiReplyChunk`
      live token deltas; a presence channel `online.conversation.{id}` powers
      an "agent online" dot. `ChatService` is the single write path.
      **AI fallback**: a client message on an *unassigned* conversation
      dispatches `StreamAiReplyJob`, which streams a Claude reply via the new
      `ClaudeClient::stream()` (token deltas coalesced into words before
      broadcasting), falls back to a holding message on error, and posts as an
      `ai` message — but the **stealth rule** holds end-to-end:
      `ChatMessage::displayRoleFor()` shows a client only `you`/`agent`
      (never `ai`), the wire event is the source-neutral `agent.typing`, and
      the nudge carries no sender type. Filament pages in both panels
      (client support chat, staff inbox where replying claims the thread and
      silences the AI), functional via Livewire + `wire:poll` with Echo as
      progressive enhancement. Verified by 20 feature tests (channel auth,
      masking, AI trigger/silence/idempotency, both pages). Hardened by
      adversarial review (**12 findings fixed, 0 refuted**): a **critical
      client-page IDOR** (a tamperable Livewire `conversationId` exposed any
      client's thread) closed with `#[Locked]` + owner-scoped lookup; the AI
      job made **idempotent** (compare-against-latest-message so bursts collapse
      and retries never double-post) with the claim re-checked against fresh
      state before persisting; a stealth leak (the `ai.chunk` wire name)
      renamed; per-token broadcast flood coalesced to words; N+1 staff-inbox
      unread counts folded into one aggregate; a check-then-create race on the
      open conversation closed with a partial unique index (one open per
      client); the poll write guarded; staff replies no longer steal a
      claimed thread; and the typing bubble `wire:ignore`d so the poll morph
      can't wipe it mid-stream. **To go live**: run the Reverb server
      (`php artisan reverb:start`), a queue worker (for `StreamAiReplyJob`),
      `npm run build`, and set `ANTHROPIC_API_KEY` for real AI replies (the
      `FakeClaudeClient` streams a placeholder keyless).

- [x] **Epic 7 — SEO/SMM engine**: the data + admin CRUD for `blogs` and
      `social_posts` pre-existed (both with a `dueForPublishing` scope); this
      wired the behavioural engine. **Blog (SEO)**: an admin "Generate with AI"
      action seeds a draft stub; `GenerateBlogJob` asks Claude
      (`BlogPromptBuilder`) for a strict-JSON article, fills the blog, and moves
      it to PendingReview (malformed JSON / failure keeps it a draft with the
      error in meta). `blogs:publish-due` (hourly) publishes scheduled articles
      and queues promo social drafts. Public `BlogController` renders a
      published-only index + `/blog/{slug}` (404 for unpublished) through
      `<x-store-layout>`, which now emits full SEO head tags (meta
      description, canonical, Open Graph) plus a `$head` slot; the article page
      injects **JSON-LD `BlogPosting`** and renders the body via a **DOM
      allow-list sanitiser** (`App\Support\HtmlSanitizer`). Plus `sitemap.xml`
      + `robots.txt`. **Social (SMM)**: `GenerateSocialPostsJob` drafts one
      PendingReview post per platform for a published blog (idempotent); VA
      Approve/Reject/Retry actions + status/platform filters on the resource;
      `social:distribute-due` (15 min) dispatches `DistributeSocialPostJob`,
      which is **at-most-once** (compare-and-swap claim — a public duplicate is
      worse than a drop) and distributes via the `SocialDistributor` interface,
      bound to a **fail-closed** `NullSocialDistributor` until a real platform
      driver (e.g. `hamzahassanm/laravel-social-auto-post`) + `services.social`
      credentials are wired. Verified via 26 feature tests + a real-browser
      check of the rendered SEO tags/JSON-LD. Hardened by adversarial review
      (**14 findings fixed, 2 refuted**): three **XSS** holes closed — the
      regex `safeBody()` (bypassable by entity-encoded `javascript:`,
      slash-separated `on*` handlers, `data:`) replaced with the DOM
      allow-list, and the JSON-LD `</script>` breakout closed with
      `JSON_HEX_TAG`; unique-slug generation (duplicate titles no longer 500,
      and the AI title re-slugs the URL); approved-with-null-schedule now
      distributes; the distribution job catches any Throwable (no silent
      "published-but-unsent") with a VA Retry path; a distinct **Rejected**
      status separates review-rejection from distribution failure; N+1 index
      eager-load; page-aware canonical; and a meta-description word-run fix.
      **To go live**: set `ANTHROPIC_API_KEY`, run a queue worker + the
      scheduler, `npm run build`, and bind a real `SocialDistributor` with
      platform credentials.

- [x] **Epic 8 — SEO lead magnet**: the `leads` table pre-existed with
      `website_url` + `seo_report_path` + a `seo_audit` source. A public,
      throttled form (`SeoAuditController`, `GET/POST /seo-audit`) captures a
      Lead and dispatches `GenerateSeoAuditJob`, which fetches the prospect URL,
      extracts SEO signals (DOMDocument), has Claude produce a JSON audit
      (`SeoAuditPromptBuilder`), renders a branded dompdf report (`SeoAuditPdf`),
      stores it to the private disk, stamps `seo_report_path`, and emails it
      (`SeoAuditReport`, markdown + PDF attachment). Staff download the stored
      report from the Lead resource. On any failure the lead is still captured
      with the error in meta — a broken report is never emailed. The critical
      surface is **SSRF** (fetching an arbitrary user URL); `App\Support\SafeUrlFetcher`
      guards it. Verified via 43 feature tests (31 dedicated to the fetcher).
      Hardened by adversarial review (**8 findings fixed, 1 refuted**), most
      security-critical: an **IPv6 SSRF gap** — NAT64 `64:ff9b::/96`, 6to4,
      v4-compat/v4-mapped, site-local and multicast addresses passed the
      `filter_var`-only check and (on a NAT64 host like Railway) reached
      `169.254.169.254`; fixed by extracting the embedded IPv4 and re-checking
      it plus an explicit IPv6 CIDR blocklist. Also: the response size cap now
      aborts **during transfer** (`CURLOPT_MAXFILESIZE` + progress abort) so a
      hostile large body can't OOM the worker; a **port allow-list** (80/443)
      closes internal port-scanning; the job is **idempotent** (completion
      stamped after the email, so a retry won't re-send); a **per-email daily
      cooldown** stops the form mailbombing a third party; and staff can now
      download the stored PDF. **Full SSRF defences**: scheme allow-list,
      resolved-IP validation (v4 CIDR blocklist + v6 embedding-aware),
      `CURLOPT_RESOLVE` IP-pinning (DNS-rebind), and per-hop redirect
      re-validation. **To go live**: set `ANTHROPIC_API_KEY`, run a queue
      worker, and configure a real `MAIL_MAILER`; consider double opt-in if
      abuse of the public endpoint becomes a problem.

- [x] **Epic 9 — CMS** (custom, not LaraGrape): the roadmap flagged LaraGrape
      as a high-risk dependency and nothing depended on GrapesJS, so this is a
      **custom** CMS reusing the Epic 7 content pipeline. `CmsPage` (translatable
      `title`/`body` via **spatie/laravel-translatable** — its first consumer)
      with a Filament resource (RichEditor constrained to the sanitiser's tag
      set), an admin `slug`/status/footer/SEO form, and `uniqueSlug()`. Public
      pages render at a **root catch-all** `/{page:slug}` (registered last,
      slug-constrained, published-only/404) through `<x-store-layout>` with the
      `HtmlSanitizer` on the body + per-locale SEO meta. A `SetLocale`
      middleware resolves `?lang` (allow-listed) / user locale. **Legal pages**
      (privacy/terms/refund, with the ACL refund wording) are seeded and linked
      from the footer (cached). **Tracking injection is security-first**: only
      **format-validated IDs** (GA4/GTM/Meta-pixel) are stored via a `Setting`
      key/value model + an admin settings page; the `<x-analytics>` component
      **re-validates each ID at render** and interpolates it into **fixed
      official script templates** — a raw `<script>` is never stored or
      rendered (a poisoned ID is dropped). Verified via 17 feature tests + a
      real-browser check (legal page + GA4 tag + footer links). Hardened by
      adversarial review (**5 findings fixed, 0 refuted; the XSS/analytics
      dimension found nothing**): a **HIGH** edit-page 500 — spatie translatable
      attrs hydrate the RichEditor as a `{"en":…}` array — fixed by flattening
      in `mutateFormDataBeforeFill` (the "test the edit render" lesson, now with
      a regression test); the RichEditor toolbar constrained so authored
      content can't silently vanish through the sanitiser; slug format +
      reserved-word validation (and the route now allows a leading digit) so a
      page can't be saved unreachable or shadowing a real route; per-locale SEO
      title; and the footer query cached with save/delete invalidation. **To go
      live**: author real legal copy, and set the analytics IDs in the admin
      Analytics page.

- [x] **Epic 10 — Affiliate program**: the data layer (referral_relationships,
      commissions, `CommissionStatus`, the `User::created` referral-code
      generator, `Money::percentage`) pre-existed; this wired it. A
      `CaptureReferral` middleware drops a **first-touch, encrypted** `referral`
      cookie on `?ref=CODE`; a custom client Register page calls
      `ReferralService::attachReferral` (resolve code → referrer, guard
      blank/unknown/self/**staff**/already-attributed, set `referred_by` +
      create the relationship). `recordCommissionForFirstPaidOrder` runs inside
      the Stripe webhook's once-only paid block: a **compare-and-swap on
      `converted_at`** makes it fire at most once per referred user, creating a
      Pending commission of `order->total->percentage(bps)` (10% default,
      snapshotted as `rate_basis_points`), plus a `unique(order_id)` guard. The
      lifecycle is pending → approved (admin) → **credited** (client self-serves
      "take as account credit") | **paid** (admin cash) — the credit-vs-payout
      choice. Admin `CommissionResource` (approve/mark-paid); client
      `CommissionResource` (owner-scoped `referrer_id = Auth::id()`) + an
      Affiliate page (referral link, earnings by bucket). Verified via 15 tests.
      Hardened by adversarial review (**3 findings fixed; the IDOR dimension
      found nothing**): a **segregation-of-duties** gap — staff got referral
      codes and could self-approve/self-pay their own commissions — closed
      (staff can't be referrers, and admin actions are four-eyes: you can't
      action a commission where you're the referrer); **Credited made a terminal
      state** so a commission can't be settled as both credit and cash; and the
      Affiliate page's earnings sum now seeds from the actual commission currency
      (a non-AUD commission no longer 500s the page). **To go live**: set
      `AFFILIATE_COMMISSION_BPS` if 10% isn't right, and run a queue worker (the
      commission is created synchronously in the webhook — no worker needed).

- [x] **Epic 11 — Server ops**: uptime + TLS-expiry monitoring, a config-gated
      WHM API client, and scheduled backups with an admin health panel — all
      net-new (nothing existed; `spatie/laravel-uptime-monitor` was *not* pulled
      in — the monitor domain is custom). `server_monitors` (name/url/status +
      `MonitorStatus` enum, nullable `incident_ticket_id`/`ssl_ticket_id` guard
      columns) drive `MonitorService::check()`: an uptime poll (plain `Http` —
      monitored URLs are admin-configured/trusted, so **not** SafeUrlFetcher,
      whose private-IP block would reject internal targets) plus a TLS-expiry
      probe (raw `ssl://` socket + `openssl_x509_parse`, `verify_peer` off so an
      already-expired cert is still read), then a **row-locked** reconcile that
      auto-opens **one** `HelpdeskTicket` on `up→down` (after an N-failure
      debounce) and on SSL nearing expiry, and auto-resolves them on recovery.
      Duplicate suppression is a CAS-style null-guard on the ticket-id columns
      under `lockForUpdate` (repeated failures never open a second ticket);
      system tickets are owned by the first admin and messages are
      `is_internal` (never leak infra to a client). `CheckMonitorJob` (one per
      monitor) + `monitors:check` (every 5 min). **WHM**: a `WhmClient`
      interface, config-gated real `WhmApiClient` (token auth against the :2087
      JSON API) bound only when `WHM_HOST/USERNAME/API_TOKEN` are all set, else
      a **fail-closed** `NullWhmClient` (mirrors `NullSocialDistributor`).
      **Backups**: `spatie/laravel-backup` configured (`config/backup.php`,
      `.env` excluded from the file set), `backup:clean`/`backup:run` scheduled
      daily, and an admin **Backup monitor** page (lists archives on the backup
      disk with size/age, health-checks against the age/size thresholds, surfaces
      WHM server status, queues a backup). Admin `ServerMonitorResource`
      (list/create/edit + Check-now/Check-all actions). Verified via 14 feature
      tests. Hardened by adversarial review (**3 findings fixed, 4 refuted**): a
      **HIGH** — the Backup page read `config('backup.destination…')`/`backup.name`
      one nesting level too shallow (spatie nests them under a top-level `backup`
      key), so it silently listed **zero** archives on the wrong disk and always
      reported "no backups" even while backups succeeded — fixed to
      `backup.backup.*` with a null-name guard and a regression test that seeds a
      fake archive; a transient TLS handshake failure no longer **wipes** a
      still-valid `ssl_expires_at` to null (null-guarded, mirroring the ticket
      reconcile); and a test that opened a **real outbound TLS socket** now binds
      the SSL-stubbed service. **To go live**: seed real monitor URLs, run a
      queue worker + the scheduler, set `WHM_*` for server management, and point
      `BACKUP_DISK` at `s3` (with `BACKUP_ARCHIVE_PASSWORD`) for off-server,
      encrypted archives.

- [x] **Epic 12 — Onboarding & SSO**: client-panel social login + a forced
      first-login profile wizard. All three packages pre-existed (installed,
      unwired): `dutchcodingcompany/filament-socialite` 3.2, `laravel/socialite`,
      `spatie/laravel-onboard` 2.6. **SSO** (client panel only — staff stay
      password-only): `FilamentSocialitePlugin` registers Google + GitHub, each
      button **config-gated** via `->visible(fn () => filled(config('services.<p>.client_id')))`
      so it never appears (or dead-links) without credentials. `createUserUsing`
      → `SocialiteUserCreator` makes a verified, **passwordless** user (a new
      migration makes `users.password` nullable), role defaults to `client`, and
      **replicates the first-touch referral attribution** the SSO flow would
      otherwise bypass (reads the `referral` cookie → `attachReferral`). Provider
      links live in a net-new `socialite_users` table (published from the vendor
      stub). **Onboarding**: `User implements Onboardable`; an `Onboard` step is
      registered in `AppServiceProvider::boot()`; a hidden client-panel
      `Onboarding` page collects `company_name`/`phone` and stamps `onboarded_at`;
      the `EnsureOnboarded` middleware (on the client panel's `authMiddleware`)
      forces incomplete clients to the wizard, with an allow-list (wizard page,
      `*livewire.update`, logout) so it can't loop or lock them out. Verified via
      12 feature tests + a real-browser check of the config-gated buttons on
      `/client/login`. Hardened by adversarial review (**1 HIGH fixed, 7
      refuted**): a **staff-account auth-boundary bypass** — both panels share the
      `web` guard and client SSO auto-links by email, so a staff member (or anyone
      with their Google/GitHub account — the seeded admin is a real Gmail) could
      sign in via `/client` SSO and then reach `/admin`; closed with an
      `->authorizeUserUsing()` gate that **rejects any staff email before it can
      link or authenticate** (regression-tested). Two correctness bugs were caught
      pre-review: spatie's `completeIf` callback binds the model to a param named
      `$model` (a `$user` param resolved a fresh empty User, making the gate
      always fire), and the middleware now gates on `hasCompletedOnboarding()`
      directly rather than spatie's `once()`-memoized `onboarding()->inProgress()`
      (which would go stale on a FrankenPHP/Octane persistent worker — the Epic 13
      target). **To go live**: set `GOOGLE_CLIENT_ID/SECRET` and/or
      `GITHUB_CLIENT_ID/SECRET` (+ redirect URIs) and register the callback URLs
      (`<APP_URL>/client/oauth/callback/{google,github}`) with each provider.

- [x] **Epic 13 — Railway deploy**: production build config + the code changes a
      real deploy needs — no new features. **Deploy artifacts**: `railway.json`
      (Railpack builder, `restartPolicyType: ALWAYS`), `railway/{release,worker,
      scheduler,reverb}.sh` per-service commands, `.env.production.example`, and
      [docs/DEPLOY.md](DEPLOY.md) — a 4-service topology (web / worker / scheduler
      / reverb) + managed Postgres (+ optional Redis) from one repo. Only the web
      service migrates (pre-deploy `migrate --force`); `RAILPACK_SKIP_MIGRATIONS=1`
      everywhere else. Reverb binds `0.0.0.0:8080` while the browser connects over
      the edge (`REVERB_PORT=443`, wss). **Production code changes**: trusted
      proxies (`$middleware->trustProxies(at: '*')` in `bootstrap/app.php`) so the
      app sees the real scheme/host behind Railway's edge; the sign-pad
      neutralizing Closure route became an invokable `NotFoundController` so
      `route:cache` (run by Railpack's build-time `optimize`) doesn't fail; a
      `config('filesystems.private_disk')` indirection routes intake uploads + SEO
      PDFs to **S3** in prod (the container FS is ephemeral *and* not shared
      between web + worker); and `pgsql` reads `DATABASE_URL` directly. Verified:
      `php artisan optimize` succeeds and the 285-test suite passes. Hardened by
      adversarial review (**5 fixed, 6 refuted**): a **CRITICAL** — the S3 disks
      I documented would fatal (`class not found`) because
      `league/flysystem-aws-s3-v3` wasn't a dependency (invisible locally — dev
      defaults to the `local` disk); added it. A **HIGH** — the worker's
      `queue:work --max-time=3600` exits cleanly, which `ON_FAILURE` wouldn't
      restart (queues would die an hour after every deploy); switched the restart
      policy to `ALWAYS`. Plus: `.env.production.example` used shell `${VAR}`
      interpolation that Railway doesn't expand (VITE websocket keys + OAuth
      redirects would ship as literal strings) → `${{VAR}}`; the Reverb health
      check must hit `/up` (not `/`, which 404s); and the scheduler must run a
      single replica. **To go live**: follow docs/DEPLOY.md — provision Postgres +
      an S3 bucket, create the services, set env from the template (generate
      `APP_KEY`, point `APP_URL`/OAuth/Stripe webhook at the web domain).

- [x] **Epic 14 — Hardening** (final epic): four areas. **RBAC** — real per-role
      authorization. There is deliberately **no `Gate::before`** (it would bypass
      `UserPolicy`'s self-delete guard); each policy allows admin explicitly.
      Admin-only models not in the client panel get policies (`ProductPolicy`,
      `ServerMonitorPolicy`); the shared `Order` model gets a client-panel-safe
      `OrderPolicy` (only `delete` is admin-restricted); shared models with client
      resources (Invoice/Contract/Commission) and the `CmsPage` (legal pages) +
      the two admin pages (ManageAnalytics/BackupMonitor) are gated with a Filament
      `canAccess()=isAdmin` on the **admin** resource so the client panel is
      untouched. The role field self-locks (no self-demotion) and the Users
      bulk-delete authorizes each row (no self-lockout). **Rate limiting** —
      Stripe-session routes (`checkout`/`subscribe`) throttled; Filament login
      (5/min) + register (2/min) are already throttled. **GST** — a consistent
      **inclusive** model: `InvoiceService::gstComponent()` backs GST out of the
      total (`total/11`), shared by the admin invoice flow and the storefront
      webhook, so an issued invoice never exceeds what Stripe charged; the tax
      invoice PDF shows ABN + legal name/address + the GST component;
      `config/company.php` holds the tax identity; Stripe Tax is config-gated
      (off) with `tax_behavior=inclusive`. **Geotargeting** — `en-AU`/hreflang/geo
      meta + a site-wide `Organization` JSON-LD (AU address + `areaServed` + ABN,
      `JSON_HEX_TAG`); the Search Console country setting is documented as an
      external step. Verified: 308 tests + a real-browser check of the geo signals.
      Hardened by adversarial review (**3 fixed, 5 refuted**): a **HIGH** — with
      Stripe Tax enabled, the one-time checkout's inclusive prices lacked
      `tax_behavior=inclusive`, so Stripe would add 10% on top (overcharging +
      a non-reconciling invoice) — fixed; a **MEDIUM** — `CmsPageResource` was
      missed in the admin-gating, letting a VA edit/unpublish legal pages — gated;
      and a **LOW** — the Users bulk-delete bypassed the self-delete guard — now
      authorizes per row. **To go live**: set `COMPANY_ABN`/legal-name/address for
      valid tax invoices, and set the Australia country target in Search Console.

---

**All 14 roadmap epics are complete.** Remaining work is operational go-live
configuration (secrets/credentials — every feature fails closed until set) and
the Railway deploy itself (see [docs/DEPLOY.md](DEPLOY.md)), plus any future
enhancements beyond the blueprint.
