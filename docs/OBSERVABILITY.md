# Prometheus and Grafana (DDEV)

WordPress exposes inquiry counts as Prometheus text. DDEV scrapes that endpoint and Grafana graphs it. PHPUnit, wp-env, and GitHub Actions never start Prometheus. The theme/plugin zip does not include the DDEV compose file.

```
WordPress  GET /wp-json/hotel-booking/v1/metrics  (text/plain)
        ← scrape every 15s ← Prometheus (:9090 / HTTPS :9091)
REST / inquiry / OpenSearch
        → OpenTelemetry OTLP HTTP (:4318) → Tempo (:3200 / HTTPS :3201)
                                    ← Grafana datasource + dashboards (:3000 / HTTPS :3001)
```

## What runs locally

Prometheus, Grafana, Loki, Promtail, and Tempo are the **`observability`** Compose profile. Default `ddev start` does not run them. Metrics still exist as `GET /wp-json/hotel-booking/v1/metrics`.

```bash
ddev start --profiles=observability
ddev start-profiles observability
```

`ddev describe` then lists **prometheus** and **grafana**. They are extra Docker services, like OpenSearch (`search`) and RabbitMQ (`queue`).

| Service | Open | Notes |
| --- | --- | --- |
| Metrics | `ddev exec curl -s http://web/wp-json/hotel-booking/v1/metrics` | Prometheus exposition text from [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) |
| Prometheus | `ddev launch :9091` | Scrape job `hotel-booking` → `web:80` + `/wp-json/hotel-booking/v1/metrics` |
| Grafana | `ddev launch :3001` | Anonymous Viewer, or `admin` / `admin`. Provisioned **Hotel Booking**, **logs**, and **traces** dashboards |
| Tempo | `ddev launch :3201/ready` | OTLP from PHP (`WP_OTEL_ENDPOINT`). No UI at `/` — traces are in Grafana |

nginx FastCGI already bypasses `/wp-json/`, so scrapes hit PHP.

## Metrics

| Name | Meaning |
| --- | --- |
| `hotel_booking_inquiries{status=…}` | `COUNT(*)` for `pending`, `contacted`, and `closed` |
| `hotel_booking_opensearch_up` | `1` if OpenSearch answers `/_cluster/health`, else `0` |

The route uses `permission_callback` `__return_true` (same as rooms) so Prometheus can scrape without a cookie. **Do not leave this unauthenticated in production.** Put it behind the private network, basic auth, or a scrape token.

`rest_pre_serve_request` serves this route as `text/plain`. WordPress REST would otherwise `json_encode` the body.

PHPUnit calls `rest_do_request( '/hotel-booking/v1/metrics' )` and asserts the text. It does not start a Prometheus container.

## Traces (Tempo)

Hotel Booking Core starts OpenTelemetry spans around REST rooms/metrics, inquiry insert, and OpenSearch requests ([`inc/tracing.php`](../wp-content/plugins/hotel-booking-core/inc/tracing.php)). The PHP SDK talks OTLP HTTP to Tempo. PHPUnit and GitHub Actions never start Tempo; without `WP_OTEL_ENDPOINT`, or when `tempo` does not resolve, the wrapper still runs the callback.

After `ddev seed-content` (or `ddev demo-observability`) Grafana **Hotel Booking traces** lists matching traces in a table (the waterfall Traces view only renders one Trace ID). Click a Trace ID to open Explore. If the table is empty, widen the time picker past Last 5 minutes.

## Production (not in this zip)

Run Prometheus, Grafana, and Tempo (or a vendor) on the host. Point a scrape job at the WordPress metrics URL on the private network. Set `WP_OTEL_ENDPOINT` only on a private collector. Do not copy [`.ddev/docker-compose.observability.yaml`](../.ddev/docker-compose.observability.yaml) onto the server. Protect the metrics endpoint before it is reachable from the public internet.

PHP errors, `wp-content/debug.log`, Query Monitor, and Loki (same Grafana, different dashboard) are documented in [DEBUG.md](DEBUG.md).
