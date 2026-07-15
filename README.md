# OptiTide CRM

A custom PHP + Bootstrap billing CRM for a small Australian digital agency —
**web design, SEO, SMM and hosting**. No framework: a small hand-written core
(router, PDO layer, migrations, views, auth) on PHP 8.3, themed to the OptiTide
brand and ready for Coolify.

## Features

- **Clients** — CRM records with contacts, ABN, address, outstanding balance.
- **Services & subscriptions** — a catalogue of one-off and recurring services
  grouped into **service lines you can add in-app** (new lines = zero code).
- **Invoicing** — GST-**inclusive** Australian tax invoices (ABN, GST line,
  AUD), a line-item builder, PDF generation (dompdf), email delivery (Resend),
  and a public tokenised pay page.
- **Payments** — **PayID / bank transfer** and **Payoneer payment links**, both
  behind a `PaymentGateway` interface so Stripe / PayTo / GoCardless drop in
  later with no change to the billing core. Payments are reconciled by recording
  them against an invoice.
- **Recurring billing** — hosting/retainers generate a fresh invoice each cycle
  (`invoices:recurring`); overdue invoices are flagged daily (`invoices:overdue`).
- **Client portal** — clients log in to view services, view/pay/download
  invoices, and manage their profile. Everything is owner-scoped.
- **Roles** — `admin`, `staff`, `client`. Admin-only user management + settings.

## Stack

- PHP 8.3, PDO (SQLite for local dev, **PostgreSQL** in production), Bootstrap 5.
- **Redis** (optional) for sessions, cache and login rate limiting — via the
  pure-PHP `predis` client, so no `phpredis` extension is required. Defaults to
  file-based drivers locally.
- Composer dependencies: `dompdf/dompdf` (PDF invoices) + `predis/predis` (Redis).
- Resend for transactional email (plain HTTPS, no SDK).

## Local development

```bash
cp .env.example .env
composer install
php bin/console key:generate
php bin/console migrate:fresh --seed
php -S 127.0.0.1:8000 -t public public/index.php
```

Open http://127.0.0.1:8000. Seeded logins (password `password`):

| Email                | Role   |
|----------------------|--------|
| Hello@OptiTide.io    | admin  |
| va@optitide.io       | staff  |
| client@example.com   | client |

## Console

```bash
php bin/console migrate              # run pending migrations
php bin/console migrate:fresh --seed # rebuild + seed
php bin/console seed                 # seed only
php bin/console key:generate         # generate APP_KEY
php bin/console invoices:recurring   # generate due recurring invoices (add --send to email)
php bin/console invoices:overdue     # mark past-due invoices overdue
```

Run `invoices:recurring` and `invoices:overdue` daily (cron / Coolify scheduled task).

## Configuration

Everything is environment-driven — see `.env.example`. To go live you set:

- `COMPANY_ABN` + legal name/address — required for valid tax invoices.
- `PAYID_VALUE` (+ optional `BANK_BSB`/`BANK_ACCOUNT_NUMBER`) — enables the PayID
  option on invoices.
- `PAYONEER_MODE=manual` — paste a Payoneer request link on each invoice.
- `RESEND_API_KEY` — enables real email (otherwise emails are logged to
  `storage/logs/mail.log`).

## Deploy

See [docs/COOLIFY.md](docs/COOLIFY.md). The repo ships a `Dockerfile`
(PHP 8.3 + Apache with `pdo_pgsql`) and a `docker-compose.yaml`
(app + PostgreSQL + Redis) that Coolify can deploy as-is.
