# Prometheus and Grafana (DDEV)

WordPress exposes inquiry counts as Prometheus text. DDEV scrapes that endpoint and Grafana graphs it. PHPUnit, wp-env, and GitHub Actions never start Prometheus. The theme/plugin zip does not include the DDEV compose file.

```
WordPress  GET /wp-json/hotel-booking/v1/metrics  (text/plain)
        ← scrape every 15s ← Prometheus (:9090 / HTTPS :9091)
                                    ← Grafana datasource + dashboard (:3000 / HTTPS :3001)
```

## What runs locally

`ddev describe` lists **prometheus** and **grafana**. They are extra Docker services, like OpenSearch and RabbitMQ.

| Service | Open | Notes |
| --- | --- | --- |
| Metrics | `ddev exec curl -s http://web/wp-json/hotel-booking/v1/metrics` | Prometheus exposition text from [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) |
| Prometheus | `ddev launch :9091` | Scrape job `hotel-booking` → `web:80` + `/wp-json/hotel-booking/v1/metrics` |
| Grafana | `ddev launch :3001` | Anonymous Viewer, or `admin` / `admin`. Provisioned **Hotel Booking** dashboard |

nginx FastCGI already bypasses `/wp-json/`, so scrapes hit PHP.

## Metrics

| Name | Meaning |
| --- | --- |
| `hotel_booking_inquiries{status=…}` | `COUNT(*)` for `pending`, `contacted`, and `closed` |
| `hotel_booking_opensearch_up` | `1` if OpenSearch answers `/_cluster/health`, else `0` |

The route uses `permission_callback` `__return_true` (same as rooms) so Prometheus can scrape without a cookie. **Do not leave this unauthenticated in production.** Put it behind the private network, basic auth, or a scrape token.

`rest_pre_serve_request` serves this route as `text/plain`. WordPress REST would otherwise `json_encode` the body.

PHPUnit calls `rest_do_request( '/hotel-booking/v1/metrics' )` and asserts the text. It does not start a Prometheus container.

## Production (not in this zip)

Run Prometheus and Grafana on the host (or a vendor). Point a scrape job at the WordPress metrics URL on the private network. Do not copy [`.ddev/docker-compose.observability.yaml`](../.ddev/docker-compose.observability.yaml) onto the server. Protect the endpoint before it is reachable from the public internet.

This pass does not add nginx `stub_status`, `mysqld_exporter`, or OpenTelemetry.

PHP errors, `wp-content/debug.log`, Query Monitor, and Loki (same Grafana, different dashboard) are documented in [DEBUG.md](DEBUG.md).
