# OptiTide — Handoff Notes

Notes for a fresh Claude (or developer) picking this project up cold. This
captures what is **not** already in the checked-in docs. Read these three first,
in order, then this file:

1. [`CLAUDE.md`](../CLAUDE.md) — authoritative architecture rules + gotchas. **Start here.**
2. [`docs/ROADMAP.md`](ROADMAP.md) — full epic-by-epic history, decisions, and per-epic "to go live" notes.
3. [`docs/DEPLOY.md`](DEPLOY.md) — Railway deployment runbook.

---

## 1. Where the project stands

OptiTide is a Laravel 13 (PHP 8.3) + Filament v5.6 digital-agency platform:
public e-commerce storefront + a "stealth AI" CRM (clients never see the AI
pipeline). **All 14 roadmap epics are complete** — the app is feature-complete
against the blueprint. `php artisan test` is green (**308 tests**).

Every external integration **fails closed** until its secret is configured, so
the app runs and the suite passes with zero credentials. What's stubbed/gated:

| Feature | Without config it… | To enable |
|---|---|---|
| Stripe (checkout + webhook) | webhook returns **503**; no charges | `STRIPE_KEY/SECRET/WEBHOOK_SECRET` + Stripe Prices for hosting plans |
| Claude AI (pipeline, chat, blogs, reminders, audits) | uses `FakeClaudeClient` (labelled placeholder) | `ANTHROPIC_API_KEY` |
| GitHub repo sync on delivery | no-ops | `GITHUB_TOKEN` + `GITHUB_OWNER` |
| Social distribution | `NullSocialDistributor` (fails closed) | bind a real driver + `services.social.*` |
| WHM server mgmt | `NullWhmClient` (fails closed) | `WHM_HOST/USERNAME/API_TOKEN` |
| Reverb live chat | works locally but needs the server running | `php artisan reverb:start` + queue worker + `npm run build` |
| Social login (Google/GitHub) | buttons hidden | `GOOGLE_/GITHUB_CLIENT_ID`+`SECRET` |
| S3 private disk (prod) | uses local disk | `AWS_*` + set the disks to `s3` (the `league/flysystem-aws-s3-v3` dep **is** installed) |

Local logins (all password `password`): `michaeldemae@gmail.com` (admin),
`va@optitide.io` (va), `client@example.com` (client).

---

## 2. Git state — READ THIS

**The repo has ZERO commits.** Everything (~446 files) is **staged in the index
but never committed.** This is deliberate: the standing rule for this project is
**stage everything with `git add -A`, but NEVER commit or push unless the user
explicitly asks.**

Consequences for the handoff:
- All the code is present in the **working directory** — a folder copy carries
  the entire project regardless of the un-committed git state.
- You cannot `git clone` this (no commits/HEAD). To move it via git you'd have to
  make an initial commit first — **ask the user before doing that.**
- The **per-account memory files** live under the user's home
  (`~/.claude/.../memory/*.md`), NOT in the repo, so they do **not** travel with
  a folder copy. Their lessons are reproduced in §6 below.

---

## 3. How this project is worked (the expected workflow)

The user drives development **epic-by-epic** with a terse **"next"** = "advance to
the next epic in `docs/ROADMAP.md`." All 14 are now done, so a future "next" means
new work beyond the blueprint — confirm scope first.

**Ultracode is ON** (a standing directive): use the **Workflow tool** to run a
multi-agent adversarial review on every substantial change; token cost is not a
constraint.

The per-epic pattern that was followed every time (reproduce it):
1. **Scope** existing scaffolding with an `Explore` subagent (read-only).
2. **Build** the feature.
3. **Adversarial review** via the Workflow tool: ~4 dimension reviewers (e.g.
   correctness, security, framework-correctness, tests) → **each finding
   verified by a skeptic agent** told to refute it → keep only CONFIRMED. Every
   epic's review found real bugs; do not skip it.
4. **Apply** all confirmed fixes (+ a regression test for each where practical).
5. **Verify**: Pest always; a real-browser check for public/user-facing routes.
6. **Update** `docs/ROADMAP.md` + `CLAUDE.md` + the memory files.
7. **Reset** the dev DB: `php artisan migrate:fresh --seed`.
8. **Stage** with `git add -A` — **never commit.**

---

## 4. Toolchain & environment gotchas (these bite)

- **Composer is NOT on PATH:** `php "$HOME/.local/bin/composer.phar" <args>`
  (quote it — there's a space in `$HOME`).
- **Tests:** `php artisan test` or `./vendor/bin/pest`. `phpunit.xml` pins
  `memory_limit=512M` (dompdf blows past 128M across the full suite). SQLite
  `:memory:`, sync queue, array mail/cache — all set in `phpunit.xml`.
- **Pest exit-code gotcha:** the harness shows pest output as JSON
  (`{"tool":"pest","result":"failed","tests":N,"passed":N}`). If `passed == tests`
  but `result` is `"failed"` and there's no `failed` field, it's usually a PHPUnit
  **error event** (not an assertion failure) — most often an **invalid data
  provider**. Diagnose with
  `./vendor/bin/pest <file> --log-events-verbose-text /tmp/ev.txt` then grep for
  "Triggered PHPUnit Error". Known trap: a flat 2-element dataset
  `->with([A::class, B::class])` is misread as a `[class, method]` callable —
  **wrap each value in its own array**: `->with([[A::class],[B::class]])`.
- **Stale blade views:** if the dev server serves old markup after a blade edit,
  a leftover view cache is blocking recompilation — run `php artisan optimize:clear`.
  (`php artisan optimize` is run in prod builds; it caches views/config/routes.)
- **`route:cache` safety:** no Closure routes allowed (Railpack runs `optimize` at
  build). The sign-pad 404 override is an invokable `NotFoundController`.
- **Dev server:** `php artisan serve --port=8000`, or the browser tool's
  `preview_start` with name `optitide` (from `.claude/launch.json`).
- **In-app browser limitation:** it can't hold an authenticated Filament panel
  session (session-cookie quirk), so **verify PUBLIC routes in-browser and rely on
  Pest for authenticated admin/client panels.** Screenshots occasionally time out —
  fall back to `read_page` / `javascript_tool`.
- **Reset DB:** `php artisan migrate:fresh --seed`. Seeders must NOT use
  `WithoutModelEvents` (the `User::created` event generates referral codes).

---

## 5. Architecture rules you must not violate

These are all in `CLAUDE.md` in detail — the highest-impact ones:

- **Money** = integer minor units (cents) + sibling `currency` char(3); cast via
  `MoneyCast` → immutable `App\Support\Money`. Filament forms edit dollars,
  dehydrate to cents.
- **Order pipeline**: never set `orders.state` directly — call
  `Order::transitionTo(...)` (enforces the transition map + writes audit rows).
- **Client masking / stealth AI**: internal pipeline stages and "AI" must never
  leak to the client portal (`OrderState::clientFacingLabel()`, chat
  `displayRoleFor()`, `agent.typing` wire event).
- **RBAC**: roles admin | va | client. **Policies are model-global across BOTH
  panels** — a restrictive policy on a model shared with the client panel breaks
  the client panel; gate the *admin* resource with `canAccess()=isAdmin` instead.
  **No `Gate::before`** (it would bypass `UserPolicy`'s self-delete guard).
- **GST is INCLUSIVE**: `InvoiceService::gstComponent()` backs GST out of the
  total (total/11), never added on top; admin flow + Stripe webhook both use it.
- **Untrusted content**: user/AI HTML → `App\Support\HtmlSanitizer` (DOM
  allow-list, never regex); user URLs → `App\Support\SafeUrlFetcher` (SSRF guard);
  LLM HTML previews only in `srcdoc + sandbox="allow-scripts"` iframes; JSON-LD
  needs `JSON_HEX_TAG`.
- **Config-gated / fail-closed** external services (mirror `GitHubService`,
  `NullSocialDistributor`, `NullWhmClient`).
- **Idempotency**: compare-and-swap claims for anything that charges, emails, or
  publishes (Stripe webhook, reminders, social distribution, commissions, monitor
  auto-ticketing).

---

## 6. Lessons learned (from the per-account memory — reproduced so they survive the handoff)

- **Filament non-string-cast edit crash**: a `MoneyCast` object OR a
  spatie-translatable `{"en":…}` array 500s the Filament Edit form — flatten to a
  string in `mutateFormDataBeforeFill`. **Always test the edit render.**
- **Custom Filament Page IDOR**: custom `Page` classes get NO automatic record
  scoping — any public Livewire property selecting a record must be `#[Locked]`
  AND owner-scoped.
- **Untrusted HTML**: regex sanitisers are bypassable (entity-encoded schemes,
  slash-separated `on*`) — use a DOM allow-list; inline JSON-LD needs `JSON_HEX_TAG`.
- **SSRF-safe fetching**: validate the **resolved** IP (IPv6 NAT64/6to4 embed an
  IPv4!), pin it against DNS-rebind, allow-list ports 80/443, cap the transfer.
- **Test the positive path, not just empty**: a list/monitor test that only
  asserts the empty state hides data-path bugs (a config-nesting bug in the backup
  page shipped this way) — seed a real fixture.
- **SSO shared-guard boundary**: social login on one panel can authenticate a
  *staff* account into an admin-capable session when panels share a guard + the
  library auto-links by email — reject the disallowed role at `authorizeUserUsing`.
- **Prod-only driver dependency gap**: flipping a disk/cache/DB to an
  s3/redis/pgsql driver fatals in prod unless its package is a real `require`
  dependency — local tests use the default driver and never catch it.
- **Policies are model-global across panels**: (see §5 RBAC) — gate the admin
  resource with `canAccess()`, and chain `->authorizeIndividualRecords('delete')`
  on bulk actions so per-record guards (no self-delete) still apply.

---

## 7. Outstanding work

**Known bug (not yet fixed):** the storefront home page 500s with
`CmsPage::footerPages(): … __PHP_Incomplete_Class returned` when the cache holds a
stale serialized value (seen after a DB reset; `php artisan cache:clear` is only a
workaround). `app/Models/CmsPage.php` caches an Eloquent Collection via
`Cache::rememberForever`; harden it — cache plain arrays (slug+title), or catch an
unserialize/`__PHP_Incomplete_Class` failure and rebuild from the DB — and add a
regression test that poisons the cache key and asserts a 200. Grep for other
`Cache::remember*` calls that cache models with the same risk.

**Go-live (operational, not code):**
- Deploy to Railway per `docs/DEPLOY.md` — **the deploy config has never been run
  against a real Railway project**; expect to iterate on it live.
- Set all go-live secrets (Stripe, Anthropic, mail, S3/`AWS_*`, OAuth, WHM,
  `COMPANY_ABN`/legal-name/address for valid tax invoices).
- Set **Australia** country targeting in Google Search Console (International
  Targeting) for the `.io` property.
- Author real legal copy for the CMS legal pages; change the seeded local passwords.

**Not verified locally (no way to):** the Railway deploy, and anything needing
real Stripe/Anthropic/Reverb/WHM/S3 credentials. Authenticated Filament panels are
covered by Pest render/interaction tests, not the in-app browser.

---

## 8. Quick reference

```bash
# tests
php artisan test                     # or ./vendor/bin/pest
# reset DB
php artisan migrate:fresh --seed
# composer (not on PATH)
php "$HOME/.local/bin/composer.phar" <args>
# clear stale caches (after blade/config edits)
php artisan optimize:clear
# dev server
php artisan serve --port=8000
# a real AI/queue/realtime run also needs:
php artisan queue:work
php artisan reverb:start
php artisan schedule:work
npm run build
```
