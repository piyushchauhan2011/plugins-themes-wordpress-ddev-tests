# Offloading work and RabbitMQ

The HTTP request should **commit MySQL and return**. Email, search indexing, and “notify Slack” belong in a worker. If the worker is down, the inquiry row must still exist; the job retries or the same request falls back.

## Live path (DDEV)

[`inc/amqp.php`](../wp-content/plugins/hotel-booking-core/inc/amqp.php) uses project Composer [`php-amqplib/php-amqplib`](https://github.com/php-amqplib/php-amqplib) (not a plugin package). The zip does not ship `vendor/`.

| Trigger | Routing key | Queue | Worker |
| --- | --- | --- | --- |
| `hotel_booking_insert_inquiry()` | `inquiry.created` | `hotel-booking.email` | `wp_mail` desk; set `desk_mailed_at` |
| Daily WP-Cron stale pending | `inquiry.remind` | `hotel-booking.email` | `wp_mail` desk; set `reminded_at` |
| Daily WP-Cron digest | `desk.digest` | `hotel-booking.email` | `wp_mail` “N pending inquiries” |
| `save_post_hb_room` | `room.updated` / `room.deleted` | `hotel-booking.search` | OpenSearch PUT/DELETE |
| `before_delete_post` | `room.deleted` | `hotel-booking.search` | OpenSearch DELETE |

Pattern:

1. Write the source of truth (MySQL).
2. Publish a **small message** (`inquiry_id`, `room_id`, `pending_count`).
3. Return 200 / redirect.
4. Consumer loads the row, does I/O, **acks**.

If publish fails (broker down, library missing, `WP_AMQP_HOST` unset), the plugin runs the same mail/index function in the request. No transactional outbox.

```bash
ddev start --profiles=queue    # RabbitMQ is optional; default start skips it
ddev wp hotel-booking worker   # blocking consume (DDEV daemon runs this when rabbitmq resolves)
ddev launch :15673             # RabbitMQ management (rabbitmq / rabbitmq)
ddev launch :8026              # Mailpit
```

PHPUnit never defines `WP_AMQP_HOST`, so tests exercise the in-request fallback.

## Action Scheduler vs RabbitMQ

Use **Action Scheduler** ([jobs-wp-cron.md](jobs-wp-cron.md)) when:

- One WordPress app, PHP-only workers
- Tens of jobs per minute
- You want retries in wp-admin

Use a **broker (RabbitMQ)** when:

- Workers are separate processes or languages
- Fan-out (email **and** index **and** analytics from one `inquiry.created`)
- Backpressure (web can publish faster than OpenSearch can index)
- You already run AMQP for other services

This project publishes from the request **after** insert. Failure is logged and the in-request fallback runs.

## RabbitMQ shape

```
WordPress  --publish-->  exchange hotel-booking (topic, durable)
                              ├─ queue hotel-booking.email   (inquiry.created, inquiry.remind, desk.digest)
                              └─ queue hotel-booking.search  (room.updated, room.deleted)
```

- **Consumer:** `wp hotel-booking worker` (loads WordPress, then php-amqplib)
- **Ack:** after success. **Nack / requeue** on retryable errors
- **Idempotency:** `desk_mailed_at` / `reminded_at` so double delivery does not double-email

Sketch (not loaded): [`snippets/rabbitmq-publish.php.example`](snippets/rabbitmq-publish.php.example).

### Connection and credentials

Web and workers use `WP_AMQP_HOST`, `WP_AMQP_PORT`, `WP_AMQP_USER`, `WP_AMQP_PASS`, `WP_AMQP_VHOST` from the environment / `wp-config.php`, not git. DDEV values: host `rabbitmq`, port `5672`, user/pass `rabbitmq`, vhost `/`. The plugin skips AMQP when that hostname does not resolve (no `queue` profile). Production: a private AMQP URL — do not copy the DDEV compose file. See [DEPLOYMENT.md](DEPLOYMENT.md).

## Failure modes

- Broker down: insert succeeded; email/index still happens in-request. Robust production can add an outbox row in the **same** MySQL transaction; this plugin does not.
- Worker crash after side effect before ack: at-least-once delivery → idempotent consumers (`desk_mailed_at`).
- Poison message: nack/requeue can loop; a dead-letter queue is the next step, not coded here.

Index search documents from the **search** queue: [jobs-search.md](jobs-search.md).
