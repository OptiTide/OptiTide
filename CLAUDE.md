# OptiTide

Digital agency platform: public e-commerce catalog + AI-driven CRM ("stealth" —
clients never see the AI pipeline stages). Built from `OptiTide Web App
Blueprint.docx`; see [docs/ROADMAP.md](docs/ROADMAP.md) for corrected
architecture decisions and remaining epics.

## Stack

- Laravel 13 (PHP 8.3 — the minimum for L13), Filament v5.6 (two panels), Vite 8 + Tailwind v4
- SQLite for local dev; PostgreSQL + Redis on Railway in production
- Stripe via laravel/cashier (NOT Payoneer — see roadmap for why)
- Pest for tests

## Commands

- Composer is NOT on PATH: `php "$HOME/.local/bin/composer.phar" <args>`
- Dev server: `php artisan serve --port=8000` (or `.claude/launch.json` name `optitide`)
- Tests: `./vendor/bin/pest`
- Reset DB: `php artisan migrate:fresh --seed`
- Local logins (all password `password`): michaeldemae@gmail.com (admin), va@optitide.io (va), client@example.com (client)

## Architecture rules

- **Money**: all monetary DB columns are integer minor units (cents) with a
  sibling `currency` char(3) column (default AUD). Models cast via
  `App\Casts\MoneyCast` to the immutable `App\Support\Money` value object.
  Filament forms edit dollars and dehydrate to cents (see ProductForm).
- **Order pipeline**: `App\Enums\OrderState` (8 stages) owns the allowed
  transition map. NEVER set `orders.state` directly — call
  `Order::transitionTo($state, $actor, $notes)`, which enforces the map and
  writes `order_state_transitions` audit rows atomically.
- **Client masking**: internal stages (AI generation, QA) must never leak to
  the client portal. Use `OrderState::clientFacingLabel()` in anything a
  client can see.
- **RBAC**: `users.role` enum (admin | va | client). Staff use the `/admin`
  panel, clients use `/client`. Panel access is gated in
  `User::canAccessPanel()`. Client resources scope queries with
  `->where('user_id', Auth::id())` in `getEloquentQuery()`.
- **Panels**: admin resources live in `app/Filament/Resources`, client ones in
  `app/Filament/Client/Resources`. Filament v5 layout: `<Name>Resource.php` +
  `Schemas/<Name>Form.php` + `Tables/<Name>sTable.php` + `Pages/`.
- Subscriptions/subscription_items tables are owned by Cashier (Stripe
  billing); do not hand-roll subscription state.
- **Stripe webhook** (`/stripe/webhook`, `StripeWebhookController`): extends
  Cashier's controller, CSRF-exempt, registered manually because
  `Cashier::ignoreRoutes()` is set in AppServiceProvider. It **fails closed**
  (503) when `cashier.webhook.secret` is blank outside tests — so it's
  inert until STRIPE_WEBHOOK_SECRET is configured. Order fulfilment is a
  compare-and-swap (`where('payment_status', 'pending')->update(...)`) so
  redelivery never double-issues invoices. The metadata `order_id` fallback
  only matches orders with a null `stripe_checkout_session_id`.
- **Contracts** (`Contract` model, `creagia/laravel-sign-pad`): signing goes
  through `ContractSignatureController` (auth + `$contract->user_id ===
  auth()->id()`), NOT the package's `creagia/sign-pad` route (which uses a
  weak per-class shared token). The store flow wraps signature-row creation +
  image storage + PDF generation + `markSigned()` in one `DB::transaction` so
  a mid-flight failure never leaves a dangling signature that bricks the
  contract. PDFs render `contracts.body.{template_key}` via TCPDF (limited
  HTML/CSS only). Contracts are auto-issued by `ContractService`, never
  created by hand in Filament.
- **AI pipeline** (`app/Services/AI/*`, `app/Jobs/Generate*Job`): all Claude
  calls go through the `ClaudeClient` interface — bound to the real
  `AnthropicClaudeClient` (opus-4-8) when `ANTHROPIC_API_KEY` is set, else
  `FakeClaudeClient` (placeholder output; keyless dev + tests). Never call the
  SDK directly. Generation is queued (`GenerateMockupJob`/`GenerateLogicJob`) —
  **jobs run async on the database queue in dev/prod** (sync only in tests), so
  a real run needs `php artisan queue:work`. `PipelineService` is the only place
  that creates artifacts + transitions the order; don't hand-roll pipeline
  transitions. **Untrusted LLM HTML must only ever render in a
  `srcdoc + sandbox="allow-scripts"` iframe (never `allow-same-origin`)** —
  see admin/artifact-preview and storefront/proof blades.
- **Intake brief** (`IntakeController`, `SchemaFormBuilder`): the per-order
  project brief is schema-driven from `form_schemas` (JSON `fields`). Answers
  split into `client_submissions.data` (text/select/date/url) vs
  `.brand_assets` (files + colour hexes). Uploads go to the private `local`
  disk (`intake_assets/{order}`); the `brief.asset` route only streams paths
  actually recorded in the submission (no traversal). Submitting advances the
  order `pending_intake → admin_review`. `Order::needsIntake()` is the single
  gate (paid, at intake, no submission, no unsigned agreement).
- **Invoicing** (`InvoiceService`, `InvoicePdf`, `app/Jobs/SendOverdueReminderJob`):
  invoice + line-item money is integer cents; the admin Filament `Repeater` edits
  dollars and dehydrates to cents. `subtotal`/`total` are **never** set by hand —
  `InvoiceService::recomputeTotals()` sums the line items + tax (called from
  Create/Edit page `afterCreate`/`afterSave`) and re-stamps every item to the
  invoice `currency` (the repeater has no per-item currency field). PDFs render
  `invoices.pdf` via dompdf; download goes through `InvoiceController@download`
  (owner-or-staff, drafts staff-only). Issuing is `InvoiceService::send()`
  (Draft→Sent + `InvoiceIssued` mail with PDF attached), never a hand-set status.
  The overdue engine (`invoices:process-overdue`, daily) marks past-due invoices
  Overdue and dispatches escalating **Claude-drafted** reminders at 5/15/30 days;
  `SendOverdueReminderJob` **claims atomically** (compare-and-swap on
  `reminders_sent`) before sending — never reorder the claim after the mail.
  Reminders and any "balance owed" figure use `total->subtract(amount_paid)`
  (the "Balance due" convention), not the gross total.
- **Mailables**: templates using `<x-mail::message>`/`<x-mail::button>` MUST be
  declared `Content(markdown: ...)`, never `Content(view: ...)` — `view:` skips
  the markdown pipeline and throws "No hint path defined for [mail]" on a real
  send. `Mail::fake()` skips rendering, so assert real rendering with
  `(new TheMailable(...))->render()` or the `array` transport.
- **Live chat** (`ChatService`, `app/Jobs/StreamAiReplyJob`, `app/Events/*`):
  `ChatService` is the single write path — `postMessage()` creates the message,
  reopens/bumps the conversation, fires the content-free `ChatMessageSent`
  broadcast, and dispatches the AI fallback. Never create `ChatMessage`s by
  hand. **Stealth AI**: a client must never learn a reply was AI — render every
  message through `ChatMessage::displayRoleFor($viewer)` (a client sees only
  `you`/`agent`), keep broadcast payloads sender-type-free, and keep the wire
  event `agent.typing` (never encode "ai" in an event name a client can see).
  Reverb broadcasting: private `chat.conversation.{id}` authorized by
  `ChatConversation::canBeAccessedBy()`; `StreamAiReplyJob` is idempotent via
  compare-against-latest-message and only answers an open, unassigned thread.
  A real run needs `php artisan reverb:start` + a queue worker + `npm run build`.
- **Filament page IDOR guard**: custom Filament `Page` classes (not Resources)
  get NO automatic `getEloquentQuery()` client scoping. Any public Livewire
  property that selects a record (e.g. `$conversationId`) MUST be `#[Locked]`
  AND the lookup owner-scoped (`Auth::user()->relation()->findOrFail(...)`) —
  an un-Locked public property is re-hydrated from the client payload every
  request and is trivially tampered.
- **Testing broadcast channel auth**: channels register on the *default*
  broadcaster at boot (`null` in tests). To exercise `/broadcasting/auth`,
  switch `config(['broadcasting.default' => 'reverb'])` **and** re-`require`
  `routes/channels.php` so the closures register on the reverb broadcaster.
- **SEO/SMM engine** (`app/Jobs/Generate*Job`, `Publish*`/`Distribute*`
  commands): blogs and social posts generate through `ClaudeClient` +
  `BlogPromptBuilder`/`SocialPostPromptBuilder`; the blog job expects strict
  JSON, the social job plain text. Both flow through a human gate
  (`pending_review`) before going live. `blogs:publish-due` (hourly) publishes
  scheduled articles and queues promo social drafts; `social:distribute-due`
  (15 min) distributes approved posts. **Distribution is at-most-once** — a
  compare-and-swap claim (a public duplicate is worse than a drop) via the
  `SocialDistributor` interface, bound to the **fail-closed**
  `NullSocialDistributor` until a real driver + `services.social` creds are
  wired; the job catches any `Throwable` so a post is never left claimed-as-
  published but unsent.
- **Untrusted HTML on public pages**: AI/user HTML rendered with `{!! !!}` MUST
  go through `App\Support\HtmlSanitizer` (DOM allow-list) — never a regex
  sanitiser (bypassable by entity-encoded schemes / slash-separated `on*`
  handlers). Inline JSON in a `<script>` (JSON-LD) MUST use `JSON_HEX_TAG`
  (never `JSON_UNESCAPED_SLASHES`) or a `</script>` in the data breaks out.
  Blog slugs come from `Blog::uniqueSlug()` (the unique column 500s otherwise).
- **Fetching user-supplied URLs** (SEO lead magnet): ALWAYS go through
  `App\Support\SafeUrlFetcher` — never `Http::get()` a user URL directly. It
  validates the *resolved* IP (IPv4 CIDR blocklist + IPv6 that extracts embedded
  IPv4 from NAT64/6to4/v4-mapped — `filter_var` flags alone miss those), pins
  the IP against DNS-rebind (`CURLOPT_RESOLVE`), allow-lists ports 80/443,
  re-validates each redirect hop, and caps the transfer. The public audit form
  keeps a **per-email daily cooldown** so it can't mailbomb a third party, and
  `GenerateSeoAuditJob` stamps `seo_report_path` only after emailing (idempotency).
- **CMS** (`CmsPage`, `Setting`, `<x-analytics>`): CMS pages are custom (not
  LaraGrape). `title`/`body` are **spatie translatable** (JSON) — the Edit page
  MUST flatten them to the current-locale string in `mutateFormDataBeforeFill`
  (a `RichEditor` 500s on the `{"en":…}` array — always test the edit render).
  Public pages live at the root catch-all `/{page:slug}` (registered LAST,
  slug-constrained, published-only); body renders through `HtmlSanitizer` and
  the RichEditor toolbar is constrained to the sanitiser's tags. **Tracking**:
  never store or render a raw `<script>` — the admin Analytics page stores only
  **format-validated IDs** (`Setting`), and `<x-analytics>` re-validates each ID
  and emits a **fixed official snippet**. Slugs are format+reserved-word
  validated so a page can't be saved unreachable or shadowing a real route.

- **Affiliate program** (`ReferralService`, `CommissionService`): referral
  attribution is first-touch via the encrypted `referral` cookie
  (`CaptureReferral` middleware) → `attachReferral` at registration (a custom
  client Register page). Commission is created on the referred user's **first
  paid order only**, inside the Stripe webhook's once-only block via a
  compare-and-swap on `referral_relationships.converted_at` (+ `unique(order_id)`);
  amount = `order->total->percentage(config('affiliate.commission_basis_points'))`.
  **Segregation of duties**: staff can't be referrers (`attachReferral` rejects
  `isStaff()`) and admin approve/mark-paid actions are four-eyes (can't action
  your own commission). Lifecycle pending→approved→**credited|paid** where
  credited is **terminal** (no cash-on-top-of-credit). Summing `Money` across
  rows, seed `Money::zero($currency)` from the data, never a hardcoded default —
  `add()` throws on a currency mismatch.

- **Server ops** (`MonitorService`, `ServerMonitor`, `WhmClient`,
  spatie/laravel-backup): uptime/SSL monitors are **custom** (not
  spatie/laravel-uptime-monitor). `MonitorService::check()` does the network I/O
  (uptime via the plain `Http` facade; TLS expiry via a raw `ssl://` socket +
  `openssl_x509_parse`, `verify_peer` off so an expired cert is still read)
  **outside** a transaction, then reconciles state inside a `DB::transaction`
  that re-reads the row with `lockForUpdate`. Monitored URLs are
  admin-configured/**trusted** — deliberately **not** routed through
  `SafeUrlFetcher` (its private-IP block would reject legitimate internal
  targets). Auto-ticketing is **idempotent**: `up→down` (after an N-failure
  debounce) and SSL-nearing-expiry each open **one** `HelpdeskTicket`, guarded
  by nullable `incident_ticket_id`/`ssl_ticket_id` columns set only while null
  (under the row lock) and cleared on recovery — repeated failing checks never
  duplicate. System tickets are owned by the first admin (`user_id` is NOT
  nullable) and all monitor messages are `is_internal` (never leak infra to a
  client). Only overwrite `ssl_expires_at` when a cert was actually read (a
  transient handshake failure must not wipe a still-valid expiry). `WhmClient`
  is config-gated (real `WhmApiClient` only when `WHM_HOST/USERNAME/API_TOKEN`
  are all set, else fail-closed `NullWhmClient`) — mirror `NullSocialDistributor`.
  **spatie/laravel-backup config is nested under a top-level `backup` key** —
  read `config('backup.backup.name')` and `config('backup.backup.destination.disks')`
  (NOT `backup.name`/`backup.destination`); `monitor_backups` IS top-level.
  `.env` is excluded from the file backup (secrets never ship in an archive; the
  DB is dumped separately). Real runs need a queue worker + the scheduler.

- **Onboarding & SSO** (`filament-socialite`, `spatie/laravel-onboard`,
  `SocialiteUserCreator`, `EnsureOnboarded`): social login is registered on the
  **client panel only** (`ClientPanelProvider`) — staff stay password-only.
  **Both panels share the `web` guard**, so client SSO MUST reject staff: an
  `->authorizeUserUsing()` gate refuses any OAuth identity whose email belongs to
  a non-client role *before* it can link/authenticate (without it, the email
  auto-link would sign a staff user into a session that `canAccessPanel('admin')`
  accepts). Each provider button is config-gated via
  `->visible(fn () => filled(config('services.<p>.client_id')))`. `createUserUsing`
  → `SocialiteUserCreator` sets `email_verified_at` directly (it's guarded),
  leaves `password` null (the column is nullable for OAuth-only accounts), lets
  `role` fall to the `client` default, and **replicates the referral cookie
  attribution** the SSO flow bypasses. The `github` `config/services` block holds
  BOTH the repo-push token/owner AND OAuth client_id/secret. **Onboarding**:
  `User implements Onboardable`; the `EnsureOnboarded` middleware forces
  incomplete clients to the wizard but gates on `hasCompletedOnboarding()`
  **directly** — NOT spatie's `onboarding()->inProgress()`, which memoizes step
  completion via `once()` and goes stale on a persistent worker (FrankenPHP/Octane).
  A spatie `completeIf` callback is invoked via `app()->call(..., ['model' => …])`,
  so its parameter MUST be named `$model` (a `$user` param resolves a fresh empty
  model from the container). The wizard allow-list must match the real Livewire
  route name (`default-livewire.update`, matched with `*livewire.update`) or the
  form submit is redirected mid-flight.

- **Deploy (Railway/Railpack + FrankenPHP)** — see [docs/DEPLOY.md](docs/DEPLOY.md)
  and `.env.production.example`. One repo → 4 services (web/worker/scheduler/reverb)
  + managed Postgres. **Trusted proxies** are on (`bootstrap/app.php`
  `trustProxies(at: '*')`) — required behind the edge for correct https/host,
  secure cookies, and Reverb/OAuth callback URLs. **`route:cache` must stay
  green** (Railpack runs `php artisan optimize` at build): NO Closure routes —
  the sign-pad 404 override is an invokable `NotFoundController`, not a closure.
  Private artifacts must use `config('filesystems.private_disk')` (intake uploads,
  SEO PDFs) — never hardcode the `local` disk — because prod sets it to **s3**
  (the container FS is ephemeral and not shared across services); `SIGNATURES_DISK`
  + `BACKUP_DISK` are the same idea. The S3 driver (`league/flysystem-aws-s3-v3`)
  IS a required dependency — anything that sets a disk to `s3` needs it. Only the
  **web** service migrates (pre-deploy); `RAILPACK_SKIP_MIGRATIONS=1` everywhere.
  The worker uses `--max-time` (clean exit to recycle) so the restart policy is
  `ALWAYS` (ON_FAILURE would never restart it). Railway env references use the
  **`${{VAR}}`** form (not shell `${VAR}`) — the build-time `VITE_*` and OAuth
  redirect vars break otherwise.

- **RBAC / per-role policies** (Epic 14): staff split into admin vs va. There is
  **no `Gate::before`** (an admin superuser bypass would defeat `UserPolicy`'s
  self-delete guard) — every policy allows admin explicitly. **Policies are
  model-global across BOTH panels**, so only use a policy for a model NOT in the
  client panel (`ProductPolicy`, `ServerMonitorPolicy` = admin-only); a model
  shared with the client panel needs a client-safe policy (`OrderPolicy`:
  permissive `viewAny`/`view`, only `delete`/`deleteAny` admin) OR — cleaner —
  gate the **admin** resource with a Filament `canAccess()=isAdmin` override
  (Invoice/Contract/Commission/CmsPage admin resources + the ManageAnalytics /
  BackupMonitor pages) so the client resource is untouched. Bulk actions authorize
  only via `deleteAny` — chain `->authorizeIndividualRecords('delete')` when the
  per-record policy has a guard (e.g. no self-delete). Commission four-eyes stays.
- **GST is INCLUSIVE** (`InvoiceService::gstComponent`, `config/company.php`): AU
  prices include GST, so GST is the component within the total
  (`round(amount*bps/(10000+bps))`, i.e. total/11), **never added on top** — the
  admin invoice flow and the Stripe webhook both use `gstComponent` so an issued
  invoice never exceeds the charged amount. A valid AU tax invoice needs the ABN +
  legal name/address (`config('company')`) on the PDF. If Stripe Tax is enabled
  (`STRIPE_AUTOMATIC_TAX`), one-time `price_data` MUST set
  `tax_behavior=inclusive` and subscription Prices must be Inclusive in Stripe, or
  Stripe adds 10% on top.

## Gotchas

- Models with status/state columns define `protected $attributes` defaults so
  fresh instances have usable enum values before a DB round-trip.
- Order/invoice numbers are generated in `created` model events
  (OT-000001 / INV-000001); a PENDING-uuid placeholder satisfies the unique
  constraint during insert.
- `DatabaseSeeder` must NOT use `WithoutModelEvents` — referral codes are
  generated by the `User::created` event.
