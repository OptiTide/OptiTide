# Deploying OptiTide to Coolify

OptiTide runs as a **majestic monolith**: one repo, one Docker image, four
process types (web / worker / scheduler / reverb) started with different
commands. On Coolify this is a single **Docker Compose** resource plus a
**managed PostgreSQL** database.

- Build config: [`Dockerfile`](../Dockerfile) (FrankenPHP + PHP 8.3), started via
  [`docker/entrypoint.sh`](../docker/entrypoint.sh), served by
  [`docker/Caddyfile`](../docker/Caddyfile).
- Topology: [`docker-compose.yaml`](../docker-compose.yaml).
- Env template: [`.env.coolify.example`](../.env.coolify.example).

> This replaces the Railway path ([`DEPLOY.md`](DEPLOY.md)). `railway.json` and
> `railway/*.sh` are unused by Coolify and can be ignored or deleted.

## What the image does for you

- Installs the required PHP extensions (`intl`, `bcmath`, `zip`, `pdo_pgsql`,
  `redis`, `gd`, `pcntl`, `opcache`).
- Compiles Vite/Tailwind assets and publishes Filament assets.
- **At container start** (env is present by then), caches config/routes/views and
  — on the web service only — runs `php artisan migrate --force` exactly once.

## Steps

### 1. Create the PostgreSQL database
Coolify → your project → **+ New** → **Database** → **PostgreSQL**. After it
starts, open it and copy the **internal** connection URL
(`postgres://user:pass@host:5432/db`). You'll paste it as `DB_URL`.

### 2. Create the application (Docker Compose)
**+ New** → **Application** → **Public/Private Repository** →
`https://github.com/OptiTide/OptiTide` (connect the GitHub App for a private
repo). Set **Build Pack = Docker Compose**, compose file = `docker-compose.yaml`,
base directory = `/`.

### 3. Set environment variables
Paste [`.env.coolify.example`](../.env.coolify.example) into the resource's
**Environment Variables** and fill in the blanks. Minimum to boot:

- `APP_KEY` — generate locally: `php artisan key:generate --show`
- `APP_URL` — the web domain you'll assign in step 5 (https)
- `DB_URL` — from step 1
- `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` and `REVERB_HOST`
  (the reverb domain from step 5)
- S3 (`AWS_*`) — private uploads, contract PDFs and backups need it in prod
- Stripe, Anthropic, mail, `COMPANY_*` as applicable

> **`REVERB_*` are also build args.** The browser bundle is compiled with
> `VITE_REVERB_*` (derived from these), so they must be set **before the build**.
> If you change `REVERB_HOST`/`REVERB_APP_KEY` later, **redeploy** so the JS is
> recompiled — otherwise websockets fail in the browser even when reverb is up.

### 4. Deploy
Deploy the resource. First deploy: the **web** container publishes assets, runs
migrations, then serves; `worker`/`scheduler` wait for web to become healthy
(`/up`).

### 5. Assign domains
- **web** service → your app domain (e.g. `app.your-domain.com`), target port
  **8080**. Set the same value as `APP_URL`.
- **reverb** service → a websocket domain (e.g. `ws.your-domain.com`), target
  port **8080**. Set the same value as `REVERB_HOST`.

Coolify/Traefik terminates TLS; the containers speak plain HTTP on 8080 and the
app trusts the proxy for https/host.

### 6. Post-deploy
- Point OAuth redirect URIs and the **Stripe webhook** at the web domain; set
  `STRIPE_WEBHOOK_SECRET` (the webhook returns 503 until it is set).
- Seed baseline data if needed via a one-off command on the web service:
  `php artisan db:seed --force` — then change the seeded passwords.
- Verify `/up` returns 200 and the admin panel (`/admin`) renders with styles.

## Operational notes

- **Single-replica services** — keep **web** and **scheduler** at 1 replica. Web
  owns migrations; scheduler would double-fire if scaled. **worker** may scale.
- **Migrations** run in the web entrypoint (guarded by `RUN_MIGRATIONS=true`,
  set only on web in the compose file). No other service migrates.
- **Adopting Redis** — set `REDIS_URL`, flip `CACHE_STORE`/`QUEUE_CONNECTION`/
  `SESSION_DRIVER` to `redis`, and redeploy. The `redis` extension is already in
  the image, so no rebuild-for-extensions step is needed (unlike Railway).
- **Logs** go to stderr (JSON) — view them per service in Coolify.
- **Backups** — the DB is backed up by Coolify's Postgres resource; app file
  backups (`spatie/laravel-backup`) target the S3 `BACKUP_DISK`.

## Production concerns handled in code

Same as the Railway guide — see [`DEPLOY.md` → "Production concerns handled in
code"](DEPLOY.md#production-concerns-handled-in-code): trusted proxies,
`route:cache` safety (no Closure routes), and S3 for the ephemeral filesystem.
