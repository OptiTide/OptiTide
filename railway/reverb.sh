#!/usr/bin/env sh
# Start command for the REVERB (websockets) service. Binds 0.0.0.0:8080; expose
# a public domain on this service targeting port 8080. Railway's edge proxies
# wss:// natively, so the browser connects over 443 (REVERB_SCHEME=https,
# REVERB_PORT=443) while the container listens on 8080.
set -e
exec php artisan reverb:start --host=0.0.0.0 --port=8080
