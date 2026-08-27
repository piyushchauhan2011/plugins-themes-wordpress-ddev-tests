# Jobs, queues, and search (not deployed)

Hotel Booking Core does **no** scheduled work and **no** search index. A booking request writes MySQL and returns. Room lists are `WP_Query`. Desk lists are `$wpdb->get_results`. Nothing in this folder is wired into `wp-content/` or `.ddev/`.

**Possible:** yes — WordPress can run jobs via **WP-Cron** or **Action Scheduler**, offload heavy work to **RabbitMQ** workers, and serve fast filters/full-text from **Elasticsearch or OpenSearch**. None of that is required for five demo rooms.

```
HTTP request → insert inquiry / save room (MySQL)
             → optional enqueue (Action Scheduler or RabbitMQ)
             → worker: email, export, index document
Search later → Elasticsearch / OpenSearch (not WP_Query meta_query)
```

## What we have today

| Piece | Where |
| --- | --- |
| Room catalog | `WP_Query` on `hb_room` + `meta_query` `hb_guests >= n` in [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) and [`inc/helpers.php`](../wp-content/plugins/hotel-booking-core/inc/helpers.php) |
| Inquiry desk | SQL `SELECT` with `status` / `LIMIT` in [`inc/inquiries.php`](../wp-content/plugins/hotel-booking-core/inc/inquiries.php) |
| After insert | Same PHP request; no email queue, no index job |
| WP-Cron | Core default (spawned on visits); plugin registers **no** events |

## Ladder

1. **WP-Cron on a real crontab** (`DISABLE_WP_CRON`) so jobs run without a visitor. See [jobs-wp-cron.md](jobs-wp-cron.md).
2. **Action Scheduler** for retries and async work still inside WordPress. Same doc.
3. **RabbitMQ** (or another broker) when workers are separate processes, fan-out, or not PHP. See [jobs-queues.md](jobs-queues.md).
4. **Search index** when `meta_query` and `LIKE` on inquiries are too slow. See [jobs-search.md](jobs-search.md).

Object cache and replicas are a different ladder: [SCALING.md](SCALING.md).

## Documents

| Doc | Contents |
| --- | --- |
| [jobs-wp-cron.md](jobs-wp-cron.md) | WP-Cron vs system cron, locks, Action Scheduler |
| [jobs-queues.md](jobs-queues.md) | What to offload, RabbitMQ, ack/idempotency |
| [jobs-search.md](jobs-search.md) | ES/OpenSearch mappings, index-on-write, search API |
| [snippets/README.md](snippets/README.md) | Cron, enqueue, AMQP, ES sketches — **not loaded** |

## What we are not adding in this repo

- No `DISABLE_WP_CRON` in `wp-config.php`
- No Action Scheduler Composer package
- No RabbitMQ or Elasticsearch DDEV services
- No `save_post_hb_room` / insert hooks that publish messages
- No change to `GET /hotel-booking/v1/rooms`
