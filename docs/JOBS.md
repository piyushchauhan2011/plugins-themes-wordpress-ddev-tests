# Jobs, queues, and search

MariaDB is the source of truth. A booking request **inserts the inquiry and returns**. RabbitMQ carries a small id payload; a worker sends desk mail and indexes rooms. If the broker is down, the same `wp_mail` / OpenSearch call runs in the HTTP request (same idea as OpenSearch → `WP_Query`).

No Action Scheduler. PHPUnit, wp-env, and GitHub Actions never start RabbitMQ. The theme/plugin zip does not include the DDEV compose file.

```
HTTP request → insert inquiry / save room (MySQL)
             → publish inquiry.created / room.updated (or in-request fallback)
WP-Cron daily → inquiry.remind / desk.digest
Worker        → wp_mail (Mailpit on DDEV) / OpenSearch PUT
Search later  → OpenSearch, or WP_Query if the cluster is down
```

## What we have today

| Piece | Where |
| --- | --- |
| Room catalog | OpenSearch `hotel-booking-rooms` when `WP_OPENSEARCH_HOST` is set; otherwise `WP_Query` in [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php) and [`inc/helpers.php`](../wp-content/plugins/hotel-booking-core/inc/helpers.php) |
| Inquiry desk | SQL `SELECT` with `status` / `LIMIT` in [`inc/inquiries.php`](../wp-content/plugins/hotel-booking-core/inc/inquiries.php); **Mailed** / **Reminded** notes from `desk_mailed_at` / `reminded_at` |
| After insert | `do_action( 'hotel_booking_inquiry_created' )` → AMQP `inquiry.created` or in-request `wp_mail` |
| Room save | AMQP `room.updated` / `room.deleted`, or the same sync PUT/DELETE as before |
| WP-Cron | Plugin schedules `hotel_booking_stale_pending` and `hotel_booking_desk_digest` daily. DDEV sets `DISABLE_WP_CRON` and ticks with a web daemon |

## DDEV

- Add-on [`ddev/ddev-rabbitmq`](https://github.com/ddev/ddev-rabbitmq), image `rabbitmq:4-management` in [`.ddev/.env.rabbitmq`](../.ddev/.env.rabbitmq)
- Management UI: `ddev rabbitmq launch` or `ddev launch :15673` (user `rabbitmq` / `rabbitmq`)
- Mailpit: `ddev launch :8026`
- `WP_AMQP_*` and `DISABLE_WP_CRON` from `post-start` in [`.ddev/config.yaml`](../.ddev/config.yaml)
- Daemons on the web container: `wp-cron-tick` (`wp cron event run --due-now` every 60s) and `hotel-booking-worker` (`wp hotel-booking worker`)
- Client library: project Composer `php-amqplib/php-amqplib` (not a plugin package). `ddev composer install` puts it in `vendor/`

```bash
ddev describe
ddev wp hotel-booking remind-stale
ddev wp hotel-booking digest
ddev wp hotel-booking reindex
```

## Ladder

1. **WP-Cron on a real crontab** (`DISABLE_WP_CRON`) so jobs run without a visitor. Live on DDEV; host notes in [jobs-wp-cron.md](jobs-wp-cron.md).
2. **Action Scheduler** is still the usual WordPress-only queue if you drop the broker. Same doc; **not** a Composer dependency here.
3. **RabbitMQ** for email and async OpenSearch. Live path is [`inc/amqp.php`](../wp-content/plugins/hotel-booking-core/inc/amqp.php). Sketches: [jobs-queues.md](jobs-queues.md).
4. **Search index** — **running on DDEV** (rooms only). Inquiries stay in MariaDB. See [jobs-search.md](jobs-search.md).

Object cache and replicas are a different ladder: [SCALING.md](SCALING.md).

## Documents

| Doc | Contents |
| --- | --- |
| [jobs-wp-cron.md](jobs-wp-cron.md) | WP-Cron vs system cron, DDEV tick, Action Scheduler (not used) |
| [jobs-queues.md](jobs-queues.md) | Topology, ack/idempotency, host AMQP URL |
| [jobs-search.md](jobs-search.md) | OpenSearch on DDEV, mapping, async index with sync fallback |
| [snippets/README.md](snippets/README.md) | Cron, enqueue, AMQP sketches — **not loaded**. Live path is the plugin |

## What we are not adding in this repo

- No Action Scheduler Composer package
- No RabbitMQ in GitHub Actions or wp-env
- No inquiry documents in OpenSearch
- No transactional outbox (publish is fire-and-forget; in-request fallback if the broker is down)
- PHPUnit still has **no** broker: `WP_AMQP_HOST` is unset, so mail and index run in-request
