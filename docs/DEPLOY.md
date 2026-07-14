# Deploying OptiTide to Railway

OptiTide deploys as a **majestic monolith**: one repository, several Railway
services that share the same build (Railpack + FrankenPHP) but run different
process commands. This guide covers the topology, per-service settings, and the
production concerns baked into the code.

> Builder: **Railpack** is Railway's default and auto-detects Laravel — it
> installs PHP 8.3 (from `composer.json`), runs `npm run build`, serves from
> `public/` via FrankenPHP, and runs `php artisan optimize` at build time.
> `railway.json` pins the builder explicitly.

## Services

Create these services in one Railway project, all deployed from this repo. Only
the **Start Command** and a few variables differ between them.

| Service | Start command | Public domain? | Notes |
|---|---|---|---|
| **web** | *(Railpack default — FrankenPHP)* | Yes → port 8080 | Pre-deploy: `sh railway/release.sh` (runs `migrate --force` once). Health check path `/up`. |
| **worker** | `sh railway/worker.sh` | No | `queue:work` (database queue); self-exits hourly to recycle — see restart policy below. |
| **scheduler** | `sh railway/scheduler.sh` | No | `schedule:work` — 60s loop (Railway cron floors at 5 min; `monitors:check` needs 5 min). **Run exactly 1 replica.** |
| **reverb** | `sh railway/reverb.sh` | Yes → port 8080 | Websockets; the edge proxies `wss://` over 443. |
| **Postgres** | *(managed plugin)* | — | Provides `DATABASE_URL`. |
| **Redis** | *(managed plugin, optional)* | — | Only needed if you switch cache/queue/session to redis or scale Reverb. |

### Per-service settings

- **web** — set the **Pre-Deploy Command** to `sh railway/release.sh` and the
  **Health Check Path** to `/up`. This is the only service that migrates.
- **worker / scheduler** — set their **Start Command** as above. No pre-deploy
  migrate, no health check path (they serve no HTTP).
- **reverb** — Start Command as above; if you set a **Health Check Path**, use
  `/up` (Reverb's own health endpoint returns 200 — a plain `/` returns 404 and
  would fail the check). Leaving it unset is also fine.
- **worker restart policy** — `railway.json` sets `restartPolicyType: ALWAYS`.
  This matters: `queue:work --max-time=3600` exits cleanly (code 0) to recycle
  each hour, and only `ALWAYS` restarts a clean exit (`ON_FAILURE` would leave
  the worker dead and silently stop all queue processing an hour after deploy).
- **scheduler must be a single replica** — if scaled, each replica runs
  `schedule:run` every minute; `->withoutOverlapping()` (a `database`-cache lock,
  shared across instances) dedupes most of it, but keep it at 1 to be safe.
- **All services** — set `RAILPACK_SKIP_MIGRATIONS=1` so Railpack's own start
  script doesn't also migrate (only the web pre-deploy should).

## Environment variables

Start from [`.env.production.example`](../.env.production.example) — it documents
every key with Railway reference-variable syntax. The essentials:

- `APP_KEY` — `php artisan key:generate --show` and paste it (identical across
  all services).
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<web-domain>`.
- `DB_CONNECTION=pgsql`, `DB_URL=${{Postgres.DATABASE_URL}}` (config also reads a
  raw `DATABASE_URL` as a fallback).
- `LOG_CHANNEL=stderr` + `LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter`.
- `SESSION_SECURE_COOKIE=true`.
- Reverb: `REVERB_*` on all services; on the **reverb** service the container
  binds `REVERB_SERVER_PORT=8080`, while the browser uses `REVERB_HOST=<reverb
  domain>`, `REVERB_PORT=443`, `REVERB_SCHEME=https`.
- **Build-time (`VITE_*`)** — `VITE_APP_NAME` and `VITE_REVERB_*` are compiled
  into the browser bundle, so they must be set on the **web** service (Railway
  injects service vars at build time). If they're blank at build, websockets
  break in the browser even when the reverb service is healthy.

Set the same shared secrets (`APP_KEY`, DB, Redis, Stripe, Anthropic, S3, mail,
Reverb) on every service that needs them. Use a shared variable group if
available.

## Production concerns handled in code

- **Trusted proxies** — `bootstrap/app.php` calls `$middleware->trustProxies(at:
  '*')`. Without it, behind Railway's edge the app mis-detects scheme/host and
  generates `http://` URLs, drops secure cookies, and breaks OAuth/Reverb
  callback URLs.
- **`route:cache` safety** — the sign-pad neutralizing route is an invokable
  controller (`NotFoundController`), not a Closure, so `php artisan optimize`
  (which Railpack runs at build) succeeds.
- **Ephemeral filesystem → S3** — the container disk is wiped on every redeploy
  and is **not shared** between the web and worker services. Private artifacts
  therefore go to S3 in production:
  - intake uploads + generated SEO reports → `FILESYSTEM_PRIVATE_DISK=s3`
  - signed contract PDFs → `SIGNATURES_DISK=s3`
  - backup archives → `BACKUP_DISK=s3`
  Provision an S3 (or S3-compatible) bucket and set the `AWS_*` vars. The
  `league/flysystem-aws-s3-v3` adapter is already a project dependency, so the
  `s3` disk works out of the box. (Invoices stream on the fly and need no
  storage.)

## Redis (optional)

The app runs on database-backed cache/queue/session out of the box — no Redis
required to launch. To adopt Redis: provision the plugin, set
`REDIS_URL=${{Redis.REDIS_URL}}`, flip `CACHE_STORE`/`QUEUE_CONNECTION`/
`SESSION_DRIVER` to `redis`, and make the extension available — either
`RAILPACK_PHP_EXTENSIONS=redis` (phpredis) or `REDIS_CLIENT=predis` after adding
`predis/predis`. Reverb only needs Redis if you run more than one reverb
instance (`REVERB_SCALING_ENABLED=true`).

## Go-live checklist

1. Provision Postgres (+ Redis if desired) and an S3 bucket.
2. Create the 4 app services from this repo; set start/pre-deploy commands.
3. Set env from `.env.production.example` (generate `APP_KEY`; set `APP_URL`,
   DB/Redis/S3, Stripe, Anthropic, mail, Reverb, `RAILPACK_SKIP_MIGRATIONS=1`).
4. Expose public domains on **web** and **reverb** (both target port 8080).
5. Point OAuth redirect URIs + the Stripe webhook at the web domain; set
   `STRIPE_WEBHOOK_SECRET` (the webhook returns 503 until it's set).
6. Deploy. The web pre-deploy runs migrations; check `/up` returns 200.
7. Seed baseline data if needed (`php artisan db:seed --force` via a one-off) and
   change the seeded local passwords.
8. Set the company/tax vars (`COMPANY_ABN`, `COMPANY_LEGAL_NAME`,
   `COMPANY_ADDRESS_*`) so invoices are valid AU tax invoices; enable
   `STRIPE_AUTOMATIC_TAX=true` only after configuring Stripe Tax on the account.

## Geotargeting (external, one-time)

OptiTide targets Australia on a generic `.io` domain. The pages already emit
`lang="en-AU"`, `og:locale`, `hreflang="en-au"`, `geo.region` meta, and an
`Organization` JSON-LD with an AU address + `areaServed`. The remaining step is
**not codeable**: in **Google Search Console → (legacy) International Targeting →
Country**, set the target country to **Australia** for the `.io` property. (A
country-code TLD like `.com.au` would signal this automatically; `.io` is
generic, so it must be set manually.)
