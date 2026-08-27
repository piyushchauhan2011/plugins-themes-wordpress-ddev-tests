# Jobs, queues, and search

Hotel Booking Core does **no** scheduled work and **no** message queue. A booking request writes MySQL and returns. Desk lists are `$wpdb->get_results`. **Room search** on DDEV talks to OpenSearch when the cluster is up, then falls back to `WP_Query`.

**Possible:** yes — WordPress can run jobs via **WP-Cron** or **Action Scheduler**, and offload heavy work to **RabbitMQ** workers. Indexing here is synchronous on `save_post` (fine for five rooms), not a broker.

```
HTTP request → insert inquiry / save room (MySQL)
             → optional enqueue (Action Scheduler or RabbitMQ) — not wired
             → save_post_hb_room → OpenSearch document (DDEV)
Search later → OpenSearch, or WP_Query if the cluster is down
```

## What we have today

| Piece | Where |
| --- | --- |
| Room catalog | OpenSearch `hotel-booking-rooms` when `WP_OPENSEARCH_HOST` is set; otherwise `WP_Query` in [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) and [`inc/helpers.php`](../wp-content/plugins/hotel-booking-core/inc/helpers.php) |
| Inquiry desk | SQL `SELECT` with `status` / `LIMIT` in [`inc/inquiries.php`](../wp-content/plugins/hotel-booking-core/inc/inquiries.php) |
| After insert | Same PHP request; no email queue. Rooms also `PUT` to OpenSearch on save |
| WP-Cron | Core default (spawned on visits); plugin registers **no** events |

## Ladder

1. **WP-Cron on a real crontab** (`DISABLE_WP_CRON`) so jobs run without a visitor. See [jobs-wp-cron.md](jobs-wp-cron.md).
2. **Action Scheduler** for retries and async work still inside WordPress. Same doc.
3. **RabbitMQ** (or another broker) when workers are separate processes, fan-out, or not PHP. See [jobs-queues.md](jobs-queues.md).
4. **Search index** — **running on DDEV** (rooms only). Inquiries stay in MariaDB. See [jobs-search.md](jobs-search.md).

Object cache and replicas are a different ladder: [SCALING.md](SCALING.md).

## Documents

| Doc | Contents |
| --- | --- |
| [jobs-wp-cron.md](jobs-wp-cron.md) | WP-Cron vs system cron, locks, Action Scheduler |
| [jobs-queues.md](jobs-queues.md) | What to offload, RabbitMQ, ack/idempotency |
| [jobs-search.md](jobs-search.md) | OpenSearch on DDEV, mapping, index-on-write, REST + fallback |
| [snippets/README.md](snippets/README.md) | Cron, enqueue, AMQP sketches — **not loaded**. Room mapping sketch matches the live index |

## What we are not adding in this repo

- No `DISABLE_WP_CRON` in `wp-config.php`
- No Action Scheduler Composer package
- No RabbitMQ DDEV service
- No inquiry documents in OpenSearch
- PHPUnit / wp-env / GitHub Actions still have **no** OpenSearch service
