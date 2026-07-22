# Deploying OptiTide CRM on Coolify

The app ships a `Dockerfile` (PHP 8.3 + Apache with `pdo_pgsql`, docroot =
`public/`) and a `docker-compose.yaml` (app + PostgreSQL + Redis). Two common
ways to deploy:

## Option A — Docker Compose (self-contained: app + Postgres + Redis)

1. In Coolify: **New Resource → Docker Compose**, point it at this repo.
2. Coolify reads `docker-compose.yaml`. Set the environment variables below in
   the Coolify UI (they map to the `${...}` references). `APP_KEY` and
   `DB_PASSWORD` are **required** — the stack refuses to start without them.
3. Deploy. `db` (Postgres) keeps a persistent `pgdata` volume and `redis` a
   `redisdata` volume; the `app` entrypoint waits for Postgres and migrates
   automatically. Sessions + cache use Redis (`SESSION_DRIVER=redis`).

## Option B — Dockerfile + managed Postgres/Redis

1. **New Resource → Dockerfile** (or Application) against this repo.
2. Attach a Coolify-managed PostgreSQL and Redis.
3. Set `DB_CONNECTION=pgsql` + the `DB_*` vars (or a single `DATABASE_URL`), and
   `REDIS_HOST`/`REDIS_PORT` (or `REDIS_URL`) with `SESSION_DRIVER=redis`.
4. Expose port 80. Coolify's proxy terminates HTTPS in front.

## Required Environment Variables

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.yourdomain.com          # your real URL (for email/pay links)
APP_KEY=base64:...                          # generate once: php bin/console key:generate

DB_CONNECTION=pgsql
DB_HOST=...            DB_PORT=5432
DB_DATABASE=optitide  DB_USERNAME=optitide  DB_PASSWORD=change-me
# ...or a single managed-Postgres URL (overrides the DB_* values):
# DATABASE_URL=postgres://user:pass@host:5432/optitide

# Redis — sessions, cache, login rate limiting
REDIS_HOST=...        REDIS_PORT=6379       # or REDIS_URL=redis://:pass@host:6379
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Email (Resend) — verify your sending domain in Resend first
MAIL_DRIVER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=Hello@OptiTide.io

# AU tax identity (required for valid tax invoices)
COMPANY_LEGAL_NAME=OptiTide Pty Ltd
COMPANY_ABN=12 345 678 901
COMPANY_GST_REGISTERED=true

# Payments
PAYID_ENABLED=true   PAYID_TYPE=mobile   PAYID_VALUE=04xxxxxxxx
PAYID_ACCOUNT_NAME=OptiTide Pty Ltd
BANK_BSB=062-000     BANK_ACCOUNT_NUMBER=12345678
PAYONEER_ENABLED=true  PAYONEER_MODE=manual
```

## First Boot

- Migrations run automatically (entrypoint, with a DB-ready wait loop).
- To seed the demo catalogue + admin login once, set `SEED_ON_BOOT=true` for the
  first deploy, then remove it. **Change the seeded passwords immediately.**
- Health check: `GET /health` returns `{"status":"ok"}`.

## Scheduled Tasks

Add these as Coolify **Scheduled Tasks** against the app container. A command
that is never scheduled fails silently — nothing errors, the work simply never
happens — so check this list against `bin/console` after adding any command.

| Command                                             | Schedule          | What breaks without it                      |
|-----------------------------------------------------|-------------------|---------------------------------------------|
| `php bin/console invoices:recurring --send`         | daily, e.g. 6am   | Recurring clients are never invoiced        |
| `php bin/console invoices:overdue`                  | daily, e.g. 7am   | Overdue invoices never change status        |
| `php bin/console clients:suspend-overdue`           | daily, e.g. 8am   | Non-payers keep their services indefinitely |
| `php bin/console blacklist:check`                   | daily             | Domain/IP blacklisting goes unnoticed       |
| `php bin/console seo:reports`                       | monthly, 1st      | No month-end SEO report cards are raised    |
| `php bin/console emails:prune`                      | daily, e.g. 3am   | Email bodies are kept forever (see below)   |
| `php bin/console tickets:import-email`              | every 5-15 min\*  | Inbound client emails never become tickets  |

\* Only if IMAP is configured for the helpdesk; it needs the `imap` extension
and is a no-op otherwise.

### Email log retention

`emails:prune` enforces retention for the Email Log (Admin > Email Log).
Defaults: clear message **bodies** after 90 days, delete rows entirely after
730. Override per environment:

```
php bin/console emails:prune --days=30 --keep-days=365
```

The two stages are separate on purpose. Bodies are the bulk of the table and the
part holding personal data, so they go early. The metadata — who was emailed,
when, and whether it sent — is small, and is exactly what you want a year later
when a client says an invoice never arrived.

## Notes

- Uploads/PDFs are generated on the fly and streamed; nothing is written to a
  shared disk, so a single app container is all you need.
- If you later scale to multiple app replicas, move sessions to a shared store
  and run migrations as a one-off release command instead of on every boot
  (`RUN_MIGRATIONS=false` on the replicas).
