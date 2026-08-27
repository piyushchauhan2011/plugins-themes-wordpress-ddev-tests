# Debugging PHP errors

WordPress writes PHP notices, warnings, and fatals to `wp-content/debug.log`. Query Monitor shows the same errors in wp-admin. Loki tails that file into Grafana. PHPUnit, wp-env, and GitHub Actions never start Loki. The theme/plugin zip does not include Query Monitor or the DDEV compose file.

```
PHP / WordPress  →  wp-content/debug.log
                 →  ddev logs (php-fpm / nginx stderr)
Query Monitor    →  wp-admin bar (admin only)
Promtail         →  Loki (:3100)  →  Grafana logs dashboard (:3001)
REST / inquiry   →  OpenTelemetry OTLP → Tempo (:3200) → Grafana traces
```

Metrics (inquiry counts) live in [OBSERVABILITY.md](OBSERVABILITY.md). This page is **how to see and fix failures**.

## First 60 seconds (white screen / 500)

1. Container stderr (php-fpm and nginx, including crashes before WordPress loads):

```bash
ddev logs
```

2. WordPress / PHP file log:

```bash
ddev exec tail -n 80 wp-content/debug.log
```

3. Logged in as `admin`, open wp-admin. The **Query Monitor** item in the admin bar lists PHP errors, slow queries, and HTTP calls (OpenSearch, etc.). Hotel managers on `/desk/` do not get this; it is a local admin tool.

`WP_DEBUG` is on in DDEV. `WP_DEBUG_DISPLAY` is **off** so the public theme stays usable. Turn display on only while you are staring at a blank page:

```bash
ddev wp config set WP_DEBUG_DISPLAY true --raw --type=constant
```

Set it back to `false` when you are done. Do not commit `wp-config.php`.

## What seed gives you

`ddev seed-content` installs [Query Monitor](https://wordpress.org/plugins/query-monitor/) (gitignored, like Polylang) and runs `ddev demo-observability` (REST curls, `[hotel-booking]` log lines, a demo PHP warning) so Grafana is not empty. Promtail tails a Docker volume at `/var/log/hotel-booking/debug.log` (not the Mutagen copy of `wp-content/debug.log`). After start, `wp-content/debug.log` is a symlink to that file so `ddev exec tail` still works.

Plugin lines are prefixed `[hotel-booking]` (`hotel_booking_log()` in [`inc/helpers.php`](../wp-content/plugins/hotel-booking-core/inc/helpers.php)). AMQP connect/publish failures already use that helper and then fall back in-request.

## Grafana Loki

`ddev describe` lists **loki** (and **promtail**). Open Grafana:

```bash
ddev launch :3001
```

Use the **Hotel Booking logs** dashboard, or Explore with:

| Query | What it shows |
| --- | --- |
| `{job="wordpress"}` | Whole `debug.log` |
| `{job="wordpress"} \|= "[hotel-booking]"` | Plugin-prefixed lines |
| `{job="wordpress"} \|~ "(?i)fatal"` | Fatals |

Loki is an API, not a dashboard. `ddev launch :3101` hits `/` and Loki returns **404** — that means the service is up. Health: `ddev launch :3101/ready` (should print `ready`). Read logs in Grafana, not here. PHPUnit never starts these containers.

## Grafana Tempo (traces)

Core REST (`/rooms`, `/metrics`), inquiry insert, and OpenSearch HTTP run inside named OpenTelemetry spans when `WP_OTEL_ENDPOINT` is set (`http://tempo:4318` on DDEV). There is no PHP OTel extension.

`ddev describe` lists **tempo**. Grafana **Hotel Booking traces** lists traces in a table (click a Trace ID for the waterfall in Explore), or Explore Tempo with `{ resource.service.name="hotel-booking" }`. Tempo has no UI at `/` (`ddev launch :3201` is a 404); health is `ddev launch :3201/ready`.

Refill dashboards without reseeding:

```bash
ddev demo-observability
```

PHPUnit never starts Tempo. The SDK may be in project `vendor/` from Composer; the exporter no-ops without `WP_OTEL_ENDPOINT`.

## Xdebug (breakpoints)

Xdebug is off by default (`xdebug_enabled: false`). Turn it on for a session:

```bash
ddev xdebug on
```

Point the IDE at the DDEV Xdebug docs for this PHP version, then `ddev xdebug off` when you are done (it slows every request).

## Static checks before a guess

A 500 on `/booking/` or `/wp-json/` is often a PHP parse error or a missing function. Run:

```bash
ddev phpcs
ddev phpstan
ddev phpunit --filter Test_Hotel_Booking
```

Fatals in Hotel Booking Core show a file under `wp-content/plugins/hotel-booking-core/`. Theme fatals are under `wp-content/themes/hotel-booking/`.

## Plugin fatals vs optional services

| Symptom | Likely cause | What to do |
| --- | --- | --- |
| White screen, `debug.log` has a stack in Core or the theme | PHP fatal | Fix the line; `ddev logs` if the file is empty |
| Desk email delayed, log has `[hotel-booking] AMQP … failed` | RabbitMQ down | Mail still sends in-request; start the project (`ddev start`) |
| Search falls back to `WP_Query`, rooms still list | OpenSearch down | `ddev exec curl -s http://opensearch:9200/_cluster/health`; `ddev wp hotel-booking reindex` |
| Query Monitor HTTP panel shows 7xx/timeouts to `opensearch` | Same | Optional cluster; not a theme bug |
| Metrics empty in Grafana | Prometheus scrape | [OBSERVABILITY.md](OBSERVABILITY.md); `ddev exec curl -s http://web/wp-json/hotel-booking/v1/metrics` |

## Production (not in this zip)

Set `WP_DEBUG` false (and do not define `WP_DEBUG_DISPLAY` true). Do not copy Loki/Promtail compose or Query Monitor onto the server. Host PHP-FPM/nginx logs, or a vendor, replace this local stack. See [DEPLOYMENT.md](DEPLOYMENT.md).
